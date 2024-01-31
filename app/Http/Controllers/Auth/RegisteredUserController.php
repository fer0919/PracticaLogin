<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Jobs\SendEmail;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $role_id = 1;
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);
        $usuariosRegistrados = User::all()->where('role_id')->count();
        if($usuariosRegistrados > 0){
            $role_id = 2;
        }
        $randomNumber = rand(9999, 1000);
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'role_id' => $role_id,
            'verification_code' => $randomNumber,
            'password' => Hash::make($request->password),
        ]);
        SendEmail::dispatch($user, URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => $randomNumber]
        ));


        Auth::login($user);

        return redirect(RouteServiceProvider::HOME);
    }
}
