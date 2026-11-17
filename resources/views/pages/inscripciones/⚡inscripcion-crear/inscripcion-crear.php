<?php

use App\Models\Inscripcion;
use App\Models\Proceso;
use App\Traits\AuthorizesPermisos;
use App\Traits\InscripcionCrear\GestionaCatalogosInscripcion;
use App\Traits\InscripcionCrear\GestionaPasoGeneral;
use App\Traits\InscripcionCrear\GestionaPasoPago;
use App\Traits\InscripcionCrear\GestionaPasoPersonal;
use App\Traits\InscripcionCrear\GestionaPasoPostulacion;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.app')]
#[Title('Nueva inscripción')]
class extends Component
{
    use AuthorizesPermisos;
    use GestionaCatalogosInscripcion;
    use GestionaPasoGeneral;
    use GestionaPasoPago;
    use GestionaPasoPersonal;
    use GestionaPasoPostulacion;

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
     *   nombre_apoderado:string,
     *   celular_apoderado:string,
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
        'nombre_apoderado' => '',
        'celular_apoderado' => '',
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
     *   id_sede:string,
     *   id_carrera:string,
     *   id_turno:string
     * }
     */
    public array $postulacionForm = [
        'id_sede' => '',
        'id_carrera' => '',
        'id_turno' => '',
    ];

    /**
     * @var array{
     *   id_regla_pago:string,
     *   numero_documento:string,
     *   numero_operacion:string,
     *   numero_documento_beneficio:string
     * }
     */
    public array $pagoForm = [
        'id_regla_pago' => '',
        'numero_documento' => '',
        'numero_operacion' => '',
        'numero_documento_beneficio' => '',
    ];

    /**
     * Pagos LIBRES del DNI que se encontraron en el paso 1.
     *
     * @var array<int, int>
     */
    public array $resultadoPagoDetalleIds = [];

    /**
     * Pagos marcados para esta inscripción: [id_ibd => bool].
     *
     * @var array<int|string, bool>
     */
    public array $pagosSeleccionados = [];

    public bool $busquedaPagoRealizada = false;

    /**
     * El paso 1 quedó conforme: los pagos marcados alcanzan el mínimo.
     */
    public bool $pagoValidado = false;

    /**
     * El operador revisó el resumen y confirma que los datos son correctos.
     */
    public bool $confirmacionDatos = false;

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
                'proceso',
                'sede',
                'modalidadAdmision',
                'carrera',
                'turno',
                'paisColegio',
                'colegio.ubigeo',
                'obligacionesPago.reglaPago.conceptoPago',
                'obligacionesPago.pagos.importacionBancoDetalle',
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
        $this->pasoActual = $this->pagoValidado ? 4 : 1;
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
                'proceso',
                'sede',
                'modalidadAdmision',
                'carrera',
                'turno',
            ])
            ->find($this->inscripcionId);
    }

    #[Computed]
    public function pasos(): array
    {
        return [
            1 => ['titulo' => 'Validación de pago', 'descripcion' => 'DNI, operación y modalidad de pago', 'icono' => 'credit-card'],
            2 => ['titulo' => 'Información personal', 'descripcion' => 'Datos base del postulante', 'icono' => 'user'],
            3 => ['titulo' => 'Información general', 'descripcion' => 'Colegio y año de egreso', 'icono' => 'note-book'],
            4 => ['titulo' => 'Postulación', 'descripcion' => 'Sede, carrera y turno', 'icono' => 'school'],
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
        $this->resetPostulacionForm();
        $this->pagoForm = $this->formularioPagoBase();
        $this->resultadoPagoDetalleIds = [];
        $this->pagosSeleccionados = [];
        $this->busquedaPagoRealizada = false;
        $this->pagoValidado = false;
        $this->confirmacionDatos = false;
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

        if ($paso > 1 && ! $this->pagoValidado) {
            $this->dispatch('toast', message: 'Primero debe validar el pago del postulante.', type: 'warning');

            return;
        }

        if ($paso > 2 && $this->personaId === null) {
            $this->dispatch('toast', message: 'Primero debe guardar la informacion personal.', type: 'warning');

            return;
        }

        if ($paso > 3) {
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
