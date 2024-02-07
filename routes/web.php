<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

    Route::get('/dashboard', function () {
        return view('dashboard');
    })->middleware(['auth', 'verified'])->name('dashboard2')->middleware('RolValidation:2');
    
    Route::get('/dashboardAdmin', function () {
        return view('dashboardAdmin');
    })->middleware(['auth', 'verified'])->name('dashboard1')->middleware('RolValidation:1', 'catch-unauth-two-factor');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/code-verification', function(){
        return view('auth.code_verification');
    })->name('code-verification')->middleware('catch-auth-two-factor');
});

require __DIR__.'/auth.php';
