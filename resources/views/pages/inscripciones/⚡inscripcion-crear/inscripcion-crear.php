<?php

use App\Models\Inscripcion;
use App\Models\Proceso;
use App\Support\ValidadorVigenciaInscripcion;
use App\Traits\AuthorizesPermisos;
use App\Traits\InscripcionCrear\GestionaCatalogosInscripcion;
use App\Traits\InscripcionCrear\GestionaPasoExpedientes;
use App\Traits\InscripcionCrear\GestionaPasoGeneral;
use App\Traits\InscripcionCrear\GestionaPasoPago;
use App\Traits\InscripcionCrear\GestionaPasoPersonal;
use App\Traits\InscripcionCrear\GestionaPasoPostulacion;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

new
#[Layout('layouts.app')]
#[Title('Nueva inscripción')]
class extends Component
{
    use AuthorizesPermisos;
    use GestionaCatalogosInscripcion;
    use GestionaPasoExpedientes;
    use GestionaPasoGeneral;
    use GestionaPasoPago;
    use GestionaPasoPersonal;
    use GestionaPasoPostulacion;
    use WithFileUploads;

    public int $pasoActual = 1;

    public array $tiposDocumentoCatalogo = [];

    public array $estadosCivilesCatalogo = [];

    public array $paisesCatalogo = [];

    public array $nacionalidadesCatalogo = [];

    public array $identidadesEtnicasCatalogo = [];

    public array $lenguasNativasCatalogo = [];

    public array $lenguasExtranjerasCatalogo = [];

    public array $tiposDiscapacidadCatalogo = [];

    public array $tiposColegioCatalogo = [];

    /**
     * @var array{
     *   id_tipo_documento:string,
     *   numero_documento:string,
     *   solo_un_apellido:bool,
     *   tiene_apellido_casada:bool,
     *   apellido_paterno:string,
     *   apellido_materno:string,
     *   apellido_casada:string,
     *   nombres:string,
     *   fecha_nacimiento:string,
     *   sexo:string,
     *   id_estado_civil:string,
     *   id_pais_nacimiento:string,
     *   id_nacionalidad:string,
     *   id_ubigeo_nacimiento:string,
     *   id_ubigeo_domicilio:string,
     *   direccion:string,
     *   celular:string,
     *   telefono:string,
     *   correo_electronico:string,
     *   discapacidad:bool,
     *   id_tipo_discapacidad:string,
     *   tipo_lengua_materna:string,
     *   id_identidad_etnica:string,
     *   id_lengua_nativa:string,
     *   id_lengua_extranjera:string,
     *   observacion:string
     * }
     */
    public array $personaForm = [
        'id_tipo_documento' => '',
        'numero_documento' => '',
        'solo_un_apellido' => false,
        'tiene_apellido_casada' => false,
        'apellido_paterno' => '',
        'apellido_materno' => '',
        'apellido_casada' => '',
        'nombres' => '',
        'fecha_nacimiento' => '',
        'sexo' => '',
        'id_estado_civil' => '',
        'id_pais_nacimiento' => '',
        'id_nacionalidad' => '',
        'id_ubigeo_nacimiento' => '',
        'id_ubigeo_domicilio' => '',
        'direccion' => '',
        'celular' => '',
        'telefono' => '',
        'correo_electronico' => '',
        'discapacidad' => false,
        'id_tipo_discapacidad' => '',
        'tipo_lengua_materna' => '',
        'id_identidad_etnica' => '',
        'id_lengua_nativa' => '',
        'id_lengua_extranjera' => '',
        'observacion' => '',
    ];

    /**
     * @var array{
     *   anio_graduacion:string,
     *   id_pais_colegio:string,
     *   id_tipo_colegio:string,
     *   id_colegio:string,
     *   nombre_colegio_extranjero:string,
     *   veces_postula_unu:int,
     *   veces_postula_otras:int,
     *   observacion:string
     * }
     */
    public array $generalForm = [
        'anio_graduacion' => '',
        'id_pais_colegio' => '',
        'id_tipo_colegio' => '',
        'id_colegio' => '',
        'nombre_colegio_extranjero' => '',
        'veces_postula_unu' => 0,
        'veces_postula_otras' => 0,
        'observacion' => '',
    ];

    /**
     * @var array{
     *   id_modalidad_admision:string,
     *   id_lugar_inscripcion:string,
     *   id_turno:string,
     *   id_proceso_oferta:string,
     *   id_convenio_proceso:string
     * }
     */
    public array $postulacionForm = [
        'id_modalidad_admision' => '',
        'id_lugar_inscripcion' => '',
        'id_turno' => '',
        'id_proceso_oferta' => '',
        'id_convenio_proceso' => '',
    ];

    /**
     * @var array{
     *   id_regla_pago:string,
     *   numero_documento:string,
     *   numero_operacion:string,
     *   fecha_pago:string,
     *   id_detalle_banco:string
     * }
     */
    public array $pagoForm = [
        'id_regla_pago' => '',
        'numero_documento' => '',
        'numero_operacion' => '',
        'fecha_pago' => '',
        'id_detalle_banco' => '',
    ];

