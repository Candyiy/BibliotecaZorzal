<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UsuarioTest extends TestCase
{
    use RefreshDatabase;

    public function test_puede_listar_crear_editar_y_eliminar_un_usuario(): void
    {
        $administrador = User::factory()->create(['name' => 'Administrador']);

        $this->actingAs($administrador)
            ->get(route('usuarios.index'))
            ->assertOk()
            ->assertSee('command="show-modal"', false)
            ->assertSee('commandfor="crear-admin"', false)
            ->assertSee('commandfor="crear-cliente"', false)
            ->assertDontSee('<select', false)
            ->assertSee('Tu cuenta');

        $this->actingAs($administrador)
            ->post(route('usuarios.store'), [
                '_modal' => 'crear-cliente',
                'rol' => User::ROL_CLIENTE,
                'name' => 'Ana García',
                'email' => 'ana@correo.com',
                'password' => 'clave-segura-123',
            ])
            ->assertRedirect(route('usuarios.index'))
            ->assertSessionHasNoErrors();

        $usuario = User::where('email', 'ana@correo.com')->firstOrFail();

        $this->assertSame('Ana García', $usuario->name);
        $this->assertSame(User::ROL_CLIENTE, $usuario->rol);
        $this->assertTrue(Hash::check('clave-segura-123', $usuario->password));

        $this->actingAs($administrador)
            ->get(route('usuarios.index'))
            ->assertOk()
            ->assertSee('commandfor="editar-'.$usuario->id.'"', false)
            ->assertSee('<dialog id="editar-'.$usuario->id.'"', false)
            ->assertSee('commandfor="eliminar-usuario-'.$usuario->id.'"', false);

        $this->actingAs($administrador)
            ->put(route('usuarios.update', $usuario), [
                '_modal' => 'editar-'.$usuario->id,
                'name' => 'Ana García Pérez',
                'email' => 'ana.nueva@correo.com',
                'password' => '',
            ])
            ->assertRedirect(route('usuarios.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame('Ana García Pérez', $usuario->fresh()->name);
        $this->assertSame('ana.nueva@correo.com', $usuario->fresh()->email);
        $this->assertTrue(Hash::check('clave-segura-123', $usuario->fresh()->password));

        $this->actingAs($administrador)
            ->put(route('usuarios.update', $usuario), [
                '_modal' => 'editar-'.$usuario->id,
                'name' => 'Ana García Pérez',
                'email' => 'ana.nueva@correo.com',
                'password' => 'nueva-clave-456',
            ])
            ->assertRedirect(route('usuarios.index'));

        $this->assertTrue(Hash::check('nueva-clave-456', $usuario->fresh()->password));

        $this->actingAs($administrador)
            ->delete(route('usuarios.destroy', $usuario))
            ->assertRedirect(route('usuarios.index'));

        $this->assertDatabaseMissing('users', ['id' => $usuario->id]);
    }

    public function test_valida_el_correo_unico_y_reabre_el_modal(): void
    {
        $administrador = User::factory()->create();
        $otro = User::factory()->create(['email' => 'existente@correo.com']);

        $respuesta = $this->actingAs($administrador)
            ->from(route('usuarios.index'))
            ->followingRedirects()
            ->post(route('usuarios.store'), [
                '_modal' => 'crear-cliente',
                'rol' => User::ROL_CLIENTE,
                'name' => 'Usuario duplicado',
                'email' => 'existente@correo.com',
                'password' => 'clave-segura-123',
            ])
            ->assertOk()
            ->assertSee('Revisa los datos:', false)
            ->assertSee('Este correo electrónico ya está registrado.', false);

        $this->assertMatchesRegularExpression(
            '/<dialog id="crear-cliente"\s+open/',
            $respuesta->getContent()
        );

        $this->actingAs($administrador)
            ->put(route('usuarios.update', $otro), [
                '_modal' => 'editar-'.$otro->id,
                'name' => $otro->name,
                'email' => 'existente@correo.com',
                'password' => '',
            ])
            ->assertRedirect(route('usuarios.index'))
            ->assertSessionHasNoErrors();
    }

    public function test_no_puede_eliminar_su_propio_usuario(): void
    {
        $administrador = User::factory()->create(['name' => 'Administrador']);

        $this->actingAs($administrador)
            ->from(route('usuarios.index'))
            ->followingRedirects()
            ->delete(route('usuarios.destroy', $administrador))
            ->assertOk()
            ->assertSee('No puedes eliminar tu propio usuario.');

        $this->assertDatabaseHas('users', ['id' => $administrador->id]);
    }

    public function test_la_gestion_de_usuarios_requiere_autenticacion(): void
    {
        $usuario = User::factory()->create();

        $this->get(route('usuarios.index'))->assertRedirect(route('login'));
        $this->post(route('usuarios.store'), [
            'rol' => User::ROL_CLIENTE,
            'name' => 'Sin sesión',
            'email' => 'sin@sesion.com',
            'password' => 'clave-segura-123',
        ])->assertRedirect(route('login'));
        $this->delete(route('usuarios.destroy', $usuario))->assertRedirect(route('login'));
    }

    public function test_puede_crear_administradores_y_clientes_con_rol_interno(): void
    {
        $superAdmin = User::factory()->create(['rol' => User::ROL_SUPER_ADMIN]);

        $this->actingAs($superAdmin)
            ->post(route('usuarios.store'), [
                '_modal' => 'crear-admin',
                'rol' => User::ROL_ADMIN,
                'name' => 'Admin Uno',
                'email' => 'admin@correo.com',
                'password' => 'clave-segura-123',
            ])
            ->assertRedirect(route('usuarios.index'))
            ->assertSessionHasNoErrors();

        $this->actingAs($superAdmin)
            ->post(route('usuarios.store'), [
                '_modal' => 'crear-cliente',
                'rol' => User::ROL_CLIENTE,
                'name' => 'Cliente Uno',
                'email' => 'cliente@correo.com',
                'password' => 'clave-segura-123',
            ])
            ->assertRedirect(route('usuarios.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame(User::ROL_ADMIN, User::where('email', 'admin@correo.com')->firstOrFail()->rol);
        $this->assertSame(User::ROL_CLIENTE, User::where('email', 'cliente@correo.com')->firstOrFail()->rol);
    }

    public function test_no_se_puede_crear_un_super_admin_desde_el_formulario(): void
    {
        $superAdmin = User::factory()->create(['rol' => User::ROL_SUPER_ADMIN]);

        $respuesta = $this->actingAs($superAdmin)
            ->from(route('usuarios.index'))
            ->followingRedirects()
            ->post(route('usuarios.store'), [
                '_modal' => 'crear-admin',
                'rol' => User::ROL_SUPER_ADMIN,
                'name' => 'Falso super admin',
                'email' => 'falso@correo.com',
                'password' => 'clave-segura-123',
            ])
            ->assertOk()
            ->assertSee('El rol seleccionado no es válido.', false);

        $this->assertMatchesRegularExpression(
            '/<dialog id="crear-admin"\s+open/',
            $respuesta->getContent()
        );

        $this->assertDatabaseMissing('users', ['email' => 'falso@correo.com']);
    }

    public function test_la_edicion_no_permite_cambiar_el_rol(): void
    {
        $superAdmin = User::factory()->create(['rol' => User::ROL_SUPER_ADMIN]);
        $cliente = User::factory()->create(['rol' => User::ROL_CLIENTE]);

        $this->actingAs($superAdmin)
            ->put(route('usuarios.update', $cliente), [
                '_modal' => 'editar-'.$cliente->id,
                'rol' => User::ROL_ADMIN,
                'name' => 'Cliente editado',
                'email' => $cliente->email,
                'password' => '',
            ])
            ->assertRedirect(route('usuarios.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame('Cliente editado', $cliente->fresh()->name);
        $this->assertSame(User::ROL_CLIENTE, $cliente->fresh()->rol);
    }

    public function test_el_staff_puede_suspender_y_reactivar_una_cuenta(): void
    {
        $staff = User::factory()->admin()->create();
        $cliente = User::factory()->cliente()->create();

        $this->actingAs($staff)
            ->post(route('usuarios.suspender', $cliente), ['dias' => 3])
            ->assertRedirect(route('usuarios.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame(User::ESTADO_SUSPENDIDO, $cliente->fresh()->estado);
        $this->assertTrue($cliente->fresh()->estaSuspendido());

        $this->actingAs($cliente->fresh())
            ->get(route('libros.index'))
            ->assertRedirect(route('cuenta.suspendida'));

        $this->actingAs($cliente->fresh())
            ->get(route('cuenta.suspendida'))
            ->assertOk()
            ->assertSee('suspendida', false);

        $this->actingAs($staff)
            ->post(route('usuarios.activar', $cliente))
            ->assertRedirect(route('usuarios.index'));

        $this->assertSame(User::ESTADO_ACTIVO, $cliente->fresh()->estado);
        $this->assertFalse($cliente->fresh()->estaSuspendido());

        $this->actingAs($cliente->fresh())
            ->get(route('libros.index'))
            ->assertOk();
    }

    public function test_no_se_puede_suspender_la_propia_cuenta_y_la_suspension_expira(): void
    {
        $staff = User::factory()->admin()->create();

        $this->actingAs($staff)
            ->post(route('usuarios.suspender', $staff), ['dias' => 2])
            ->assertStatus(422);

        $this->assertSame(User::ESTADO_ACTIVO, $staff->fresh()->estado);

        $cliente = User::factory()->cliente()->create();
        $cliente->suspenderHasta(now()->subMinute());

        $this->assertFalse($cliente->fresh()->estaSuspendido());

        $this->actingAs($cliente->fresh())
            ->get(route('libros.index'))
            ->assertOk();

        $this->assertSame(User::ESTADO_ACTIVO, $cliente->fresh()->estado);
    }

    public function test_un_usuario_suspendido_no_puede_iniciar_sesion(): void
    {
        $cliente = User::factory()->cliente()->create([
            'email' => 'suspendido@correo.com',
        ]);
        $cliente->suspenderHasta(now()->addDays(2));

        $this->post(route('login.store'), [
            'email' => 'suspendido@correo.com',
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
