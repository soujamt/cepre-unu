<?php

namespace App\Console\Commands;

use App\Enums\EstadoImportacionBancoDetalle;
use App\Enums\EstadoInscripcion;
use App\Enums\EstadoObligacionPago;
use App\Enums\TipoLenguaMaternaPersona;
use App\Models\Aula;
use App\Models\Colegio;
use App\Models\EstadoCivil;
use App\Models\FotoPostulante;
use App\Models\IdentidadEtnica;
use App\Models\ImportacionBanco;
use App\Models\ImportacionBancoDetalle;
use App\Models\Inscripcion;
use App\Models\InscripcionObligacionPago;
use App\Models\InscripcionPago;
use App\Models\LenguaExtranjera;
use App\Models\LenguaNativa;
use App\Models\Nacionalidad;
use App\Models\Pais;
use App\Models\Parametro;
use App\Models\Persona;
use App\Models\Proceso;
use App\Models\ProcesoAula;
use App\Models\ProcesoCarrera;
use App\Models\ProcesoCodigoAdmision;
use App\Models\ReglaPago;
use App\Models\TipoColegio;
use App\Models\TipoDocumento;
use App\Models\Turno;
use App\Models\Ubigeo;
use App\Models\Usuario;
use App\Support\AsignadorAulaInscripcion;
use App\Support\CodigosAdmisionProceso;
use App\Support\GeneradorCodigoInscripcion;
use App\Support\InscripcionPagoManager;
use App\Support\ProcesadorFotoPostulante;
use App\Support\ProcesoInscripcionResolver;
use App\Support\ResolvedorModalidadEgreso;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Carga postulantes ficticios en el proceso activo para revisar el sistema de
 * punta a punta: inscripciones activas, con saldo, con beca, en revisión del
 * portal, anuladas y pagos libres para probar el asistente y el portal.
 */
#[Signature('cepre:datos-prueba {--limpiar : Borra los datos de prueba cargados por este comando}')]
#[Description('Carga (o borra) postulantes ficticios para probar el sistema')]
class CargarDatosPrueba extends Command
{
    /**
     * Los DNI de prueba van de 71230001 a 71230040.
     */
    private const PREFIJO_DNI = 7123;

    private const HASH_IMPORTACION = 'datos-prueba-cepre';

    private const RUTA_FIRMA = 'carnet/firma-sello.png';

