<?php

namespace App\Notifications;

use App\Models\Suscripcion;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SuscripcionPorRenovar extends Notification
{
    use Queueable;

    public function __construct(
        public Suscripcion $suscripcion,
        public string $clave,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $plan = $this->suscripcion->plan?->tipo ?? 'plan';
        $fecha = $this->suscripcion->f_finalizacion?->format('d/m/Y') ?? '—';

        return [
            'clave' => $this->clave,
            'titulo' => $this->suscripcion->esPrueba() ? 'Prueba por finalizar' : 'Suscripción por renovar',
            'mensaje' => $this->suscripcion->esPrueba()
                ? "Tu período de prueba del plan {$plan} termina el {$fecha}. Elige un plan para seguir leyendo."
                : "Tu plan {$plan} se renueva el {$fecha}. Revisa tus opciones antes de esa fecha.",
            'suscripcion_id' => $this->suscripcion->id,
            'tipo' => 'renovacion',
            'url' => route('planes.index'),
        ];
    }
}
