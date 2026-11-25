<?php

namespace Tests\Concerns;

use App\Models\Libro;
use App\Models\Licencia;
use App\Models\LicenciaEditorial;
use App\Models\Plan;
use App\Models\Suscripcion;
use App\Models\User;

trait CreaDatosBiblioteca
{
    protected function crearLibroConLicencia(string $titulo = 'Libro de prueba'): Libro
    {
        $libro = Libro::create([
            'titulo' => $titulo,
            'descripcion' => 'Descripción del libro de prueba.',
            'fecha_publicacion' => '2020-01-01',
        ]);

        $editorial = LicenciaEditorial::create([
            'nombre' => 'Licencia de '.$titulo,
            'f_inicio' => today()->toDateString(),
            'f_finalizacion' => today()->addYear()->toDateString(),
        ]);

        Licencia::create([
            'libro_id' => $libro->id,
            'licencia_editorial_id' => $editorial->id,
        ]);

        return $libro;
    }

    protected function activarSuscripcion(User $usuario, ?Plan $plan = null): Suscripcion
    {
        $plan ??= Plan::create([
            'tipo' => 'mensual',
            'precio' => '9.99',
        ]);

        return $usuario->suscripciones()->create([
            'plan_id' => $plan->id,
            'f_inicio' => today()->subDay()->toDateString(),
            'f_finalizacion' => today()->addMonth()->toDateString(),
            'estado' => 'activa',
        ]);
    }
}