    /**
     * @var list<array{0:string,1:string,2:string,3:string}>
     */
    private const POSTULANTES = [
        ['LUCIA FERNANDA', 'RAMIREZ', 'TORRES', 'FEMENINO'],
        ['DIEGO ALONSO', 'PANDURO', 'VASQUEZ', 'MASCULINO'],
        ['VALERIA SOFIA', 'RIOS', 'SHAPIAMA', 'FEMENINO'],
        ['JOSE MIGUEL', 'TUANAMA', 'CACHIQUE', 'MASCULINO'],
        ['CAMILA ANDREA', 'FLORES', 'MORI', 'FEMENINO'],
        ['KEVIN ARTURO', 'SANGAMA', 'RENGIFO', 'MASCULINO'],
        ['MARIA JOSE', 'GARCIA', 'AHUANARI', 'FEMENINO'],
        ['LUIS ENRIQUE', 'PINEDO', 'SALDAÑA', 'MASCULINO'],
        ['ANA LUCIA', 'CHAVEZ', 'ICAHUATE', 'FEMENINO'],
        ['CARLOS DANIEL', 'VELA', 'MOZOMBITE', 'MASCULINO'],
        ['XIMENA ALEJANDRA', 'LOPEZ', 'AREVALO', 'FEMENINO'],
        ['JHON BRAYAN', 'CAHUAZA', 'PEREZ', 'MASCULINO'],
        ['ROSA ELENA', 'SINARAHUA', 'GOMEZ', 'FEMENINO'],
        ['PEDRO PABLO', 'MACEDO', 'YUMBATO', 'MASCULINO'],
        ['ANGIE PAOLA', 'TANANTA', 'DIAZ', 'FEMENINO'],
        ['RENZO GABRIEL', 'HUAYMACARI', 'LOZANO', 'MASCULINO'],
        ['DANIELA ISABEL', 'SAAVEDRA', 'NAVARRO', 'FEMENINO'],
        ['FRANCO JESUS', 'AMASIFUEN', 'RUIZ', 'MASCULINO'],
        ['GABRIELA NICOLE', 'PEZO', 'CORDOVA', 'FEMENINO'],
        ['ALEXANDER JOEL', 'URQUIA', 'TAPULLIMA', 'MASCULINO'],
        ['NICOLE STEFANY', 'GUERRA', 'PAIMA', 'FEMENINO'],
        ['JORGE LUIS', 'MANIHUARI', 'SOTO', 'MASCULINO'],
        ['KIARA MILAGROS', 'ISUIZA', 'VARGAS', 'FEMENINO'],
        ['ERICK JAVIER', 'CAMPOS', 'NORIEGA', 'MASCULINO'],
        ['FIORELLA ESTHER', 'OCMIN', 'BARDALES', 'FEMENINO'],
        ['BRUNO SEBASTIAN', 'TAFUR', 'ARIMUYA', 'MASCULINO'],
        ['MILAGROS DEL PILAR', 'YAHUARCANI', 'RIOS', 'FEMENINO'],
        ['ANTHONY RAUL', 'SILVANO', 'MURAYARI', 'MASCULINO'],
        ['SHIRLEY ROXANA', 'PACAYA', 'CURITIMA', 'FEMENINO'],
        ['MARCO ANTONIO', 'LINARES', 'TAMANI', 'MASCULINO'],
        ['YESSICA KARINA', 'NAJAR', 'PISCO', 'FEMENINO'],
        ['WILLIAM CESAR', 'CHUJANDAMA', 'RAMOS', 'MASCULINO'],
        ['ESTRELLA ABIGAIL', 'MUÑOZ', 'CAYRUNA', 'FEMENINO'],
        ['JEAN PIERRE', 'OLIVEIRA', 'MANUYAMA', 'MASCULINO'],
        ['ARIANA BELEN', 'SHUÑA', 'GONZALES', 'FEMENINO'],
        ['CRISTIAN OMAR', 'FASABI', 'HIDALGO', 'MASCULINO'],
        ['DAYANA MELISSA', 'TELLO', 'APAGUEÑO', 'FEMENINO'],
        ['HECTOR MANUEL', 'GRANDEZ', 'INUMA', 'MASCULINO'],
        ['PAMELA JUDITH', 'ROJAS', 'CANAYO', 'FEMENINO'],
        ['OSCAR DAVID', 'TORRES', 'NUÑEZ', 'MASCULINO'],
    ];

    private Proceso $proceso;

    private int $secuenciaOperacion = 0;

    public function handle(): int
    {
        if (app()->isProduction()) {
            $this->error('Este comando no se ejecuta en producción.');

            return self::FAILURE;
        }

        if ($this->option('limpiar')) {
            $this->limpiar();

            return self::SUCCESS;
        }

        $proceso = app(ProcesoInscripcionResolver::class)->resolver()['proceso'];

        if ($proceso === null) {
            $this->error('No hay un proceso activo con oferta. Configure el proceso antes de cargar datos de prueba.');

            return self::FAILURE;
        }

        $this->proceso = $proceso;

        if (Persona::query()->where('numero_documento_per', $this->dni(1))->exists()) {
            $this->warn('Los datos de prueba ya están cargados. Use --limpiar para borrarlos y vuelva a cargarlos.');

            return self::SUCCESS;
        }

        $usuario = Usuario::query()->where('usuario_usu', 'gestor.cepre')->first()
            ?? Usuario::query()->where('usuario_usu', 'superadmin')->firstOrFail();
        Auth::setUser($usuario);

        $this->info("Proceso activo: {$proceso->nombre_pro}");

        $this->asegurarAulasTarde();
        $this->configurarCarnet();
        $this->cargarCodigosAdmision();

        $resumen = collect();

        foreach ($this->escenarios() as $indice => $escenario) {
            try {
                $resumen->push([$this->dni($indice), $this->nombreCompleto($indice), $this->cargarEscenario($indice, $escenario)]);
            } catch (RuntimeException $excepcion) {
                $resumen->push([$this->dni($indice), $this->nombreCompleto($indice), 'OMITIDO: '.$excepcion->getMessage()]);
            }
        }

        $this->table(['DNI', 'Postulante', 'Resultado'], $resumen->all());
        $this->info('Listo. Los N° de operación de los pagos libres están en Inscripciones › Pagos (filtro Libres).');

        return self::SUCCESS;
    }

