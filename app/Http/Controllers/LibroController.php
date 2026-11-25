<?php

namespace App\Http\Controllers;

use App\Http\Concerns\FiltraPorBusqueda;
use App\Models\Autor;
use App\Models\Categoria;
use App\Models\Libro;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class LibroController extends Controller
{
    use FiltraPorBusqueda;

    public function index(Request $request)
    {
        $termino = $this->terminoDeBusqueda($request);
        $filtros = $this->filtrosDelCatalogo($request);

        $consulta = $this->filtrar(
            Libro::query(),
            $termino,
            ['titulo', 'descripcion'],
            ['autores' => ['nombre'], 'categorias' => ['nombre']],
        );

        if ($filtros['categoria_id'] !== null) {
            $consulta->whereHas('categorias', fn ($consultaCategoria) => $consultaCategoria->whereKey($filtros['categoria_id']));
        }

        if ($filtros['autor_id'] !== null) {
            $consulta->whereHas('autores', fn ($consultaAutor) => $consultaAutor->whereKey($filtros['autor_id']));
        }

        if ($filtros['anio'] !== null) {
            $consulta->whereYear('fecha_publicacion', $filtros['anio']);
        }

        if ($filtros['disponibilidad'] === 'con_pdf') {
            $consulta->whereNotNull('archivo_pdf');
        } elseif ($filtros['disponibilidad'] === 'sin_pdf') {
            $consulta->whereNull('archivo_pdf');
        }

        $libros = $consulta->with(['autores.libros', 'categorias', 'resenas.user', 'favoritos', 'licencias'])
            ->withAvg('resenas', 'puntaje')
            ->withCount('resenas')
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $autores = Autor::orderBy('nombre')->get();
        $categorias = Categoria::orderBy('nombre')->get();

        $anios = Libro::whereNotNull('fecha_publicacion')
            ->get()
            ->pluck('fecha_publicacion')
            ->map(fn ($fecha) => $fecha->year)
            ->unique()
            ->sortDesc()
            ->values();

        $opcionesFiltros = [
            [
                'name' => 'categoria',
                'label' => 'Categoría',
                'placeholder' => 'Todas las categorías',
                'options' => $categorias->mapWithKeys(fn (Categoria $categoria) => [(string) $categoria->id => $categoria->nombre])->all(),
            ],
            [
                'name' => 'autor',
                'label' => 'Autor',
                'placeholder' => 'Todos los autores',
                'options' => $autores->mapWithKeys(fn (Autor $autor) => [(string) $autor->id => $autor->nombre])->all(),
            ],
            [
                'name' => 'anio',
                'label' => 'Año',
                'placeholder' => 'Todos los años',
                'options' => $anios->mapWithKeys(fn ($anio) => [(string) $anio => (string) $anio])->all(),
            ],
            [
                'name' => 'disponibilidad',
                'label' => 'Disponibilidad',
                'placeholder' => 'Todas',
                'options' => [
                    'con_pdf' => 'Con PDF',
                    'sin_pdf' => 'Sin PDF',
                ],
            ],
        ];

        return view('libros.index', compact(
            'libros',
            'autores',
            'categorias',
            'opcionesFiltros',
        ));
    }

    public function store(Request $request)
    {
        $datos = $this->validar($request);
        $autorId = $datos['autor_id'] ?? null;
        $categoriaIds = $datos['categorias'] ?? [];
        unset($datos['autor_id'], $datos['categorias']);

        $datos['archivo_pdf'] = $request->file('archivo_pdf')->store('libros', 'local');

        $libro = Libro::create($datos);
        $this->sincronizarAutor($libro, $autorId);
        $this->sincronizarCategorias($libro, $categoriaIds);

        return redirect()->route('libros.index')->with('status', 'Libro agregado correctamente.');
    }

    public function update(Request $request, Libro $libro)
    {
        $datos = $this->validar($request, true);
        $autorId = $datos['autor_id'] ?? null;
        $categoriaIds = $datos['categorias'] ?? [];
        unset($datos['autor_id'], $datos['categorias']);
        $archivoAnterior = $libro->archivo_pdf;

        if ($request->hasFile('archivo_pdf')) {
            $datos['archivo_pdf'] = $request->file('archivo_pdf')->store('libros', 'local');
        }

        $libro->update($datos);
        $this->sincronizarAutor($libro, $autorId);
        $this->sincronizarCategorias($libro, $categoriaIds);

        if (isset($datos['archivo_pdf']) && $archivoAnterior) {
            Storage::disk('local')->delete($archivoAnterior);
        }

        return redirect()->route('libros.index')->with('status', 'Libro actualizado correctamente.');
    }

    public function destroy(Libro $libro)
    {
        if ($libro->archivo_pdf) {
            Storage::disk('local')->delete($libro->archivo_pdf);
        }

        $libro->delete();

        return redirect()->route('libros.index')->with('status', 'Libro eliminado correctamente.');
    }

    public function leer(Request $request, Libro $libro)
    {
        $this->asegurarAcceso($request, $libro);
        $this->asegurarPdf($libro);

        $request->user()->lecturas()->firstOrCreate(['libro_id' => $libro->id])->touch();

        $respuesta = response()->file(
            Storage::disk('local')->path($libro->archivo_pdf),
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$this->nombrePdf($libro).'"',
            ]
        );

        return $this->sinCache($respuesta);
    }

    public function descargar(Request $request, Libro $libro)
    {
        $this->asegurarAcceso($request, $libro);
        $this->asegurarPdf($libro);

        $respuesta = response()->download(
            Storage::disk('local')->path($libro->archivo_pdf),
            $this->nombrePdf($libro)
        );

        return $this->sinCache($respuesta);
    }

    private function validar(Request $request, bool $archivoOpcional = false): array
    {
        $mensajes = [
            'required' => 'El campo :attribute es obligatorio.',
            'date' => 'El campo :attribute debe ser una fecha válida.',
            'titulo.max' => 'El título no debe superar los 255 caracteres.',
            'titulo.regex' => 'El título no debe contener números.',
            'descripcion.regex' => 'La descripción no debe contener números.',
            'archivo_pdf.file' => 'El archivo PDF no es válido.',
            'archivo_pdf.uploaded' => 'El archivo PDF no se pudo subir.',
            'archivo_pdf.mimes' => 'El archivo debe ser un PDF.',
            'archivo_pdf.max' => 'El PDF no debe superar los 20 MB.',
            'autor_id.exists' => 'El autor seleccionado no es válido.',
            'categorias.array' => 'Selecciona categorías válidas.',
            'categorias.*.integer' => 'Selecciona categorías válidas.',
            'categorias.*.exists' => 'Una categoría seleccionada no es válida.',
        ];

        $atributos = [
            'titulo' => 'título',
            'descripcion' => 'descripción',
            'fecha_publicacion' => 'fecha de publicación',
            'archivo_pdf' => 'archivo PDF',
            'autor_id' => 'autor',
            'categorias' => 'categorías',
        ];

        $validados = Validator::make($request->except('archivo_pdf'), [
            'titulo' => ['required', 'string', 'max:255', 'regex:/^[^0-9]*$/'],
            'descripcion' => ['required', 'string', 'regex:/^[^0-9]*$/'],
            'fecha_publicacion' => ['required', 'date'],
            'autor_id' => ['nullable', 'exists:autores,id'],
            'categorias' => ['nullable', 'array'],
            'categorias.*' => ['integer', 'exists:categorias,id'],
        ], $mensajes, $atributos)->validate();

        // Un input de archivo vacío llega como UploadedFile inválido; en edición se ignora.
        if ($archivoOpcional && ! $request->hasFile('archivo_pdf')) {
            return $validados;
        }

        return array_merge($validados, Validator::make(
            ['archivo_pdf' => $request->file('archivo_pdf')],
            ['archivo_pdf' => ['required', 'file', 'mimes:pdf', 'max:20480']],
            $mensajes,
            $atributos
        )->validate());
    }

    private function sincronizarAutor(Libro $libro, ?int $autorId): void
    {
        $libro->autores()->sync($autorId ? [$autorId] : []);
    }

    /**
     * @param  array<int, int>  $categoriaIds
     */
    private function sincronizarCategorias(Libro $libro, array $categoriaIds): void
    {
        $libro->categorias()->sync($categoriaIds);
    }

    /**
     * @return array{categoria_id: int|null, autor_id: int|null, anio: int|null, disponibilidad: string|null}
     */
    private function filtrosDelCatalogo(Request $request): array
    {
        $categoriaId = $request->integer('categoria') ?: null;
        $autorId = $request->integer('autor') ?: null;
        $anio = $request->integer('anio') ?: null;
        $disponibilidad = $request->query('disponibilidad');

        if ($categoriaId !== null && ! Categoria::whereKey($categoriaId)->exists()) {
            $categoriaId = null;
        }

        if ($autorId !== null && ! Autor::whereKey($autorId)->exists()) {
            $autorId = null;
        }

        $anioActual = (int) now()->year;

        if ($anio !== null && ($anio < 1000 || $anio > $anioActual)) {
            $anio = null;
        }

        if (! in_array($disponibilidad, ['con_pdf', 'sin_pdf'], true)) {
            $disponibilidad = null;
        }

        return [
            'categoria_id' => $categoriaId,
            'autor_id' => $autorId,
            'anio' => $anio,
            'disponibilidad' => $disponibilidad,
        ];
    }

    private function asegurarAcceso(Request $request, ?Libro $libro = null): void
    {
        $usuario = $request->user();

        if (! $usuario->esCliente()) {
            return;
        }

        $suscripcion = $usuario->suscripcionActiva()->with('plan')->first();

        abort_unless($suscripcion, 403, 'Necesitas una suscripción activa para leer los libros.');

        abort_if(
            $usuario->tieneAccesoLimitadoPorPrestamos(),
            403,
            $usuario->motivoAccesoLimitado()
        );

        if ($libro) {
            abort_unless(
                $suscripcion->plan?->incluyeLibro($libro),
                403,
                'Tu plan no incluye este libro. Revisa la cobertura de tu suscripción.'
            );
        }
    }

    private function asegurarPdf(Libro $libro): void
    {
        abort_unless(
            $libro->archivo_pdf && Storage::disk('local')->exists($libro->archivo_pdf),
            404,
            'Este libro no tiene un PDF disponible.'
        );
    }

    private function nombrePdf(Libro $libro): string
    {
        return Str::slug($libro->titulo).'.pdf';
    }

    private function sinCache($respuesta)
    {
        $respuesta->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0', true);
        $respuesta->headers->set('Pragma', 'no-cache', true);
        $respuesta->headers->set('Expires', '0', true);

        return $respuesta;
    }
}
