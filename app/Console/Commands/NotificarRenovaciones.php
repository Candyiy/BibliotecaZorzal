<?php

namespace App\Console\Commands;

use App\Models\Suscripcion;
use App\Notifications\SuscripcionPorRenovar;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class NotificarRenovaciones extends Command
{
    protected $signature = 'biblioteca:notificar-renovaciones';

    protected $description = 'Avisa a los suscriptores cuando su plan está por renovarse';

    public function handle(): int
    {
        $hoy = today();
        $limite = $hoy->copy()->addDays(7)->toDateString();

        $suscripciones = Suscripcion::query()
            ->where('estado', 'activa')
            ->whereBetween('f_finalizacion', [$hoy->toDateString(), $limite])
            ->with(['usuario', 'plan'])
            ->get();

        $creadas = 0;

        foreach ($suscripciones as $suscripcion) {
            $usuario = $suscripcion->usuario;

            if (! $usuario) {
                continue;
            }

            $clave = 'renovacion-'.$suscripcion->id.'-'.$hoy->toDateString();

            $yaNotificado = $usuario->notifications()
                ->where('type', SuscripcionPorRenovar::class)
                ->where('data->clave', $clave)
                ->exists();

            if ($yaNotificado) {
                continue;
            }

            $usuario->notify(new SuscripcionPorRenovar($suscripcion, $clave));

            Log::info('Notificación de renovación generada', [
                'suscripcion_id' => $suscripcion->id,
                'user_id' => $usuario->id,
            ]);

            $creadas++;
        }

        $this->info("Notificaciones de renovación creadas: {$creadas}");

        return self::SUCCESS;
    }
}
