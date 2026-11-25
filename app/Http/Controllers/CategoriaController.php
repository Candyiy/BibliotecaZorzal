<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CategoriaController extends Controller
{
    public function store(Request $request)
    {
        $datos = Validator::make($request->all(), [
            'nombre' => ['required', 'string', 'max:255', 'regex:/^[^0-9]*$/', 'unique:categorias,nombre'],
        ], [
            'required' => 'El campo :attribute es obligatorio.',
            'string' => 'El campo :attribute debe ser texto.',
            'max' => 'El campo :attribute no debe superar los :max caracteres.',
            'regex' => 'El campo :attribute no debe contener números.',
            'unique' => 'Esta categoría ya existe.',
        ], [
            'nombre' => 'categoría',
        ])->validate();

        Categoria::create($datos);

        return redirect()->route('libros.index')->with('status', 'Categoría agregada correctamente.');
    }
}
