<?php

namespace App\Http\Controllers;

use App\Http\Concerns\FiltraPorBusqueda;
use App\Models\Libro;
use App\Models\Pago;
use App\Models\Plan;
use App\Models\Prestamo;
use App\Models\Suscripcion;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReporteController extends Controller
{
    use FiltraPorBusqueda;

    public function index(Request $request)
    {
        $mes = $this->mesSolicitado($request);
        $termino = $this->terminoDeBusqueda($request);
        $meses = collect(range(0, 11))
            ->map(fn (int $i): CarbonInterface => now()->startOfMonth()->subMonths($i)->locale('es'))
            ->mapWithKeys(fn (CarbonInterface $fecha): array => [
                $fecha->format('Y-m') => ucfirst($fecha->translatedFormat('F Y')),
            ]);

        $reportes = collect($this->tarjetasReportes())
            ->filter(function (array $reporte) use ($termino): bool {
                if ($termino === '') {
                    return true;
                }

                $texto = Str::lower($reporte['titulo'].' '.$reporte['copy']);

                return str_contains($texto, Str::lower($termino));
            })
            ->values();

        return view('reportes.index', [
            'mes' => $mes,
            'meses' => $meses,
            'reportes' => $reportes,
        ]);
    }

    public function exportar(Request $request, string $tipo)
    {
        $mes = $this->mesSolicitado($request);
        $formato = $request->query('formato') === 'pdf' ? 'pdf' : 'csv';

        $reportes = $this->reportes();

        abort_unless(isset($reportes[$tipo]), 404, 'El reporte solicitado no existe.');

        $reporte = $reportes[$tipo];
        $filas = $reporte['filas']($mes);
        $nombre = 'zorzel-'.$tipo.'-'.($mes !== '' ? $mes : today()->toDateString());

        if ($formato === 'pdf') {
            return $this->pdf($reporte, $filas, $tipo, $mes, $nombre.'.pdf', $reportes);
        }

        return $this->csv($reporte, $filas, $nombre.'.csv');
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function tarjetasReportes(): array
    {
        return [
            ['clave' => 'libros', 'titulo' => 'Libros', 'copy' => 'Catálogo completo con autores, categorías y disponibilidad de PDF.', 'icono' => 'book-open'],
            ['clave' => 'planes', 'titulo' => 'Planes y suscripciones', 'copy' => 'Planes, cobertura, pruebas, beneficios y suscriptores, junto con el detalle de suscripciones.', 'icono' => 'layers'],
            ['clave' => 'suscripciones', 'titulo' => 'Suscripciones', 'copy' => 'Planes contratados, vigencias y estado de cada suscripción.', 'icono' => 'layers'],
            ['clave' => 'pagos', 'titulo' => 'Pagos', 'copy' => 'Referencia, monto, método y fecha de cada pago registrado.', 'icono' => 'credit-card'],
            ['clave' => 'prestamos', 'titulo' => 'Préstamos', 'copy' => 'Solicitudes, vencimientos, devoluciones y días de penalización.', 'icono' => 'bookmark'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function reportes(): array
    {
        return [
            'libros' => [
                'titulo' => 'Libros',
                'cabeceras' => ['Título', 'Descripción', 'Fecha de publicación', 'Autores', 'Categorías', 'PDF'],
                'columna_fecha' => 'fecha_publicacion',
                'filas' => fn (string $mes): Collection => Libro::with(['autores', 'categorias'])
                    ->when($mes !== '', fn ($consulta) => $this->filtrarMes($consulta, 'fecha_publicacion', $mes))
                    ->orderBy('titulo')
                    ->get()
                    ->map(fn (Libro $libro): array => [
                        $libro->titulo,
                        $libro->descripcion,
                        $libro->fecha_publicacion?->format('Y-m-d'),
                        $libro->autores->pluck('nombre')->join(', '),
                        $libro->categorias->pluck('nombre')->join(', '),
                        $libro->archivo_pdf ? 'Sí' : 'No',
                    ]),
            ],
            'planes' => [
                'titulo' => 'Planes y suscripciones',
                'cabeceras' => ['Plan', 'Tipo', 'Cobertura', 'Alcance', 'Estado', 'Prueba', 'Funcionalidades', 'Suscriptores'],
                'columna_fecha' => 'created_at',
                'filas' => fn (string $mes): Collection => Plan::with(['libro', 'libros'])
                    ->withCount('suscripciones')
                    ->when($mes !== '', fn ($consulta) => $this->filtrarMes($consulta, 'created_at', $mes))
                    ->orderBy('tipo')
                    ->get()
                    ->map(fn (Plan $plan): array => [
                        'Plan '.ucfirst($plan->tipo),
                        $plan->tipo,
                        $plan->modalidadEtiqueta(),
                        $plan->coberturaEtiqueta(),
                        $plan->estado,
                        $plan->tienePrueba() ? $plan->trial_dias.' días' : '—',
                        $plan->beneficiosLista() ? implode('; ', $plan->beneficiosLista()) : '—',
                        $plan->suscripciones_count,
                    ]),
            ],
            'suscripciones' => [
                'titulo' => 'Suscripciones',
                'cabeceras' => ['Usuario', 'Correo', 'Plan', 'Tipo', 'Inicio', 'Finalización', 'Estado'],
                'columna_fecha' => 'f_inicio',
                'filas' => fn (string $mes): Collection => Suscripcion::with(['usuario', 'plan'])
                    ->when($mes !== '', fn ($consulta) => $this->filtrarMes($consulta, 'f_inicio', $mes))
                    ->latest('f_inicio')
                    ->get()
                    ->map(fn (Suscripcion $suscripcion): array => [
                        $suscripcion->usuario?->name,
                        $suscripcion->usuario?->email,
                        $suscripcion->plan ? 'Plan '.ucfirst($suscripcion->plan->tipo) : 'Plan eliminado',
                        $suscripcion->plan?->tipo,
                        $suscripcion->f_inicio?->format('Y-m-d'),
                        $suscripcion->f_finalizacion?->format('Y-m-d'),
                        $suscripcion->estado.($suscripcion->esPrueba() ? ' (prueba)' : ''),
                    ]),
            ],
            'pagos' => [
                'titulo' => 'Pagos',
                'cabeceras' => ['Referencia', 'Usuario', 'Correo', 'Plan', 'Monto', 'Método', 'Estado', 'Fecha'],
                'columna_fecha' => 'f_pago',
                'filas' => fn (string $mes): Collection => Pago::with(['usuario', 'suscripcion.plan'])
                    ->when($mes !== '', fn ($consulta) => $this->filtrarMes($consulta, 'f_pago', $mes))
                    ->latest('f_pago')
                    ->get()
                    ->map(fn (Pago $pago): array => [
                        $pago->referencia,
                        $pago->usuario?->name,
                        $pago->usuario?->email,
                        $pago->suscripcion?->plan ? ucfirst($pago->suscripcion->plan->tipo) : '—',
                        number_format((float) $pago->monto, 2, '.', ''),
                        $pago->metodo,
                        $pago->estado,
                        $pago->f_pago?->format('Y-m-d'),
                    ]),
            ],
            'prestamos' => [
                'titulo' => 'Préstamos',
                'cabeceras' => ['Usuario', 'Correo', 'Libro', 'Inicio', 'Vencimiento', 'Estado', 'Devolución', 'Días de penalización'],
                'columna_fecha' => 'f_inicio',
                'filas' => fn (string $mes): Collection => Prestamo::with(['usuario', 'licencia.libro'])
                    ->when($mes !== '', fn ($consulta) => $this->filtrarMes($consulta, 'f_inicio', $mes))
                    ->latest('f_inicio')
                    ->get()
                    ->map(fn (Prestamo $prestamo): array => [
                        $prestamo->usuario?->name,
                        $prestamo->usuario?->email,
                        $prestamo->libro?->titulo ?? 'Libro eliminado',
                        $prestamo->f_inicio?->format('Y-m-d'),
                        $prestamo->f_finalizacion?->format('Y-m-d'),
                        $prestamo->estado,
                        $prestamo->f_devolucion?->format('Y-m-d'),
                        $prestamo->dias_penalizacion,
                    ]),
            ],
        ];
    }

    private function filtrarMes($consulta, string $columna, string $mes): void
    {
        [$anio, $numero] = explode('-', $mes);

        $consulta->whereYear($columna, (int) $anio)
            ->whereMonth($columna, (int) $numero);
    }

    private function mesSolicitado(Request $request): string
    {
        $mes = trim((string) $request->query('mes', ''));

        return preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $mes) === 1 ? $mes : '';
    }

    private function csv(array $reporte, Collection $filas, string $nombre): StreamedResponse
    {
        return response()->streamDownload(function () use ($reporte, $filas): void {
            $manejador = fopen('php://output', 'w');
            fwrite($manejador, "\xEF\xBB\xBF");
            fputcsv($manejador, $reporte['cabeceras'], ';');

            foreach ($filas as $fila) {
                fputcsv($manejador, array_map(fn ($valor): string => $this->celda($valor), $fila), ';');
            }

            fclose($manejador);
        }, $nombre, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function pdf(array $reporte, Collection $filas, string $tipo, string $mes, string $nombre, array $reportes)
    {
        $secundario = null;

        if ($tipo === 'planes') {
            $secundario = [
                'titulo' => 'Suscripciones del período',
                'cabeceras' => $reportes['suscripciones']['cabeceras'],
                'filas' => $reportes['suscripciones']['filas']($mes),
            ];
        }

        $pdf = Pdf::loadView('reportes.pdf', [
            'titulo' => $reporte['titulo'],
            'cabeceras' => $reporte['cabeceras'],
            'filas' => $filas,
            'mes' => $mes,
            'periodo' => $this->periodoEtiqueta($mes),
            'resumen' => $this->resumen($tipo, $filas, $mes),
            'secundario' => $secundario,
        ])->setPaper('a4', 'landscape');

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$nombre.'"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }

    private function periodoEtiqueta(string $mes): string
    {
        if ($mes === '') {
            return 'Todas las fechas';
        }

        return ucfirst(Carbon::createFromFormat('Y-m', $mes)->locale('es')->translatedFormat('F Y'));
    }

    private function resumen(string $tipo, Collection $filas, string $mes): string
    {
        $detalle = match ($tipo) {
            'pagos' => 'Total registrado: '.number_format($filas->sum(fn (array $fila): float => (float) $fila[4]), 2, ',', '.'),
            'suscripciones' => 'Suscripciones activas: '.$filas->filter(fn (array $fila): bool => str_starts_with((string) $fila[6], 'activa'))->count(),
            'prestamos' => 'Días de penalización: '.$filas->sum(fn (array $fila): int => (int) $fila[7]),
            default => 'Registros listados: '.$filas->count(),
        };

        return 'Periodo: '.$this->periodoEtiqueta($mes).'. '.$detalle.'. Generado el '.now()->format('d/m/Y H:i').'.';
    }

    private function celda(mixed $valor): string
    {
        $texto = trim((string) ($valor ?? ''));

        foreach (['=', '+', '-', '@'] as $prefijoPeligroso) {
            if (str_starts_with($texto, $prefijoPeligroso)) {
                return "'".$texto;
            }
        }

        return $texto;
    }
}
