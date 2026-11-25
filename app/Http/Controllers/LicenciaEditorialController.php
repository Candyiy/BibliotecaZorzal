<?php

namespace App\Http\Controllers;

use App\Models\LicenciaEditorial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class LicenciaEditorialController extends Controller
{
    public function store(Request $request)
    {
        $datos = $this->validar($request);

        if ($request->hasFile('archivo_pdf')) {
            $datos['archivo_pdf'] = $request->file('archivo_pdf')->store('licencias', 'local');
        }

        LicenciaEditorial::create($datos);

        return redirect()->route('licencias.index')->with('status', 'Licencia editorial agregada correctamente.');
    }

    public function update(Request $request, LicenciaEditorial $licenciaEditorial)
    {
        $datos = $this->validar($request);
        $archivoAnterior = $licenciaEditorial->archivo_pdf;

        if ($request->hasFile('archivo_pdf')) {
            $datos['archivo_pdf'] = $request->file('archivo_pdf')->store('licencias', 'local');
        }

        $licenciaEditorial->update($datos);

        if (isset($datos['archivo_pdf']) && $archivoAnterior) {
            Storage::disk('local')->delete($archivoAnterior);
        }

        return redirect()->route('licencias.index')->with('status', 'Licencia editorial actualizada correctamente.');
    }

    public function destroy(LicenciaEditorial $licenciaEditorial)
    {
        if ($licenciaEditorial->archivo_pdf) {
            Storage::disk('local')->delete($licenciaEditorial->archivo_pdf);
        }

        $licenciaEditorial->delete();

        return redirect()->route('licencias.index')->with('status', 'Licencia editorial eliminada correctamente.');
    }

    public function pdf(LicenciaEditorial $licenciaEditorial)
    {
        abort_unless(
            $licenciaEditorial->archivo_pdf && Storage::disk('local')->exists($licenciaEditorial->archivo_pdf),
            404,
            'Esta licencia no tiene un PDF disponible.'
        );

        return response()->file(
            Storage::disk('local')->path($licenciaEditorial->archivo_pdf),
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.Str::slug($licenciaEditorial->nombre).'.pdf"',
            ]
        );
    }

    private function validar(Request $request): array
    {
        $mensajes = [
            'required' => 'El campo :attribute es obligatorio.',
            'date' => 'El campo :attribute debe ser una fecha válida.',
            'f_inicio.after_or_equal' => 'La fecha de inicio no puede ser anterior a la actual.',
            'f_finalizacion.after_or_equal' => 'La fecha de finalización no puede ser anterior a la de inicio.',
            'nombre.max' => 'El nombre no debe superar los 255 caracteres.',
            'archivo_pdf.file' => 'El archivo PDF no es válido.',
            'archivo_pdf.uploaded' => 'El archivo PDF no se pudo subir.',
            'archivo_pdf.mimes' => 'El archivo debe ser un PDF.',
            'archivo_pdf.max' => 'El PDF no debe superar los 20 MB.',
        ];

        $atributos = [
            'nombre' => 'nombre',
            'f_inicio' => 'fecha de inicio',
            'f_finalizacion' => 'fecha de finalización',
            'archivo_pdf' => 'archivo PDF',
        ];

        $validados = Validator::make($request->except('archivo_pdf'), [
            'nombre' => ['required', 'string', 'max:255'],
            'f_inicio' => ['required', 'date', 'after_or_equal:today'],
            'f_finalizacion' => ['required', 'date', 'after_or_equal:f_inicio'],
        ], $mensajes, $atributos)->validate();

        // El PDF es opcional; un input vacío se ignora también al editar.
        if (! $request->hasFile('archivo_pdf')) {
            return $validados;
        }

        return array_merge($validados, Validator::make(
            ['archivo_pdf' => $request->file('archivo_pdf')],
            ['archivo_pdf' => ['required', 'file', 'mimes:pdf', 'max:20480']],
            $mensajes,
            $atributos
        )->validate());
    }
}