    /**
     * @var array<int, int>
     */
    public array $resultadoPagoDetalleIds = [];

    public bool $busquedaPagoRealizada = false;

    /**
     * @var array<int, array{
     *   id_tipo_expediente:string,
     *   estado_revision:string,
     *   observacion:string,
     *   nombre_archivo:string,
     *   archivo_url:string
     * }>
     */
    public array $expedientesForm = [];

    /**
     * @var array<int, TemporaryUploadedFile|null>
     */
    public array $archivosExpediente = [];

    public ?int $personaId = null;

    public ?int $inscripcionId = null;

    public ?string $reniecMensaje = null;

    public string $reniecTipoMensaje = 'info';

    public ?string $origenDatosPersona = null;

    public string $ubigeoNacimientoLabel = '';

    public string $ubigeoDomicilioLabel = '';

    public string $colegioLabel = '';

    /**
     * @var array{disponible:bool,mensaje:string,proceso:?Proceso}|null
     */
    protected ?array $contextoProcesoPostulacionCache = null;

    public function mount(?string $inscripcion = null): void
    {
        $this->autorizarPermiso('inscripcion', 'crear');
        $this->hidratarCatalogos();
        $this->pagoForm['fecha_pago'] = now()->subDay()->toDateString();

        if ($inscripcion !== null) {
            $idInscripcion = decode($inscripcion);

            abort_unless($idInscripcion !== null, 404);

            $this->cargarInscripcionExistente($idInscripcion);
        }
    }

    private function cargarInscripcionExistente(int $idInscripcion): void
    {
        $inscripcion = Inscripcion::query()
            ->with([
                'persona.ubigeoNacimiento',
                'persona.ubigeoDomicilio',
                'procesoOferta.proceso',
                'procesoOferta.ofertaBase.modalidadLugarInscripcion.modalidadAdmision',
                'procesoOferta.ofertaBase.modalidadLugarInscripcion.lugarInscripcion',
                'procesoOferta.turno',
                'convenioProceso.convenio',
                'paisColegio',
                'colegio.ubigeo',
                'obligacionesPago.reglaPago.conceptoPago',
                'obligacionesPago.pagos.importacionBancoDetalle',
                'expedientes.tipoExpediente',
            ])
            ->findOrFail($idInscripcion);

        $persona = $inscripcion->persona;

        if ($persona === null) {
            abort(404);
        }

        $this->inscripcionId = $inscripcion->id_ins;
        $this->personaId = $persona->id_per;
        $this->cargarPersonaDesdeSistema($persona);
        $this->cargarInformacionGeneralExistente($inscripcion);
        $this->cargarPostulacionExistente($inscripcion);
        $this->cargarPagoExistente($inscripcion);
        $this->pasoActual = $this->resolverPasoActualDesdeInscripcion($inscripcion);

        if ($this->pasoActual >= 5) {
            $this->inicializarExpedientesForm();
        }
    }

    private function resolverPasoActualDesdeInscripcion(Inscripcion $inscripcion): int
    {
        if ($inscripcion->expedientes->isNotEmpty() || $this->tienePagoValidadoWizard) {
            return 5;
        }

        return 4;
    }

    #[Computed]
    public function inscripcionBorrador(): ?Inscripcion
    {
        if ($this->inscripcionId === null) {
            return null;
        }

        return Inscripcion::query()
            ->with([
                'persona',
                'procesoOferta.proceso',
                'procesoOferta.ofertaBase.modalidadLugarInscripcion.modalidadAdmision',
                'procesoOferta.ofertaBase.modalidadLugarInscripcion.lugarInscripcion',
                'procesoOferta.ofertaBase.sedeCarrera.carrera',
                'procesoOferta.turno',
                'convenioProceso.convenio',
            ])
            ->find($this->inscripcionId);
    }

    #[Computed]
    public function pasos(): array
    {
        return [
            1 => ['titulo' => 'Información personal', 'descripcion' => 'Datos base del postulante', 'icono' => 'user'],
            2 => ['titulo' => 'Información general', 'descripcion' => 'Datos complementarios', 'icono' => 'note-book'],
            3 => ['titulo' => 'Postulación', 'descripcion' => 'Proceso, oferta y colegio', 'icono' => 'school'],
            4 => ['titulo' => 'Pago', 'descripcion' => 'Validación del pago bancario', 'icono' => 'credit-card'],
            5 => ['titulo' => 'Expedientes', 'descripcion' => 'Carga y revisión de documentos', 'icono' => 'folder-open'],
        ];
    }

    public function irPasoAnterior(): void
    {
        if ($this->pasoActual > 1) {
            $this->pasoActual--;
        }
    }

