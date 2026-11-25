<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password', 'estado', 'suspendido_hasta'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
// class User extends Authenticatable implements MustVerifyEmail, PasskeyUser
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    public const ROL_SUPER_ADMIN = 'superAdmin';

    public const ROL_ADMIN = 'admin';

    public const ROL_CLIENTE = 'cliente';

    public const ESTADO_ACTIVO = 'activo';

    public const ESTADO_SUSPENDIDO = 'suspendido';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'suspendido_hasta' => 'datetime',
        ];
    }

    public function esSuperAdmin(): bool
    {
        return $this->rol === self::ROL_SUPER_ADMIN;
    }

    public function esAdmin(): bool
    {
        return $this->rol === self::ROL_ADMIN;
    }

    public function esStaff(): bool
    {
        return in_array($this->rol, [self::ROL_SUPER_ADMIN, self::ROL_ADMIN], true);
    }

    public function esCliente(): bool
    {
        return $this->rol === self::ROL_CLIENTE;
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        $initials = Str::initials($this->name, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }

    public function suscripciones()
    {
        return $this->hasMany(Suscripcion::class);
    }

    public function suscripcionActiva()
    {
        return $this->hasOne(Suscripcion::class)
            ->where('estado', 'activa')
            ->whereDate('f_finalizacion', '>=', today())
            ->latest('f_inicio');
    }

    public function lecturas()
    {
        return $this->hasMany(Lectura::class);
    }

    public function resenas()
    {
        return $this->hasMany(Resena::class);
    }

    public function prestamos()
    {
        return $this->hasMany(Prestamo::class);
    }

    public function pagos()
    {
        return $this->hasMany(Pago::class);
    }

    public function favoritos()
    {
        return $this->hasMany(Favorito::class);
    }

    public function estaSuspendido(): bool
    {
        if ($this->estado !== self::ESTADO_SUSPENDIDO) {
            return false;
        }

        return $this->suspendido_hasta === null || $this->suspendido_hasta->gt(now());
    }

    public function sincronizarEstado(): void
    {
        if ($this->estado === self::ESTADO_SUSPENDIDO
            && $this->suspendido_hasta !== null
            && $this->suspendido_hasta->lte(now())) {
            $this->update([
                'estado' => self::ESTADO_ACTIVO,
                'suspendido_hasta' => null,
            ]);
        }
    }

    public function suspenderHasta(CarbonInterface $fecha): void
    {
        $this->update([
            'estado' => self::ESTADO_SUSPENDIDO,
            'suspendido_hasta' => $fecha->toDateTimeString(),
        ]);
    }

    public function activar(): void
    {
        $this->update([
            'estado' => self::ESTADO_ACTIVO,
            'suspendido_hasta' => null,
        ]);
    }

    public function bloqueoPorPenalizacion(): ?CarbonInterface
    {
        $bloqueos = $this->prestamos()
            ->where('dias_penalizacion', '>', 0)
            ->whereNotNull('f_devolucion')
            ->get()
            ->map(fn (Prestamo $prestamo): CarbonInterface => $prestamo->f_devolucion->copy()->addDays($prestamo->dias_penalizacion))
            ->filter(fn (CarbonInterface $fecha): bool => $fecha->gt(today()));

        return $bloqueos->sortDesc()->first();
    }

    public function tieneAccesoLimitadoPorPrestamos(): bool
    {
        return $this->prestamos()->vencidos()->exists()
            || $this->bloqueoPorPenalizacion() !== null;
    }

    public function motivoAccesoLimitado(): string
    {
        $hasta = $this->bloqueoPorPenalizacion();

        if ($hasta !== null) {
            return 'Tienes una penalización activa hasta el '.$hasta->format('d/m/Y').'.';
        }

        return 'Tienes préstamos vencidos. Devuélvelos para recuperar el acceso.';
    }
}
