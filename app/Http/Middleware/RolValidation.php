<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RolValidation
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        try{
             // Verificar si el rol del usuario está en la lista de roles permitidos(admin)
            if (!in_array($request->user()->role_id, $roles)){
                // Redirigir si el usuario no tiene el rol permitido
                return redirect()->route('dashboard'. $request->user()->role_id)->with('error', 'No tienes acceso a esta pagina.');
            }
            // Continuar con la solicitud si el usuario tiene el rol permitido
            return $next($request);
        }
        catch(\Exception $e){
            // Manejar excepciones y redirigir a la ruta principal en caso de error
            return redirect()->route('/');
        }
    }
}