    /**
     * Qué se carga para cada postulante. Las claves son su número (1 a 40).
     *
     * @return array<int, array{tipo:string,concepto:?string,pagos:list<array{0:float,1:string}>,documento?:string,libres?:list<array{0:float,1:string}>,persona?:bool,foto?:bool}>
     */
    private function escenarios(): array
    {
        $escenarios = [];

        foreach (range(1, 16) as $indice) {
            $escenarios[$indice] = ['tipo' => 'activa', 'concepto' => 'INS_UNA', 'pagos' => [[500, 'TXT']], 'foto' => $indice <= 12];
        }

        foreach (range(17, 22) as $indice) {
            $escenarios[$indice] = [
                'tipo' => 'activa',
                'concepto' => 'INS_CUO',
                'pagos' => [[275, 'TXT']],
                'libres' => $indice <= 18 ? [[275, 'TXT']] : [],
                'foto' => true,
            ];
        }

        $escenarios[23] = ['tipo' => 'activa', 'concepto' => 'PAR_UNU', 'pagos' => [[0, 'PARENTESCO UNU']], 'documento' => 'CONST-RRHH-0153-2026', 'foto' => true];
        $escenarios[24] = ['tipo' => 'activa', 'concepto' => 'BECA_ACA', 'pagos' => [[50, 'ADMISIÓN']], 'documento' => 'RES-0457-2026-UNU-CU', 'foto' => true];
        $escenarios[25] = ['tipo' => 'activa', 'concepto' => 'MEDIA_BECA', 'pagos' => [[250, 'TXT']], 'documento' => 'RES-0460-2026-UNU-CU', 'foto' => false];
        $escenarios[26] = ['tipo' => 'activa', 'concepto' => 'INS_UNA', 'pagos' => [[300, 'TXT'], [200, 'ADMISIÓN']], 'foto' => true];

        foreach (range(27, 30) as $indice) {
            $escenarios[$indice] = ['tipo' => 'revision', 'concepto' => 'INS_UNA', 'pagos' => [[500, 'TXT']], 'foto' => false];
        }

        $escenarios[31] = ['tipo' => 'anulada', 'concepto' => 'INS_UNA', 'pagos' => [[500, 'TXT']], 'foto' => true];
        $escenarios[32] = ['tipo' => 'anulada', 'concepto' => 'INS_UNA', 'pagos' => [[500, 'TXT']], 'foto' => false];

        foreach (range(33, 36) as $indice) {
            $escenarios[$indice] = ['tipo' => 'libre', 'concepto' => null, 'pagos' => [], 'libres' => [[500, 'TXT']], 'persona' => $indice >= 35];
        }

        $escenarios[37] = ['tipo' => 'libre', 'concepto' => null, 'pagos' => [], 'libres' => [[500, 'POSGRADO']]];
        $escenarios[38] = ['tipo' => 'libre', 'concepto' => null, 'pagos' => [], 'libres' => [[275, 'TXT'], [275, 'TXT']]];
        $escenarios[39] = ['tipo' => 'libre', 'concepto' => null, 'pagos' => [], 'libres' => [[200, 'TXT']]];
        $escenarios[40] = ['tipo' => 'libre', 'concepto' => null, 'pagos' => [], 'libres' => [[50, 'ADMISIÓN']]];

        return $escenarios;
    }

