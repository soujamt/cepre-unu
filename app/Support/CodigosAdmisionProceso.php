<?php

namespace App\Support;

use App\Enums\EstadoCatalogo;
use App\Models\Inscripcion;
use App\Models\Proceso;
use App\Models\ProcesoCarrera;
use App\Models\ProcesoCodigoAdmision;
use App\Models\ProcesoModalidad;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Códigos de Admisión por proceso: arma las combinaciones de la oferta, lee la
 * plantilla llena y los resuelve para la exportación de inscritos.
 */
class CodigosAdmisionProceso
{
    public const TIPO_LUGAR = 'LUGAR';

    public const TIPO_OFERTA = 'OFERTA';

    /**
     * @var list<string>
     */
    public const ENCABEZADOS = ['CLAVE', 'TIPO', 'SEDE', 'MODALIDAD', 'CARRERA', 'CODIGO_ADMISION'];

    /**
     * Cada lugar (sede + modalidad) seguido de sus ofertas (carreras), con el
     * código guardado si lo hay.
     *
     * @return Collection<int, array{clave:string,tipo:string,id_sede:int,sede:string,id_modalidad:int,modalidad:string,id_carrera:?int,carrera:?string,codigo:string}>
     */
    public function combinaciones(Proceso $proceso): Collection
    {
        $modalidades = ProcesoModalidad::query()
            ->with('modalidadAdmision')
            ->where('id_proceso', $proceso->id_pro)
            ->where('estado_pmo', EstadoCatalogo::Habilitado->value)
            ->get()
            ->map->modalidadAdmision
            ->filter()
            ->sortBy('nombre_mad')
            ->values();

        $carrerasPorSede = ProcesoCarrera::query()
            ->with(['sede', 'carrera'])
            ->where('id_proceso', $proceso->id_pro)
            ->where('estado_pca', EstadoCatalogo::Habilitado->value)
            ->get()
            ->filter(fn (ProcesoCarrera $procesoCarrera): bool => $procesoCarrera->sede !== null && $procesoCarrera->carrera !== null)
            ->groupBy('id_sede')
            ->sortBy(fn (Collection $grupo) => $grupo->first()->sede->nombre_sed);

        $codigos = $this->codigosGuardados($proceso);
        $filas = collect();

        foreach ($carrerasPorSede as $grupo) {
            $sede = $grupo->first()->sede;
            $carreras = $grupo->map->carrera->sortBy('nombre_car');

            foreach ($modalidades as $modalidad) {
                $claveLugar = ProcesoCodigoAdmision::clave($sede->id_sed, $modalidad->id_mad);
                $filas->push([
                    'clave' => $claveLugar,
                    'tipo' => self::TIPO_LUGAR,
                    'id_sede' => $sede->id_sed,
                    'sede' => $sede->nombre_sed,
                    'id_modalidad' => $modalidad->id_mad,
                    'modalidad' => $modalidad->nombre_mad,
                    'id_carrera' => null,
                    'carrera' => null,
                    'codigo' => $codigos[$claveLugar] ?? '',
                ]);

                foreach ($carreras as $carrera) {
                    $claveOferta = ProcesoCodigoAdmision::clave($sede->id_sed, $modalidad->id_mad, $carrera->id_car);
                    $filas->push([
                        'clave' => $claveOferta,
                        'tipo' => self::TIPO_OFERTA,
                        'id_sede' => $sede->id_sed,
                        'sede' => $sede->nombre_sed,
                        'id_modalidad' => $modalidad->id_mad,
                        'modalidad' => $modalidad->nombre_mad,
                        'id_carrera' => $carrera->id_car,
                        'carrera' => $carrera->nombre_car,
                        'codigo' => $codigos[$claveOferta] ?? '',
                    ]);
                }
            }
        }

        return $filas;
    }

