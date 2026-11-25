<?php

namespace App\Http\Controllers;

use App\Models\Libro;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ResenaController extends Controller
{
    public function store(Request $request, Libro $libro)
    {
        $datos = Validator::make($request->all(), [
            'comentario' => ['required', 'string', 'max:255', 'regex:/^[^0-9]*$/'],
            'puntaje' => ['required', 'integer', 'between:1,5'],
        ], [
            'comentario.required' => 'El campo comentario es obligatorio.',
            'comentario.string' => 'El comentario debe ser texto.',
            'comentario.max' => 'El comentario no debe superar los 255 caracteres.',
            'comentario.regex' => 'El comentario no debe contener números.',
            'puntaje.required' => 'Selecciona un puntaje.',
            'puntaje.integer' => 'El puntaje debe ser un número entero.',
            'puntaje.between' => 'El puntaje debe estar entre 1 y 5.',
        ])->validate();

        $libro->resenas()->updateOrCreate(
            ['user_id' => $request->user()->id],
            $datos,
        );

        return redirect()->route('libros.index')->with('status', 'Reseña guardada correctamente.');
    }
}
