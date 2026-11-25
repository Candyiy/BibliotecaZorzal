<?php

namespace App\Http\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait FiltraPorBusqueda
{
    protected function terminoDeBusqueda(Request $request): string
    {
        return trim((string) $request->query('q', ''));
    }

    /**
     * @param  array<int, string>  $columnas
     * @param  array<string, array<int, string>>  $relaciones
     */
    protected function filtrar(Builder $consulta, string $termino, array $columnas, array $relaciones = []): Builder
    {
        if ($termino === '') {
            return $consulta;
        }

        $patron = '%'.$termino.'%';

        return $consulta->where(function (Builder $consulta) use ($patron, $columnas, $relaciones): void {
            foreach ($columnas as $columna) {
                $consulta->orWhere($columna, 'like', $patron);
            }

            foreach ($relaciones as $relacion => $columnasRelacion) {
                $consulta->orWhereHas($relacion, function (Builder $consultaRelacion) use ($patron, $columnasRelacion): void {
                    $consultaRelacion->where(function (Builder $consultaRelacion) use ($patron, $columnasRelacion): void {
                        foreach ($columnasRelacion as $columna) {
                            $consultaRelacion->orWhere($columna, 'like', $patron);
                        }
                    });
                });
            }
        });
    }
}