    /**
     * @param  array{tipo:string,concepto:?string,pagos:list<array{0:float,1:string}>,documento?:string,libres?:list<array{0:float,1:string}>,persona?:bool,foto?:bool}  $escenario
     */
    private function cargarEscenario(int $indice, array $escenario): string
    {
        return DB::transaction(function () use ($indice, $escenario): string {
            $diasAtras = 20 - ($indice % 20);

            foreach ($escenario['libres'] ?? [] as [$monto, $ubicacion]) {
                $this->crearPago($indice, (float) $monto, $ubicacion, $diasAtras);
            }

            if ($escenario['tipo'] === 'libre') {
                if ($escenario['persona'] ?? false) {
                    $this->crearPersona($indice);

                    return 'PAGO LIBRE (ya registrado como persona)';
                }

                return 'PAGO LIBRE (postulante nuevo)';
            }

            $persona = $this->crearPersona($indice);

            if ($escenario['foto'] ?? false) {
                $this->crearFoto($indice);
            }

            $pagos = collect($escenario['pagos'])
                ->map(fn (array $pago): ImportacionBancoDetalle => $this->crearPago($indice, (float) $pago[0], $pago[1], $diasAtras + 1));

            $inscripcion = $this->crearInscripcion($indice, $persona, $escenario['tipo'] === 'revision', $diasAtras);
            $this->registrarPagos($inscripcion, (string) $escenario['concepto'], $pagos, $escenario['documento'] ?? null);

            if ($escenario['tipo'] === 'anulada') {
                $inscripcion->update([
                    'estado_ins' => EstadoInscripcion::Anulado->value,
                    'id_proceso_aula' => null,
                    'motivo_anulacion_ins' => 'EL POSTULANTE SOLICITO SU RETIRO DEL PROCESO (DATO DE PRUEBA).',
                ]);
            }

            $inscripcion->refresh();

            return match (true) {
                $inscripcion->estado_ins === EstadoInscripcion::Anulado => "ANULADA {$inscripcion->codigo_ins}",
                $inscripcion->enRevision() => "EN REVISIÓN (portal) {$inscripcion->codigo_ins}",
                default => "{$inscripcion->estado_ins->value} {$inscripcion->codigo_ins} · {$escenario['concepto']}",
            };
        });
    }

    private function crearPersona(int $indice): Persona
    {
        [$nombres, $paterno, $materno, $sexo] = self::POSTULANTES[$indice - 1];
        $ubigeos = $this->ubigeosCoronelPortillo();
        $ubigeo = $ubigeos->get($indice % $ubigeos->count());
        $esShipibo = $indice % 9 === 0;
        $anioEgreso = $this->anioEgreso($indice);

        return Persona::query()->create([
            'id_tipo_documento' => TipoDocumento::query()->where('longitud_minima_tdo', 8)->where('longitud_maxima_tdo', 8)->value('id_tdo'),
            'numero_documento_per' => $this->dni($indice),
            'solo_un_apellido_per' => false,
            'apellido_paterno_per' => $paterno,
            'apellido_materno_per' => $materno,
            'apellido_casada_per' => null,
            'nombres_per' => $nombres,
            'fecha_nacimiento_per' => now()->setDate($anioEgreso - 17, ($indice % 12) + 1, ($indice % 27) + 1)->toDateString(),
            'sexo_per' => $sexo,
            'id_estado_civil' => EstadoCivil::query()->where('nombre_eci', 'like', 'SOLTERO%')->value('id_eci'),
            'id_pais_nacimiento' => Pais::query()->where('codigo_pai', '1')->value('id_pai'),
            'id_nacionalidad' => Nacionalidad::query()->orderBy('id_nac')->value('id_nac'),
            'id_ubigeo_nacimiento' => $ubigeo->id_ubi,
            'id_ubigeo_domicilio' => $ubigeo->id_ubi,
            'direccion_per' => sprintf('%s N° %d', ['JR. TACNA', 'AV. CENTENARIO', 'JR. ZAVALA', 'AV. SAENZ PEÑA', 'JR. AGUAYTIA'][$indice % 5], 100 + $indice * 7),
            'celular_per' => sprintf('9%08d', 61000000 + $indice * 1371),
            'telefono_per' => null,
            'correo_electronico_per' => Str::lower(Str::ascii(Str::before($nombres, ' ').'.'.$paterno)).$indice.'@correo.test',
            'nombre_apoderado_per' => $this->nombreApoderado($indice),
            'celular_apoderado_per' => sprintf('9%08d', 72000000 + $indice * 977),
            'discapacidad_per' => false,
            'id_tipo_discapacidad' => null,
            'tipo_lengua_materna_per' => $esShipibo ? TipoLenguaMaternaPersona::Nativa->value : TipoLenguaMaternaPersona::Extranjera->value,
            'id_lengua_nativa' => $esShipibo ? LenguaNativa::query()->where('nombre_lna', 'like', 'SHIPIBO%')->value('id_lna') : null,
            'id_lengua_extranjera' => $esShipibo ? null : LenguaExtranjera::query()->where('nombre_lex', 'like', '%CASTELLANO%')->value('id_lex'),
            'id_identidad_etnica' => IdentidadEtnica::query()->where('nombre_iet', $esShipibo ? 'like' : '=', $esShipibo ? 'NATIVO%' : 'MESTIZO')->value('id_iet'),
            'observacion_per' => 'DATO DE PRUEBA',
            'estado_per' => 'HABILITADO',
        ]);
    }

