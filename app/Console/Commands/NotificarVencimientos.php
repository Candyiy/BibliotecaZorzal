<?php

namespace App\Console\Commands;

use App\Models\Prestamo;
use App\Notifications\PrestamoPorVencer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class NotificarVencimientos extends Command
{
    protected $signature = 'biblioteca:notificar-vencimientos';

    protected $description = 'Notifica los préstamos por vencer y los que ya están vencidos';

    public function handle(): int
    {
        $hoy = today();
        $limite = $hoy->copy()->addDays(2)->toDateString();

        $prestamos = Prestamo::activos()
            ->with(['usuario', 'libro'])
            ->whereDate('f_finalizacion', '<=', $limite)
            ->get();

        $creadas = 0;

        foreach ($prestamos as $prestamo) {
            $usuario = $prestamo->usuario;

            if (! $usuario) {
                continue;
            }

            $vencido = $prestamo->estaVencido();
            $clave = $prestamo->id.'-'.$hoy->toDateString();

            $yaNotificado = $usuario->notifications()
                ->where('type', PrestamoPorVencer::class)
                ->where('data->clave', $clave)
                ->exists();

            if ($yaNotificado) {
                continue;
            }

            $usuario->notify(new PrestamoPorVencer($prestamo, $vencido, $clave));

            Log::info('Notificación de préstamo generada', [
                'prestamo_id' => $prestamo->id,
                'user_id' => $usuario->id,
                'tipo' => $vencido ? 'vencido' : 'por_vencer',
            ]);

            $creadas++;
        }

        $this->info("Notificaciones creadas: {$creadas}");

        return self::SUCCESS;
    }
}
