<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Jobs\verification_code;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Muestra la vista de inicio de sesión.
     *
     * @return View
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Maneja una solicitud de autenticación entrante.
     *
     * @param  LoginRequest  $request
     * @return RedirectResponse
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        // Verifica el reCAPTCHA
        $recaptcha_response = $request->input('g-recaptcha-response');

        if (is_null($recaptcha_response)) {
            return redirect()->back()->with('status', 'Por favor, completa el reCAPTCHA para continuar');
        }

        $url = "https://www.google.com/recaptcha/api/siteverify";

        $body = [
            'secret' => env('RECAPTCHA_SITE_SECRET'),
            'response' => $recaptcha_response,
            'remoteip' => $request->ip()
        ];

        $response = Http::asForm()->post($url, $body);

        $result = json_decode($response);

        if (!$response->successful() || !$result->success) {
            return redirect()->back()->with('status', 'Error al verificar el reCAPTCHA');
        }

        // Autenticar la solicitud
        $request->authenticate();

        // Regenerar la sesión
        $request->session()->regenerate();
        
        // Obtener el usuario autenticado
        $user = Auth::user();

        // Generar un nuevo código de verificación aleatorio y despachar el trabajo para enviar el código de verificación al usuario si es un administrador
        if ($user->role_id == 1) {
            $newVerificationCode = rand(1000, 9999);
            // Actualizar la base de datos con el nuevo código y deshabilitar la autenticación de dos factores
            User::where('id', $user->id)->update(['verification_code' => $newVerificationCode]);
            User::where('id', $user->id)->update(['two_factor_authenticated' => false]);
            // Despachar un trabajo para enviar el código de verificación al usuario
            verification_code::dispatch($user);
        }

        // Registrar el inicio de sesión en un canal de logs (Slack)
        if($request->user()->role_id == 1){
            Log::channel('slack')->critical('Inicio de sesión de administrador: ' . $request->user()->name . ' con correo: ' . $request->user()->email . ' a las ' . date('H:i:s') . ' del día ' . date('d/m/Y') . '.'.' Rol: Administrador');
        }
        else{
            Log::channel('slack')->info('Inicio de sesión de usuario: ' . $request->user()->name . ' con correo: ' . $request->user()->email . ' a las ' . date('H:i:s') . ' del día ' . date('d/m/Y') . '.');
        }

        // Redirigir al usuario a la página de verificación de código si es un administrador; de lo contrario, redirigir a la página de inicio
        if ($user->role_id == 1) {
            return redirect()->route('code-verification');
        }
        return redirect()->intended(RouteServiceProvider::HOME);
    }

    /**
     * Destruye una sesión autenticada.
     *
     * @param  Request  $request
     * @return RedirectResponse
     */
    public function destroy(Request $request): RedirectResponse
    {
        // Obtener el usuario autenticado
        $user = Auth::user();

        // Si el usuario es un administrador, deshabilitar la autenticación de dos factores
        if ($user->role_id == 1) {
            User::where('id', $user->id)->update(['two_factor_authenticated' => false]);
        }

        // Cerrar la sesión
        Auth::guard('web')->logout();

        // Invalidar la sesión actual
        $request->session()->invalidate();

        // Regenerar el token de la sesión
        $request->session()->regenerateToken();

        // Redirigir al usuario a la página de inicio
        return redirect('/');
    }
}