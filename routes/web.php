<?php

use App\Http\Controllers\AutorController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\FavoritoController;
use App\Http\Controllers\HistorialController;
use App\Http\Controllers\LibroController;
use App\Http\Controllers\LicenciaController;
use App\Http\Controllers\LicenciaEditorialController;
use App\Http\Controllers\NotificacionController;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\PrestamoController;
use App\Http\Controllers\PromocionController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\ResenaController;
use App\Http\Controllers\SuscripcionController;
use App\Http\Controllers\UserController;
use App\Models\Plan;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome', [
        'planes' => Plan::where('estado', Plan::ESTADO_ACTIVA)
            ->with(['promociones', 'libro', 'libros'])
            ->orderBy('precio')
            ->get(),
    ]);
})->name('home');

Route::get('cuenta-suspendida', function () {
    return view('cuenta.suspendida');
})->middleware('auth')->name('cuenta.suspendida');

Route::middleware(['auth', 'verified', 'cuenta-activa'])->group(function () {
    Route::redirect('dashboard', '/libros')->name('dashboard')
        ->middleware('role:superAdmin,admin');

    Route::get('historial', [HistorialController::class, 'index'])->name('historial.index');
    Route::get('suscripciones', [SuscripcionController::class, 'index'])->name('suscripciones.index');

    Route::get('prestamos', [PrestamoController::class, 'index'])->name('prestamos.index');
    Route::post('prestamos/{libro}', [PrestamoController::class, 'store'])->name('prestamos.store');
    Route::post('prestamos/{prestamo}/devolver', [PrestamoController::class, 'devolver'])->name('prestamos.devolver');
    Route::post('prestamos/{prestamo}/renovar', [PrestamoController::class, 'renovar'])->name('prestamos.renovar');

    Route::get('pagos', [PagoController::class, 'index'])->name('pagos.index');
    Route::get('pagos/{pago}', [PagoController::class, 'show'])->name('pagos.show');

    Route::get('favoritos', [FavoritoController::class, 'index'])->name('favoritos.index');
    Route::post('favoritos/{libro}', [FavoritoController::class, 'store'])->name('favoritos.store');
    Route::delete('favoritos/{libro}', [FavoritoController::class, 'destroy'])->name('favoritos.destroy');

    Route::get('notificaciones', [NotificacionController::class, 'index'])->name('notificaciones.index');
    Route::post('notificaciones/marcar-leidas', [NotificacionController::class, 'marcarLeidas'])->name('notificaciones.marcar-leidas');
    Route::post('notificaciones/{notificacion}/leer', [NotificacionController::class, 'marcarLeida'])->name('notificaciones.marcar-leida');

    Route::resource('autores', AutorController::class)
        ->only(['index'])
        ->parameters(['autores' => 'autor']);

    Route::get('libros/{libro}/leer', [LibroController::class, 'leer'])->name('libros.leer');
    Route::get('libros/{libro}/descargar', [LibroController::class, 'descargar'])->name('libros.descargar');
    Route::post('libros/{libro}/resenas', [ResenaController::class, 'store'])->name('resenas.store');
    Route::resource('libros', LibroController::class)->only(['index']);

    Route::get('planes', [PlanController::class, 'index'])->name('planes.index');
    Route::get('planes/suscripcion/{suscripcion}', [PlanController::class, 'confirmacion'])->name('planes.confirmacion');
    Route::post('planes/{plan}/suscribir', [PlanController::class, 'suscribir'])->name('planes.suscribir');
    Route::post('planes/{plan}/cancelar', [PlanController::class, 'cancelar'])->name('planes.cancelar');

    Route::middleware('role:superAdmin,admin')->group(function () {
        Route::resource('autores', AutorController::class)
            ->only(['store', 'update', 'destroy'])
            ->parameters(['autores' => 'autor']);

        Route::post('categorias', [CategoriaController::class, 'store'])->name('categorias.store');

        Route::resource('libros', LibroController::class)
            ->only(['store', 'update', 'destroy']);

        Route::resource('planes', PlanController::class)
            ->only(['store', 'update', 'destroy'])
            ->parameters(['planes' => 'plan']);

        Route::get('licencias', [LicenciaController::class, 'index'])->name('licencias.index');
        Route::post('licencias', [LicenciaController::class, 'store'])->name('licencias.store');
        Route::put('licencias/{licencia}', [LicenciaController::class, 'update'])->name('licencias.update');
        Route::delete('licencias/{licencia}', [LicenciaController::class, 'destroy'])->name('licencias.destroy');

        Route::post('licencias-editoriales', [LicenciaEditorialController::class, 'store'])->name('licencias-editoriales.store');
        Route::put('licencias-editoriales/{licenciaEditorial}', [LicenciaEditorialController::class, 'update'])->name('licencias-editoriales.update');
        Route::delete('licencias-editoriales/{licenciaEditorial}', [LicenciaEditorialController::class, 'destroy'])->name('licencias-editoriales.destroy');
        Route::get('licencias-editoriales/{licenciaEditorial}/pdf', [LicenciaEditorialController::class, 'pdf'])->name('licencias-editoriales.pdf');

        Route::post('usuarios/{usuario}/suspender', [UserController::class, 'suspender'])->name('usuarios.suspender');
        Route::post('usuarios/{usuario}/activar', [UserController::class, 'activar'])->name('usuarios.activar');

        Route::resource('usuarios', UserController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->parameters(['usuarios' => 'usuario']);

        Route::resource('promociones', PromocionController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->parameters(['promociones' => 'promocion']);

        Route::get('reportes', [ReporteController::class, 'index'])->name('reportes.index');
        Route::get('reportes/{tipo}', [ReporteController::class, 'exportar'])->name('reportes.exportar');
    });
});

require __DIR__.'/settings.php';
