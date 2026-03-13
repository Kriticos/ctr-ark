<?php

use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\ModuleController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\ProcedureController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SectorController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        $user = auth()->user();

        if ($user?->isAdmin() || $user?->hasPermissionTo('admin.dashboard')) {
            return redirect()->route('admin.dashboard');
        }

        if ($user?->hasPermissionTo('admin.procedures.index')) {
            return redirect()->route('admin.procedures.index');
        }
    }

    return redirect()->route('login');
});

Route::get('/flux-demo', function () {
    return view('flux-demo');
});

// Rotas de Autenticação (apenas para guests)
Route::middleware('guest')->group(function () {
    // Login
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);

    // Register
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);

    // Forgot Password
    Route::get('/forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');

    // Reset Password
    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'reset'])->name('password.update');
});

// Logout (para usuários autenticados)
Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

// Rotas Painel (protegidas por autenticação e ACL)
Route::prefix('painel')->name('admin.')->middleware(['auth', 'check.permission'])->group(function () {
    // Dashboard (sem checagem de permissão específica)
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // Perfil
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile/avatar', [ProfileController::class, 'deleteAvatar'])->name('profile.delete-avatar');
    Route::post('/profile/theme', [ProfileController::class, 'updateTheme'])->name('profile.update-theme');

    // Gestão de Usuários
    Route::resource('users', UserController::class);
    Route::delete('/users/{user}/avatar', [UserController::class, 'deleteAvatar'])->name('users.delete-avatar');

    // Gestão de ACL (Roles, Permissions e Modules)
    Route::resource('roles', RoleController::class);
    Route::resource('permissions', PermissionController::class);
    Route::resource('modules', ModuleController::class);
    Route::resource('sectors', SectorController::class);
    Route::resource('procedures', ProcedureController::class);
    Route::post('/procedures/{procedure}/submit-review', [ProcedureController::class, 'submitForReview'])->name('procedures.submit-review');
    Route::post('/procedures/{procedure}/approve', [ProcedureController::class, 'approve'])->name('procedures.approve');
    Route::post('/procedures/{procedure}/reject', [ProcedureController::class, 'reject'])->name('procedures.reject');
    Route::post('/procedures/{procedure}/publish', [ProcedureController::class, 'publish'])->name('procedures.publish');
    Route::post('/procedures/{procedure}/versions/{version}/restore', [ProcedureController::class, 'restoreVersion'])->name('procedures.versions.restore');
    Route::get('/procedures/{procedure}/compare/{from}/{to}', [ProcedureController::class, 'compareVersions'])->name('procedures.compare');
    Route::post('/procedures/upload-image/{procedure?}', [ProcedureController::class, 'uploadImage'])->name('procedures.images.upload');
    Route::post('/procedures/cleanup-temp-images/{procedure?}', [ProcedureController::class, 'cleanupTempImages'])->name('procedures.images.cleanup-temp');
    Route::get('/procedures/temp-images/{token}/{procedure?}', [ProcedureController::class, 'showTempImage'])->where('token', '[^/]+')->name('procedures.images.temp.show');
    Route::get('/procedures/{procedure}/images/{filename}', [ProcedureController::class, 'showImage'])->where('filename', '[^/]+')->name('procedures.images.show');
    Route::post('/procedures/preview', [ProcedureController::class, 'preview'])->name('procedures.preview');

    // Gestão de Menus
    Route::resource('menus', MenuController::class);
    Route::post('/menus/update-order', [MenuController::class, 'updateOrder'])->name('menus.update-order');
});