    private function crearInscripcion(int $indice, Persona $persona, bool $desdePortal, int $diasAtras): Inscripcion
    {
        $carreras = ProcesoCarrera::query()
            ->where('id_proceso', $this->proceso->id_pro)
            ->where('estado_pca', 'HABILITADO')
            ->orderBy('id_pca')
            ->get();

        if ($carreras->isEmpty()) {
            throw new RuntimeException('el proceso no tiene carreras en su oferta');
        }

        $procesoCarrera = $carreras->get(($indice * 5) % $carreras->count());
        $anioEgreso = $this->anioEgreso($indice);
        $modalidad = app(ResolvedorModalidadEgreso::class)->resolver($this->proceso, $anioEgreso);

        if ($modalidad === null) {
            throw new RuntimeException("el proceso no tiene modalidad para el año de egreso {$anioEgreso}");
        }

        $turno = $this->turnoPara($indice);
        $colegio = Colegio::query()
            ->whereIn('id_ubigeo', $this->ubigeosCoronelPortillo()->pluck('id_ubi'))
            ->where('estado_col', 'HABILITADO')
            ->orderBy('id_col')
            ->skip(($indice * 7) % 200)
            ->first();

        $inscripcion = Inscripcion::query()->create([
            'codigo_ins' => 'TMP-'.Str::upper(Str::random(10)),
            'id_persona' => $persona->id_per,
            'id_proceso' => $this->proceso->id_pro,
            'id_sede' => $procesoCarrera->id_sede,
            'id_modalidad_admision' => $modalidad->id_mad,
            'id_carrera' => $procesoCarrera->id_carrera,
            'id_turno' => $turno->id_tur,
            'fecha_inscripcion_ins' => now()->subDays($diasAtras)->setTime(8 + ($indice % 9), ($indice * 13) % 60),
            'estado_ins' => EstadoInscripcion::Pendiente->value,
            'origen_ins' => $desdePortal ? 'PORTAL' : 'SISTEMA',
            'requiere_revision_ins' => $desdePortal,
            'anio_graduacion_ins' => $anioEgreso,
            'id_pais_colegio' => Pais::query()->where('codigo_pai', '1')->value('id_pai'),
            'id_colegio' => $colegio?->id_col,
            'id_tipo_colegio' => TipoColegio::query()->orderBy('id_tco')->skip($indice % 2)->value('id_tco'),
            'veces_postula_unu_ins' => $indice % 3,
            'veces_postula_otras_ins' => $indice % 2,
            'observacion_ins' => null,
        ]);

        $inscripcion->update(['codigo_ins' => app(GeneradorCodigoInscripcion::class)->generar($inscripcion)]);
        app(AsignadorAulaInscripcion::class)->asignarDisponible($inscripcion, $this->proceso->id_pro, $turno->id_tur);

        return $inscripcion;
    }

    /**
     * Igual que el paso 1 del asistente: crea la obligación según la regla y
     * le asocia los pagos, que pasan a REGISTRADO.
     *
     * @param  Collection<int, ImportacionBancoDetalle>  $pagos
     */
    private function registrarPagos(Inscripcion $inscripcion, string $codigoConcepto, Collection $pagos, ?string $documento): void
    {
        $regla = ReglaPago::query()
            ->where('id_proceso', $this->proceso->id_pro)
            ->where('estado_rpa', 'HABILITADO')
            ->whereHas('conceptoPago', fn ($query) => $query->where('codigo_cpg', $codigoConcepto))
            ->first();

        if ($regla === null) {
            throw new RuntimeException("el proceso no tiene regla de pago para {$codigoConcepto}");
        }

        $obligacion = new InscripcionObligacionPago([
            'id_inscripcion' => $inscripcion->id_ins,
            'id_regla_pago' => $regla->id_rpa,
            'monto_total_iop' => round((float) $regla->monto_total_rpa, 2),
            'numero_cuotas_iop' => max(1, (int) $regla->numero_maximo_cuotas_rpa),
            'numero_documento_beneficio_iop' => $documento,
            'estado_iop' => EstadoObligacionPago::Pendiente->value,
        ]);
        $obligacion->monto_pagado_iop = 0;
        $obligacion->saldo_iop = $obligacion->monto_total_iop;
        $obligacion->save();

        app(InscripcionPagoManager::class)->asociarDetalles($obligacion, $pagos);
    }

