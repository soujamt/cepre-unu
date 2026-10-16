<?php

namespace Database\Seeders;

use App\Models\Aula;
use App\Models\Colegio;
use App\Models\EstadoCivil;
use App\Models\IdentidadEtnica;
use App\Models\ImportacionBanco;
use App\Models\ImportacionBancoDetalle;
use App\Models\Inscripcion;
use App\Models\InscripcionExpediente;
use App\Models\InscripcionObligacionPago;
use App\Models\InscripcionPago;
use App\Models\LenguaNativa;
use App\Models\Nacionalidad;
use App\Models\Pais;
use App\Models\PermisoAccion;
use App\Models\Persona;
use App\Models\Proceso;
use App\Models\ProcesoAula;
use App\Models\ProcesoOferta;
use App\Models\ReglaPago;
use App\Models\RequisitoExpediente;
use App\Models\Rol;
use App\Models\Semestre;
use App\Models\TipoColegio;
use App\Models\TipoDocumento;
use App\Models\Turno;
use App\Models\Ubigeo;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class InscripcionManualSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            BaseCatalogSeeder::class,
            BaseOfertaCatalogSeeder::class,
            InitialAccessSeeder::class,
            AulaSeeder::class,
            ConfiguracionAcademicaInicialSeeder::class,
        ]);

        [$superAdministrador, $gestorInscripciones] = $this->crearUsuariosManual();
        $this->activarProcesoDemo();

        $proceso = Proceso::query()
            ->where('codigo_pro', 'ordinario_2026_1')
            ->firstOrFail();

        $turno = Turno::query()
            ->where('codigo_tur', 'MAN')
            ->firstOrFail();

        $procesoOfertas = ProcesoOferta::query()
            ->where('id_proceso', $proceso->id_pro)
            ->where('id_turno', $turno->id_tur)
            ->whereHas('ofertaBase.modalidadLugarInscripcion', function ($query): void {
                $query
                    ->where('id_modalidad_admision', 2)
                    ->where('id_lugar_inscripcion', 603);
            })
            ->orderBy('id_pof')
            ->get();

        $reglaPago = ReglaPago::query()
            ->where('id_proceso', $proceso->id_pro)
            ->whereHas('conceptoPago', function ($query): void {
                $query->where('codigo_cpg', 'INS_CUO');
            })
            ->firstOrFail();

        $requisitosExpediente = RequisitoExpediente::query()
            ->with('tipoExpediente')
            ->where('id_proceso', $proceso->id_pro)
            ->where('estado_rex', 'HABILITADO')
            ->orderBy('id_rex')
            ->get();

        $ubigeos = Ubigeo::query()
            ->where('codigo_departamento_ubi', '25')
            ->where('codigo_provincia_ubi', '01')
            ->where('estado_ubi', 'HABILITADO')
            ->orderBy('id_ubi')
            ->get();

        $colegios = Colegio::query()
            ->whereIn('id_ubigeo', $ubigeos->pluck('id_ubi'))
            ->where('estado_col', 'HABILITADO')
            ->orderBy('id_col')
            ->get()
            ->groupBy('id_ubigeo');

        $this->asegurarAulasProceso($proceso, $turno);

        $tipoDocumento = TipoDocumento::query()
            ->where('longitud_minima_tdo', 8)
            ->where('longitud_maxima_tdo', 8)
            ->firstOrFail();

        $estadoCivil = EstadoCivil::query()->orderBy('id_eci')->firstOrFail();
        $pais = Pais::query()->where('codigo_pai', '1')->firstOrFail();
        $nacionalidad = Nacionalidad::query()->orderBy('id_nac')->firstOrFail();
        $identidadEtnica = IdentidadEtnica::query()->orderBy('id_iet')->firstOrFail();
        $lenguaNativa = LenguaNativa::query()->orderBy('id_lna')->firstOrFail();
        $tipoColegio = TipoColegio::query()->orderBy('id_tco')->firstOrFail();

        $importacion = ImportacionBanco::query()->updateOrCreate(
            ['hash_archivo_iba' => 'manual-demo-inscripciones-2026'],
            [
                'nombre_archivo_iba' => 'MANUAL_DEMO_INSCRIPCIONES.TXT',
                'moneda_iba' => 'PEN',
                'fecha_importacion_iba' => now(),
                'fecha_lote_iba' => today(),
                'total_registros_iba' => 50,
                'total_registros_reportado_iba' => 50,
                'total_monto_iba' => round((float) $reglaPago->monto_total_rpa * 50, 2),
                'total_monto_reportado_iba' => round((float) $reglaPago->monto_total_rpa * 50, 2),
                'estado_iba' => 'PROCESADO',
                'observacion_iba' => 'Lote demo para el manual de usuario CEPRE.',
            ],
        );

        foreach (range(1, 50) as $indice) {
            $ubigeo = $ubigeos->values()->get(($indice - 1) % $ubigeos->count());
            $colegio = $this->resolverColegio($colegios, $ubigeo?->id_ubi);
            $procesoOferta = $procesoOfertas->values()->get(($indice - 1) % $procesoOfertas->count());
            $numeroDocumento = (string) (76000000 + $indice);
            $codigoInscripcion = sprintf('MANUAL-INS-%03d', $indice);

            if ($ubigeo === null || $colegio === null || $procesoOferta === null) {
                continue;
            }

            $persona = Persona::query()->updateOrCreate(
                ['numero_documento_per' => $numeroDocumento],
                [
                    'id_tipo_documento' => $tipoDocumento->id_tdo,
                    'solo_un_apellido_per' => false,
                    'apellido_paterno_per' => sprintf('UCAYALI%02d', $indice),
                    'apellido_materno_per' => sprintf('PORTILLO%02d', $indice),
                    'apellido_casada_per' => null,
                    'nombres_per' => sprintf('POSTULANTE DEMO %02d', $indice),
                    'fecha_nacimiento_per' => now()->subYears(18 + ($indice % 5))->subDays($indice)->toDateString(),
                    'sexo_per' => $indice % 2 === 0 ? 'FEMENINO' : 'MASCULINO',
                    'id_estado_civil' => $estadoCivil->id_eci,
                    'id_pais_nacimiento' => $pais->id_pai,
                    'id_nacionalidad' => $nacionalidad->id_nac,
                    'id_ubigeo_nacimiento' => $ubigeo->id_ubi,
                    'id_ubigeo_domicilio' => $ubigeo->id_ubi,
                    'direccion_per' => sprintf('JR. MANUAL CEPRE %03d', $indice),
                    'celular_per' => '900'.str_pad((string) $indice, 6, '0', STR_PAD_LEFT),
                    'telefono_per' => '061'.str_pad((string) $indice, 6, '0', STR_PAD_LEFT),
                    'correo_electronico_per' => sprintf('manual.demo.%02d@cepre.test', $indice),
                    'discapacidad_per' => false,
                    'id_tipo_discapacidad' => null,
                    'tipo_lengua_materna_per' => 'NATIVA',
                    'id_identidad_etnica' => $identidadEtnica->id_iet,
                    'id_lengua_nativa' => $lenguaNativa->id_lna,
                    'id_lengua_extranjera' => null,
                    'observacion_per' => 'REGISTRO DEMO PARA MANUAL DE USUARIO',
                    'estado_per' => 'HABILITADO',
                ],
            );

            $inscripcion = Inscripcion::query()->updateOrCreate(
                ['codigo_ins' => $codigoInscripcion],
                [
                    'id_persona' => $persona->id_per,
                    'id_proceso_oferta' => $procesoOferta->id_pof,
                    'id_convenio_proceso' => null,
                    'fecha_inscripcion_ins' => now()->subDays(50 - $indice),
                    'estado_ins' => 'ACTIVO',
                    'anio_graduacion_ins' => (int) now()->subYears(1 + ($indice % 3))->format('Y'),
                    'id_pais_colegio' => $pais->id_pai,
                    'id_colegio' => $colegio->id_col,
                    'nombre_colegio_extranjero_ins' => null,
                    'id_tipo_colegio' => $tipoColegio->id_tco,
                    'veces_postula_unu_ins' => $indice % 3,
                    'veces_postula_otras_ins' => $indice % 2,
                    'observacion_ins' => 'INSCRIPCION DEMO COMPLETA PARA MANUAL',
                ],
            );

            $this->asignarAula($inscripcion, $proceso, $turno, $indice);

            $obligacion = InscripcionObligacionPago::query()->updateOrCreate(
                [
                    'id_inscripcion' => $inscripcion->id_ins,
                    'id_regla_pago' => $reglaPago->id_rpa,
                ],
                [
                    'monto_total_iop' => $reglaPago->monto_total_rpa,
                    'monto_pagado_iop' => $reglaPago->monto_total_rpa,
                    'saldo_iop' => 0,
                    'numero_cuotas_iop' => 1,
                    'estado_iop' => 'PAGADO',
                ],
            );

            $detalle = ImportacionBancoDetalle::query()->updateOrCreate(
                ['numero_operacion_ibd' => sprintf('MANUALOP%06d', $indice)],
                [
                    'id_importacion_banco' => $importacion->id_iba,
                    'numero_documento_ibd' => $numeroDocumento,
                    'nombre_depositante_ibd' => trim($persona->apellido_paterno_per.' '.$persona->apellido_materno_per.' '.$persona->nombres_per),
                    'numero_operacion_raw_ibd' => sprintf('81706%04d', $indice),
                    'numero_operacion_visible_ibd' => sprintf('%05d', 41000 + $indice),
                    'codigo_canal_ibd' => 'Y%G',
                    'codigo_oficina_ibd' => '01068',
                    'fecha_pago_ibd' => now()->subDays(50 - $indice)->toDateString(),
                    'monto_ibd' => $reglaPago->monto_total_rpa,
                    'texto_linea_ibd' => 'REGISTRO DEMO MANUAL DE USUARIO',
                    'estado_ibd' => 'ASOCIADO',
                ],
            );

            InscripcionPago::query()->updateOrCreate(
                ['id_importacion_banco_detalle' => $detalle->id_ibd],
                [
                    'id_inscripcion_obligacion_pago' => $obligacion->id_iop,
                    'numero_cuota_ipa' => 1,
                    'monto_ipa' => $reglaPago->monto_total_rpa,
                    'fecha_pago_ipa' => $detalle->fecha_pago_ibd,
                    'numero_operacion_ipa' => $detalle->numero_operacion_visible_ibd,
                    'voucher_url_ipa' => null,
                    'estado_validacion_ipa' => 'VERIFICADO',
                    'observacion_validacion_ipa' => 'PAGO DEMO VALIDADO PARA MANUAL',
                    'validado_en' => now(),
                    'id_usuario_validador' => $gestorInscripciones->id_usu,
                ],
            );

            foreach ($requisitosExpediente as $requisito) {
                $rutaArchivo = sprintf(
                    'demo/manual-expedientes/%s-%s.txt',
                    strtolower($codigoInscripcion),
                    $requisito->id_tipo_expediente,
                );

                Storage::disk('public')->put(
                    $rutaArchivo,
                    "Archivo demo para {$codigoInscripcion} - {$requisito->tipoExpediente?->nombre_tex}",
                );

                InscripcionExpediente::query()->updateOrCreate(
                    [
                        'id_inscripcion' => $inscripcion->id_ins,
                        'id_tipo_expediente' => $requisito->id_tipo_expediente,
                    ],
                    [
                        'archivo_url_iex' => $rutaArchivo,
                        'nombre_archivo_iex' => sprintf(
                            '%s %s.txt',
                            $codigoInscripcion,
                            str($requisito->tipoExpediente?->nombre_tex ?? 'EXPEDIENTE')->slug('_'),
                        ),
                        'estado_revision_iex' => 'APROBADO',
                        'observacion_iex' => 'EXPEDIENTE APROBADO EN DATOS DEMO',
                        'revisado_en' => now(),
                        'id_usuario_revisor' => $superAdministrador->id_usu,
                    ],
                );
            }
        }
    }

    /**
     * @return array{0: Usuario, 1: Usuario}
     */
    private function crearUsuariosManual(): array
    {
        $superAdministrador = Usuario::query()
            ->where('usuario_usu', 'superadmin')
            ->firstOrFail();

        $rolGestor = Rol::query()->updateOrCreate(
            ['nombre_rol' => 'GESTOR DE INSCRIPCIONES CEPRE'],
            [
                'descripcion_rol' => 'Rol operativo para gestionar inscripciones, pagos y reportes CEPRE.',
                'estado_rol' => 'HABILITADO',
            ],
        );

        $permisos = PermisoAccion::query()
            ->whereIn('clave_peracc', [
                'inscripcion_indice',
                'inscripcion_crear',
                'inscripcion_editar',
                'inscripcion_estado',
                'inscripcion_exportar_excel',
                'pago_indice',
                'pago_crear',
                'pago_editar',
                'pago_estado',
                'reporte_inscripcion_indice',
                'reporte_inscripcion_exportar_excel',
            ])
            ->pluck('id_peracc');

        $rolGestor->permisoAcciones()->sync($permisos);

        $gestorInscripciones = Usuario::query()->updateOrCreate(
            ['usuario_usu' => 'gestor.cepre'],
            [
                'correo_usu' => 'gestor.cepre@unu.edu.pe',
                'nombres_usu' => 'GESTOR INSCRIPCIONES CEPRE',
                'clave_usu' => 'password',
                'estado_usu' => 'HABILITADO',
            ],
        );

        $gestorInscripciones->roles()->syncWithoutDetaching([$rolGestor->id_rol]);

        return [$superAdministrador, $gestorInscripciones];
    }

    private function activarProcesoDemo(): void
    {
        $semestre = Semestre::query()
            ->where('codigo_sem', '2026_1')
            ->firstOrFail();

        $proceso = Proceso::query()
            ->where('codigo_pro', 'ordinario_2026_1')
            ->firstOrFail();

        $semestre->update([
            'fecha_inicio_inscripcion_sem' => today()->subDays(15),
            'fecha_fin_inscripcion_sem' => today()->addDays(30),
            'estado_sem' => 'HABILITADO',
        ]);

        $proceso->update([
            'fecha_inicio_pro' => today()->subDays(15),
            'fecha_fin_pro' => today()->addDays(30),
            'estado_pro' => 'HABILITADO',
        ]);
    }

    private function asegurarAulasProceso(Proceso $proceso, Turno $turno): void
    {
        $aulas = Aula::query()
            ->where('estado_aul', 'HABILITADO')
            ->orderBy('orden_aul')
            ->take(3)
            ->get();

        foreach ($aulas as $aula) {
            ProcesoAula::query()->updateOrCreate(
                [
                    'id_proceso' => $proceso->id_pro,
                    'id_turno' => $turno->id_tur,
                    'id_aula' => $aula->id_aul,
                ],
                [
                    'capacidad_pau' => 30,
                    'estado_pau' => 'HABILITADO',
                ],
            );
        }
    }

    private function asignarAula(Inscripcion $inscripcion, Proceso $proceso, Turno $turno, int $indice): void
    {
        $aulasProceso = ProcesoAula::query()
            ->where('id_proceso', $proceso->id_pro)
            ->where('id_turno', $turno->id_tur)
            ->where('estado_pau', 'HABILITADO')
            ->orderBy('id_pau')
            ->get();

        $aulaProceso = $aulasProceso->values()->get(($indice - 1) % $aulasProceso->count());

        if ($aulaProceso === null) {
            return;
        }

        $inscripcion->update([
            'id_proceso_aula' => $aulaProceso->id_pau,
        ]);
    }

    /**
     * @param  Collection<string, Collection<int, Colegio>>  $colegios
     */
    private function resolverColegio(Collection $colegios, string $ubigeoId): ?Colegio
    {
        $colegiosUbigeo = $colegios->get($ubigeoId);

        if ($colegiosUbigeo instanceof Collection && $colegiosUbigeo->isNotEmpty()) {
            return $colegiosUbigeo->first();
        }

        /** @var ?Colegio $colegio */
        $colegio = $colegios->flatten()->first();

        return $colegio;
    }
}