    /**
     * Guarda los códigos por clave; un código vacío borra el que había.
     *
     * @param  array<string, string|null>  $codigosPorClave
     */
    public function guardar(Proceso $proceso, array $codigosPorClave): int
    {
        $combinaciones = $this->combinaciones($proceso)->keyBy('clave');
        $guardados = 0;

        DB::transaction(function () use ($proceso, $codigosPorClave, $combinaciones, &$guardados): void {
            foreach ($codigosPorClave as $clave => $codigo) {
                $combinacion = $combinaciones->get((string) $clave);

                if ($combinacion === null) {
                    continue;
                }

                $codigo = mb_strtoupper(trim((string) $codigo), 'UTF-8');
                $registro = ProcesoCodigoAdmision::withTrashed()
                    ->where('id_proceso', $proceso->id_pro)
                    ->where('id_sede', $combinacion['id_sede'])
                    ->where('id_modalidad_admision', $combinacion['id_modalidad'])
                    ->where('id_carrera', $combinacion['id_carrera'])
                    ->first();

                if ($codigo === '') {
                    $registro?->forceDelete();

                    continue;
                }

                $registro ??= new ProcesoCodigoAdmision([
                    'id_proceso' => $proceso->id_pro,
                    'id_sede' => $combinacion['id_sede'],
                    'id_modalidad_admision' => $combinacion['id_modalidad'],
                    'id_carrera' => $combinacion['id_carrera'],
                ]);

                if ($registro->trashed()) {
                    $registro->restore();
                }

                $registro->codigo_pcd = $codigo;
                $registro->save();
                $guardados++;
            }
        });

        return $guardados;
    }

    /**
     * Lee la plantilla llena por Admisión. Solo toma filas cuya clave existe
     * en el proceso; las demás se informan como errores.
     *
     * @return array{codigos: array<string, string>, errores: list<string>}
     */
    public function leerPlantilla(string $ruta, Proceso $proceso): array
    {
        $filas = IOFactory::load($ruta)->getActiveSheet()->toArray(null, true, false, false);
        $encabezado = array_map(fn (mixed $valor): string => mb_strtoupper(trim((string) $valor), 'UTF-8'), $filas[0] ?? []);
        $columnaClave = array_search('CLAVE', $encabezado, true);
        $columnaCodigo = array_search('CODIGO_ADMISION', $encabezado, true);

        if ($columnaClave === false || $columnaCodigo === false) {
            return ['codigos' => [], 'errores' => ['El archivo no tiene las columnas CLAVE y CODIGO_ADMISION. Use la plantilla descargada.']];
        }

        $claves = $this->combinaciones($proceso)->pluck('clave')->flip();
        $codigos = [];
        $errores = [];

        foreach (array_slice($filas, 1) as $indice => $fila) {
            $clave = trim((string) ($fila[$columnaClave] ?? ''));
            $codigo = trim((string) ($fila[$columnaCodigo] ?? ''));

            if ($clave === '' && $codigo === '') {
                continue;
            }

            if (! $claves->has($clave)) {
                $errores[] = 'Fila '.($indice + 2).": la clave \"{$clave}\" no corresponde a la oferta de este proceso.";

                continue;
            }

            if (mb_strlen($codigo) > 20) {
                $errores[] = 'Fila '.($indice + 2).': el código no debe superar 20 caracteres.';

                continue;
            }

            $codigos[$clave] = $codigo;
        }

        return ['codigos' => $codigos, 'errores' => $errores];
    }

    /**
     * Códigos guardados de los procesos de las inscripciones, por
     * "id_proceso|clave", para la exportación.
     *
     * @param  Collection<int, Inscripcion>  $inscripciones
     * @return array<string, string>
     */
    public function mapaParaInscripciones(Collection $inscripciones): array
    {
        $idsProceso = $inscripciones->pluck('id_proceso')->filter()->unique()->values();

        if ($idsProceso->isEmpty()) {
            return [];
        }

        return ProcesoCodigoAdmision::query()
            ->whereIn('id_proceso', $idsProceso)
            ->get()
            ->mapWithKeys(fn (ProcesoCodigoAdmision $codigo): array => [
                $codigo->id_proceso.'|'.ProcesoCodigoAdmision::clave($codigo->id_sede, $codigo->id_modalidad_admision, $codigo->id_carrera) => $codigo->codigo_pcd,
            ])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private function codigosGuardados(Proceso $proceso): array
    {
        return ProcesoCodigoAdmision::query()
            ->where('id_proceso', $proceso->id_pro)
            ->get()
            ->mapWithKeys(fn (ProcesoCodigoAdmision $codigo): array => [
                ProcesoCodigoAdmision::clave($codigo->id_sede, $codigo->id_modalidad_admision, $codigo->id_carrera) => $codigo->codigo_pcd,
            ])
            ->all();
    }
}