    private function crearPago(int $indice, float $monto, string $ubicacion, int $diasAtras): ImportacionBancoDetalle
    {
        $this->secuenciaOperacion++;
        $dni = $this->dni($indice);
        $esTxt = $ubicacion === 'TXT';
        $operacion = $esTxt
            ? sprintf('9%04d', $indice * 10 + $this->secuenciaOperacion % 10)
            : sprintf('REC-%03d-%02d', $indice, $this->secuenciaOperacion % 100);

        return ImportacionBancoDetalle::query()->create([
            'id_importacion_banco' => $esTxt ? $this->importacionTxt()->id_iba : null,
            'origen_ibd' => $esTxt ? 'TXT' : 'MANUAL',
            'numero_documento_ibd' => $dni,
            'nombre_depositante_ibd' => $this->nombreCompleto($indice),
            'numero_operacion_raw_ibd' => $esTxt ? '8170'.$operacion : $operacion,
            'numero_operacion_visible_ibd' => $operacion,
            'numero_operacion_ibd' => $operacion,
            'codigo_canal_ibd' => $esTxt ? 'Y%G' : null,
            'codigo_oficina_ibd' => $esTxt ? '01068' : null,
            'fecha_pago_ibd' => now()->subDays($diasAtras)->toDateString(),
            'monto_ibd' => $monto,
            'ubicacion_ibd' => $esTxt ? 'RECAUDACIÓN BBVA N° 13248' : $ubicacion,
            'texto_linea_ibd' => $esTxt ? 'DATO DE PRUEBA' : null,
            'estado_ibd' => EstadoImportacionBancoDetalle::Pendiente->value,
            'observacion_ibd' => $esTxt ? null : 'PAGO MANUAL DE PRUEBA',
        ]);
    }

    private function importacionTxt(): ImportacionBanco
    {
        return ImportacionBanco::query()->firstOrCreate(
            ['hash_archivo_iba' => self::HASH_IMPORTACION],
            [
                'nombre_archivo_iba' => 'PRUEBA_RECAUDACION_BBVA.TXT',
                'moneda_iba' => 'PEN',
                'fecha_importacion_iba' => now(),
                'fecha_lote_iba' => today(),
                'total_registros_iba' => 0,
                'total_registros_reportado_iba' => 0,
                'total_monto_iba' => 0,
                'total_monto_reportado_iba' => 0,
                'estado_iba' => 'PROCESADO',
                'observacion_iba' => 'Lote ficticio cargado por cepre:datos-prueba.',
            ],
        );
    }

    /**
     * Foto de relleno: silueta sobre un color de fondo con las iniciales.
     */
    private function crearFoto(int $indice): void
    {
        [$nombres, $paterno] = self::POSTULANTES[$indice - 1];
        $ancho = ProcesadorFotoPostulante::ANCHO;
        $alto = ProcesadorFotoPostulante::ALTO;
        $imagen = imagecreatetruecolor($ancho, $alto);
        $fondos = [[214, 228, 240], [228, 236, 222], [240, 230, 214], [232, 222, 238]];
        [$r, $g, $b] = $fondos[$indice % count($fondos)];
        imagefill($imagen, 0, 0, imagecolorallocate($imagen, $r, $g, $b));

        $silueta = imagecolorallocate($imagen, 92, 104, 120);
        imagefilledellipse($imagen, (int) ($ancho / 2), 150, 130, 150, $silueta);
        imagefilledellipse($imagen, (int) ($ancho / 2), 380, 260, 240, $silueta);

        $iniciales = imagecreatetruecolor(24, 14);
        imagefill($iniciales, 0, 0, imagecolorallocate($iniciales, 92, 104, 120));
        imagestring($iniciales, 5, 2, 0, Str::substr($nombres, 0, 1).Str::substr($paterno, 0, 1), imagecolorallocate($iniciales, 255, 255, 255));
        imagecopyresampled($imagen, $iniciales, 102, 300, 0, 0, 96, 56, 24, 14);

        $ruta = tempnam(sys_get_temp_dir(), 'foto').'.jpg';
        imagejpeg($imagen, $ruta, 85);
        imagedestroy($imagen);
        imagedestroy($iniciales);

        app(ProcesadorFotoPostulante::class)->guardar(new UploadedFile($ruta, $this->dni($indice).'.jpg', 'image/jpeg', null, true), $this->dni($indice));
        @unlink($ruta);
    }

