<?php

namespace App\Notifications;

use App\Models\Prestamo;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PrestamoPorVencer extends Notification
{
    use Queueable;

    public function __construct(
        public Prestamo $prestamo,
        public bool $vencido,
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
            'titulo' => $this->vencido ? 'Préstamo vencido' : 'Préstamo por vencer',
            'mensaje' => $this->vencido
                ? "El préstamo de «{$titulo}» venció el {$fecha}. Devuélvelo para evitar la penalización."
                : "El préstamo de «{$titulo}» vence el {$fecha}.",
            'prestamo_id' => $this->prestamo->id,
            'tipo' => $this->vencido ? 'vencido' : 'por_vencer',
            'url' => route('prestamos.index'),
        ];
    }
}
