<?php

namespace App\Notifications;

use App\Models\Prestamo;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PrestamoRenovado extends Notification
{
    use Queueable;

    public function __construct(
        public Prestamo $prestamo,
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
        $titulo = $this->prestamo->libro?->titulo ?? 'Libro eliminado';
        $fecha = $this->prestamo->f_finalizacion?->format('d/m/Y') ?? '—';

        return [
            'clave' => $this->clave,
            'titulo' => 'Préstamo renovado',
            'mensaje' => "Renovamos el préstamo de «{$titulo}». La nueva fecha de devolución es {$fecha}.",
            'prestamo_id' => $this->prestamo->id,
            'tipo' => 'renovado',
            'url' => route('prestamos.index'),
        ];
    }
}