    /**
     * Si falta, completa la configuración del carnet con un director ficticio
     * y una firma de relleno. No cambia lo que ya está configurado.
     */
    private function configurarCarnet(): void
    {
        if (blank(Parametro::valor('carnet_director_nombre'))) {
            Parametro::guardar('carnet_director_nombre', 'MG. CARLOS ALBERTO RIOS PAREDES');
            $this->line('Carnet: se configuró un director ficticio (cámbielo en Conf. Académica › Carnet).');
        }

        if (blank(Parametro::valor('carnet_director_cargo'))) {
            Parametro::guardar('carnet_director_cargo', 'DIRECTOR GENERAL CEPRE-UNU');
        }

        if (blank(Parametro::valor('carnet_firma_sello')) || ! Storage::disk('local')->exists((string) Parametro::valor('carnet_firma_sello'))) {
            $firma = imagecreatetruecolor(360, 140);
            imagesavealpha($firma, true);
            imagefill($firma, 0, 0, imagecolorallocatealpha($firma, 0, 0, 0, 127));
            $tinta = imagecolorallocate($firma, 20, 40, 120);
            imagesetthickness($firma, 3);

            for ($x = 30; $x < 300; $x += 4) {
                $y = (int) (70 + 25 * sin($x / 18) * cos($x / 47));
                imageline($firma, $x, $y, $x + 4, (int) (70 + 25 * sin(($x + 4) / 18) * cos(($x + 4) / 47)), $tinta);
            }

            $sello = imagecolorallocatealpha($firma, 150, 20, 20, 40);
            imageellipse($firma, 290, 70, 110, 110, $sello);
            imageellipse($firma, 290, 70, 96, 96, $sello);
            imagestring($firma, 2, 260, 64, 'CEPRE-UNU', $sello);

            ob_start();
            imagepng($firma);
            Storage::disk('local')->put(self::RUTA_FIRMA, (string) ob_get_clean());
            imagedestroy($firma);
            Parametro::guardar('carnet_firma_sello', self::RUTA_FIRMA);
            $this->line('Carnet: se generó una firma de relleno.');
        }
    }

    /**
     * Si el proceso no tiene ningún código de Admisión, carga códigos
     * ficticios para probar la exportación.
     */
    private function cargarCodigosAdmision(): void
    {
        if (ProcesoCodigoAdmision::query()->where('id_proceso', $this->proceso->id_pro)->exists()) {
            return;
        }

        $codigos = app(CodigosAdmisionProceso::class)->combinaciones($this->proceso)
            ->values()
            ->mapWithKeys(fn (array $fila, int $posicion): array => [
                $fila['clave'] => $fila['id_carrera'] === null
                    ? sprintf('L%02d%d', $fila['id_sede'], $fila['id_modalidad'])
                    : sprintf('%02d%03d', $fila['id_modalidad'], $fila['id_carrera']),
            ])
            ->all();

        $guardados = app(CodigosAdmisionProceso::class)->guardar($this->proceso, $codigos);
        $this->line("Códigos de Admisión: se cargaron {$guardados} códigos ficticios.");
    }

    /**
     * El turno TARDE necesita aulas para repartir a los postulantes; si no
     * tiene, se le asignan dos aulas que el proceso no esté usando.
     */
    private function asegurarAulasTarde(): void
    {
        $tarde = Turno::query()->where('codigo_tur', 'TAR')->first();

        if ($tarde === null || ProcesoAula::query()->where('id_proceso', $this->proceso->id_pro)->where('id_turno', $tarde->id_tur)->exists()) {
            return;
        }

        $usadas = ProcesoAula::query()->where('id_proceso', $this->proceso->id_pro)->pluck('id_aula');

        Aula::query()
            ->where('estado_aul', 'HABILITADO')
            ->whereNotIn('id_aul', $usadas)
            ->orderBy('orden_aul')
            ->take(2)
            ->get()
            ->each(fn (Aula $aula) => ProcesoAula::query()->create([
                'id_proceso' => $this->proceso->id_pro,
                'id_turno' => $tarde->id_tur,
                'id_aula' => $aula->id_aul,
                'capacidad_pau' => 30,
                'estado_pau' => 'HABILITADO',
            ]));

        $this->line('Aulas: se asignaron 2 aulas al turno TARDE del proceso.');
    }

