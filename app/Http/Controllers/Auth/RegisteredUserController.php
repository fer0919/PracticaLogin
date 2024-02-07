<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Jobs\SendEmail;
use App\Jobs\verification_code;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rules;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\IpUtils;

class RegisteredUserController extends Controller
{
    /**
     * Muestra la vista de registro.
     *
     * @return View
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Maneja una solicitud de registro entrante.
     *
     * @param  Request  $request
     * @return RedirectResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        // Verifica si el reCAPTCHA se ha completado
        $recaptcha_response = $request->input('g-recaptcha-response');

        if (is_null($recaptcha_response)) {
            return redirect()->back()->with('status', 'Por favor, completa el Recaptcha para continuar');
        }

        // Verifica el reCAPTCHA con Google
        $url = "https://www.google.com/recaptcha/api/siteverify";

        $body = [
            'secret' => env('RECAPTCHA_SITE_SECRET'),
            'response' => $recaptcha_response,
            'remoteip' => IpUtils::anonymize($request->ip()) // Anonimiza la dirección IP para cumplir con GDPR
        ];

        $response = Http::asForm()->post($url, $body);

        $result = json_decode($response);

        if (!$response->successful() || !$result->success) {
            return redirect()->back()->with('status', 'Error al verificar el reCAPTCHA. Por favor, intenta nuevamente.');
        }

        if ($response->successful() && $result->success == true) {
            // Establece el role_id predeterminado en 1 (administrador)
            $role_id = 1;

            // Validación de los datos de registro
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
                'password' => ['required', 'confirmed', Rules\Password::defaults()],
                'phone_number' => ['required', 'min:10', 'max_digits:10', 'numeric'],
            ]);

            // Verifica si ya hay usuarios registrados y ajusta el role_id en consecuencia
            $usuariosRegistrados = User::where('role_id', 1)->count();
            if ($usuariosRegistrados > 0) {
                $role_id = 2;
            }

            // Genera un número de verificación aleatorio de 4 dígitos
            $randomNumber = rand(9999, 1000);

            // Crea un nuevo usuario en la base de datos
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'role_id' => $role_id,
                'password' => Hash::make($request->password),
                'phone_number' => $request->phone_number
            ]);

            // Si el usuario tiene un rol igual a 1 (administrador), actualiza el código de verificación y envía el código de verificación
            if ($role_id == 1) {
                User::where('id', $user->id)->update(['verification_code' => $randomNumber]);
                $updatedUser = User::find($user->id);
                verification_code::dispatch($updatedUser);
            }

            // Envía un correo electrónico de verificación al usuario
            SendEmail::dispatch($user, URL::temporarySignedRoute(
                'verification.verify',
                now()->addMinutes(60),
                ['id' => $user->id, 'hash' => sha1($user->email)]
            ));

            // Autentica al usuario recién registrado
            Auth::login($user);

            return redirect(RouteServiceProvider::HOME);
        } else {
            return redirect()->back()->withErrors('status', 'Error al verificar el reCAPTCHA. Por favor, intenta nuevamente.');
        }
    }

    /**
     * Valida el código de verificación del usuario autenticado.
     *
     * @param  Request  $request
     * @return RedirectResponse
     */
    public function validateCode(Request $request): RedirectResponse
    {
        // Obtiene el código ingresado por el usuario
        $code = $request->code;
        // Obtiene el usuario autenticado
        $user = Auth::user();

        // Verifica si el código ingresado coincide con el código de verificación enviado al usuario
        if ($user->verification_code == $code) {
            // Si coincide, actualiza la autenticación de dos factores del usuario a verdadero
            User::where('id', $user->id)->update(['two_factor_authenticated' => true]);
        }

        // Redirige al usuario al dashboard de administrador
        return redirect()->route('dashboard1');
    }
}