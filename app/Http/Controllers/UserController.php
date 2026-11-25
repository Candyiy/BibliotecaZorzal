<?php

namespace App\Http\Controllers;

use App\Http\Concerns\FiltraPorBusqueda;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    use FiltraPorBusqueda;

    public function index(Request $request)
    {
        $termino = $this->terminoDeBusqueda($request);

        $consulta = $this->filtrar(User::query(), $termino, ['name', 'email', 'rol']);

        return view('usuarios.index', [
            'usuarios' => $consulta->withCount('lecturas')
                ->with('suscripcionActiva.plan')
                ->latest()
                ->paginate(12)
                ->withQueryString(),
        ]);
    }

    public function store(Request $request)
    {
        $datos = $this->validar($request);
        $rol = $datos['rol'];
        unset($datos['rol']);

        $usuario = new User($datos);
        $usuario->rol = $rol;
        $usuario->save();

        return redirect()->route('usuarios.index')->with('status', 'Usuario agregado correctamente.');
    }

    public function update(Request $request, User $usuario)
    {
        $datos = $this->validar($request, $usuario);

        if (empty($datos['password'])) {
            unset($datos['password']);
        }

        unset($datos['rol']);

        $usuario->update($datos);

        return redirect()->route('usuarios.index')->with('status', 'Usuario actualizado correctamente.');
    }

    public function destroy(Request $request, User $usuario)
    {
        if ($usuario->id === $request->user()->id) {
            return redirect()->route('usuarios.index')
                ->with('status', 'No puedes eliminar tu propio usuario.');
        }

        $usuario->delete();

        return redirect()->route('usuarios.index')->with('status', 'Usuario eliminado correctamente.');
    }

    public function suspender(Request $request, User $usuario)
    {
        abort_if($usuario->id === $request->user()->id, 422, 'No puedes suspender tu propia cuenta.');

        $datos = Validator::make($request->all(), [
            'dias' => ['required', 'integer', 'min:1', 'max:365'],
        ], [
            'dias.required' => 'Indica cuántos días durará la suspensión.',
            'dias.integer' => 'Los días deben ser un número entero.',
            'dias.min' => 'La suspensión mínima es de 1 día.',
            'dias.max' => 'La suspensión máxima es de 365 días.',
        ])->validate();

        $hasta = today()->addDays((int) $datos['dias'])->endOfDay();
        $usuario->suspenderHasta($hasta);

        return redirect()->route('usuarios.index')
            ->with('status', 'Cuenta de '.$usuario->name.' suspendida hasta el '.$hasta->format('d/m/Y H:i').'.');
    }

    public function activar(Request $request, User $usuario)
    {
        abort_if($usuario->id === $request->user()->id, 422, 'No puedes cambiar el estado de tu propia cuenta.');

        $usuario->activar();

        return redirect()->route('usuarios.index')
            ->with('status', 'Cuenta de '.$usuario->name.' reactivada correctamente.');
    }

    private function validar(Request $request, ?User $usuario = null): array
    {
        $reglas = [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($usuario?->id),
            ],
            'password' => [$usuario ? 'nullable' : 'required', 'string', 'min:8'],
        ];

        if (! $usuario) {
            $reglas['rol'] = ['required', Rule::in([User::ROL_ADMIN, User::ROL_CLIENTE])];
        }

        return Validator::make($request->all(), $reglas, [
            'name.required' => 'El campo nombre es obligatorio.',
            'name.max' => 'El nombre no debe superar los 255 caracteres.',
            'email.required' => 'El campo correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico no es válido.',
            'email.max' => 'El correo electrónico no debe superar los 255 caracteres.',
            'email.unique' => 'Este correo electrónico ya está registrado.',
            'password.required' => 'El campo contraseña es obligatorio.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'rol.required' => 'El rol es obligatorio.',
            'rol.in' => 'El rol seleccionado no es válido.',
        ], [
            'name' => 'nombre',
            'email' => 'correo electrónico',
            'password' => 'contraseña',
        ])->validate();
    }
}