    private function limpiar(): void
    {
        $dnis = collect(range(1, count(self::POSTULANTES)))->map(fn (int $indice): string => $this->dni($indice));

        DB::transaction(function () use ($dnis): void {
            $idsPersona = Persona::withTrashed()->whereIn('numero_documento_per', $dnis)->pluck('id_per');
            $idsInscripcion = Inscripcion::withTrashed()->whereIn('id_persona', $idsPersona)->pluck('id_ins');
            $idsObligacion = InscripcionObligacionPago::withTrashed()->whereIn('id_inscripcion', $idsInscripcion)->pluck('id_iop');

            InscripcionPago::withTrashed()->whereIn('id_inscripcion_obligacion_pago', $idsObligacion)->forceDelete();
            InscripcionObligacionPago::withTrashed()->whereIn('id_iop', $idsObligacion)->forceDelete();
            Inscripcion::withTrashed()->whereIn('id_ins', $idsInscripcion)->forceDelete();
            ImportacionBancoDetalle::withTrashed()->whereIn('numero_documento_ibd', $dnis)->forceDelete();
            ImportacionBanco::withTrashed()->where('hash_archivo_iba', self::HASH_IMPORTACION)->forceDelete();

            FotoPostulante::withTrashed()->whereIn('numero_documento_fot', $dnis)->get()->each(function (FotoPostulante $foto): void {
                Storage::disk(FotoPostulante::DISCO)->delete($foto->archivo_fot);
                $foto->forceDelete();
            });

            Persona::withTrashed()->whereIn('id_per', $idsPersona)->forceDelete();
        });

        $this->info('Se borraron los postulantes, inscripciones, pagos y fotos de prueba.');
        $this->line('No se tocaron la configuración del carnet, los códigos de Admisión ni las aulas del proceso.');
    }

    private function turnoPara(int $indice): Turno
    {
        $codigo = $indice % 3 === 0 ? 'TAR' : 'MAN';
        $turno = Turno::query()->where('codigo_tur', $codigo)->first();

        $tieneAulas = $turno !== null && ProcesoAula::query()
            ->where('id_proceso', $this->proceso->id_pro)
            ->where('id_turno', $turno->id_tur)
            ->where('estado_pau', 'HABILITADO')
            ->exists();

        return $tieneAulas ? $turno : Turno::query()->where('codigo_tur', 'MAN')->firstOrFail();
    }

    /**
     * Mezcla egresados (ORDINARIO) y estudiantes en colegio (RESERVA).
     */
    private function anioEgreso(int $indice): int
    {
        $maximoOrdinario = $this->proceso->anioEgresoMaximoOrdinario();

        return $indice % 4 === 0 ? $maximoOrdinario + 1 : $maximoOrdinario - ($indice % 3);
    }

    /**
     * @return Collection<int, Ubigeo>
     */
    private function ubigeosCoronelPortillo(): Collection
    {
        return Ubigeo::query()
            ->where('codigo_departamento_ubi', '25')
            ->where('codigo_provincia_ubi', '01')
            ->where('estado_ubi', 'HABILITADO')
            ->orderBy('id_ubi')
            ->get()
            ->values();
    }

    private function nombreApoderado(int $indice): string
    {
        $paterno = self::POSTULANTES[$indice - 1][1];
        $nombres = ['ROSA MARIA', 'JUAN CARLOS', 'MARTHA', 'VICTOR HUGO', 'ELENA', 'MIGUEL ANGEL'][$indice % 6];
        $materno = self::POSTULANTES[(($indice + 7) % count(self::POSTULANTES))][2];

        return "{$paterno} {$materno} {$nombres}";
    }

    private function nombreCompleto(int $indice): string
    {
        [$nombres, $paterno, $materno] = self::POSTULANTES[$indice - 1];

        return "{$paterno} {$materno} {$nombres}";
    }

    private function dni(int $indice): string
    {
        return sprintf('%d%04d', self::PREFIJO_DNI, $indice);
    }
}