    public function cancelarYReiniciarWizard(): void
    {
        $this->personaId = null;
        $this->inscripcionId = null;
        $this->reniecMensaje = null;
        $this->reniecTipoMensaje = 'info';
        $this->origenDatosPersona = null;
        $this->ubigeoNacimientoLabel = '';
        $this->ubigeoDomicilioLabel = '';
        $this->colegioLabel = '';
        $this->contextoProcesoPostulacionCache = null;
        $this->personaForm = $this->formularioPersonaBase();
        $this->generalForm = [
            'anio_graduacion' => '',
            'id_pais_colegio' => '',
            'id_tipo_colegio' => '',
            'id_colegio' => '',
            'nombre_colegio_extranjero' => '',
            'veces_postula_unu' => 0,
            'veces_postula_otras' => 0,
            'observacion' => '',
        ];
        $this->postulacionForm = [
            'id_modalidad_admision' => '',
            'id_lugar_inscripcion' => '',
            'id_turno' => '',
            'id_proceso_oferta' => '',
            'id_convenio_proceso' => '',
        ];
        $this->pagoForm = $this->formularioPagoBase();
        $this->resultadoPagoDetalleIds = [];
        $this->busquedaPagoRealizada = false;
        $this->resetErrorBag();
        $this->pasoActual = 1;

        $this->dispatch('toast', message: 'Formulario reiniciado. Puede comenzar una nueva inscripcion.', type: 'info');
    }

    public function irPaso(int $paso): void
    {
        if ($paso < 1 || $paso > count($this->pasos())) {
            return;
        }

        if ($this->inicioInscripcionBloqueado && $paso > 1) {
            $this->dispatch(
                'toast',
                message: 'No hay un proceso de inscripcion activo en este momento. No se puede iniciar una nueva inscripcion.',
                type: 'warning'
            );

            return;
        }

        if ($paso > 1 && $this->personaId === null) {
            $this->dispatch('toast', message: 'Primero debe guardar la informacion personal.', type: 'warning');

            return;
        }

        if ($paso > 2) {
            $this->normalizarEstadoGeneralForm();

            try {
                $this->validate(
                    $this->rulesPasoGeneral(),
                    $this->messagesPasoGeneral(),
                );
            } catch (ValidationException $exception) {
                $this->setErrorBag($exception->validator->errors());
                $this->dispatch('toast', message: 'Revise la informacion general antes de continuar.', type: 'warning');

                return;
            }
        }

        if ($paso > 3) {
            $contextoProceso = $this->refrescarContextoProcesoPostulacion();

            if (! $contextoProceso['disponible']) {
                $this->dispatch('toast', message: $contextoProceso['mensaje'], type: 'warning');

                return;
            }
        }

        if ($paso > 3 && $this->inscripcionId === null) {
            $this->dispatch('toast', message: 'Primero debe guardar la postulacion antes de continuar.', type: 'warning');

            return;
        }

        if ($paso > 3) {
            $this->prepararPasoPago();
        }

        if ($paso > 4 && ! $this->tienePagoValidadoWizard) {
            $this->dispatch('toast', message: 'Primero debe validar al menos un pago antes de continuar.', type: 'warning');

            return;
        }

        if ($paso === 5) {
            $this->inicializarExpedientesForm();
        }

        $this->pasoActual = $paso;
    }

    public function volverListado(): void
    {
        $this->redirect(route('inscripciones.index'), navigate: true);
    }

    private function mayusculas(string $valor): string
    {
        return mb_strtoupper(trim($valor), 'UTF-8');
    }

    private function validarInscripcionBorradorVigente(Inscripcion $inscripcion): void
    {
        $procesoActivo = $this->obtenerProcesoActivoVigenteOrFail();

        app(ValidadorVigenciaInscripcion::class)->ofertaVigente((int) $inscripcion->id_proceso_oferta, $procesoActivo);
        app(ValidadorVigenciaInscripcion::class)->convenioVigente($inscripcion->id_convenio_proceso, $procesoActivo);
    }

    private function nullableUpper(?string $valor): ?string
    {
        $valor = $this->nullableTrim($valor);

        return $valor === null ? null : mb_strtoupper($valor, 'UTF-8');
    }

    private function nullableLower(?string $valor): ?string
    {
        $valor = $this->nullableTrim($valor);

        return $valor === null ? null : mb_strtolower($valor, 'UTF-8');
    }

    private function nullableTrim(?string $valor): ?string
    {
        $valor = trim((string) $valor);

        return $valor !== '' ? $valor : null;
    }

    private function formatearDocumentoIdentidad(?string $numeroDocumento): string
    {
        if ($numeroDocumento === null || trim($numeroDocumento) === '') {
            return 'SIN DOCUMENTO';
        }

        $numeroDocumento = trim($numeroDocumento);

        if (preg_match('/^\d{1,8}$/', $numeroDocumento) === 1) {
            return str_pad($numeroDocumento, 8, '0', STR_PAD_LEFT);
        }

        return $numeroDocumento;
    }
};
