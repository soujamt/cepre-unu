<?php

use App\Enums\EstadoCatalogo;
use App\Enums\EstadoImportacionBancoDetalle;
use App\Enums\EstadoInscripcion;
use App\Enums\EstadoObligacionPago;
use App\Enums\EstadoProceso;
use App\Enums\EstadoRevisionExpediente;
use App\Enums\EstadoValidacionPago;
use App\Enums\SexoPersona;
use App\Enums\TipoLenguaMaternaPersona;
use App\Services\ReniecService;
use App\Models\ConvenioProceso;
use App\Models\EstadoCivil;
use App\Models\IdentidadEtnica;
use App\Models\ImportacionBancoDetalle;
use App\Models\Inscripcion;
use App\Models\InscripcionExpediente;
use App\Models\InscripcionObligacionPago;
use App\Models\Colegio;
use App\Models\LenguaExtranjera;
use App\Models\LenguaNativa;
use App\Models\Nacionalidad;
use App\Models\Pais;
use App\Models\Persona;
use App\Models\Proceso;
use App\Models\ProcesoOferta;
use App\Models\ReglaPago;
use App\Models\RequisitoExpediente;
use App\Models\TipoDiscapacidad;
use App\Models\TipoColegio;
use App\Models\TipoDocumento;
use App\Models\Ubigeo;
use App\Support\InscripcionPagoManager;
use App\Support\ProcesoInscripcionResolver;
use App\Traits\AuthorizesPermisos;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.app')]
#[Title('NUEVA INSCRIPCION')]
class extends Component
{
    use AuthorizesPermisos;

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
     *   nombre_archivo:string
     * }>
     */
    public array $expedientesForm = [];

    public ?int $personaId = null;

    public ?int $inscripcionId = null;

    public ?string $reniecMensaje = null;

    public string $reniecTipoMensaje = 'info';

    public ?string $origenDatosPersona = null;

    public string $ubigeoNacimientoLabel = '';

    public string $ubigeoDomicilioLabel = '';

    public string $colegioLabel = '';

    /**
     * @var array{disponible:bool,mensaje:string,proceso:?App\Models\Proceso}|null
     */
    protected ?array $contextoProcesoPostulacionCache = null;

    public function mount(): void
    {
        $this->autorizarPermiso('inscripcion', 'crear');
        $this->hidratarCatalogos();
        $this->pagoForm['fecha_pago'] = now()->subDay()->toDateString();
    }

    public function updatedPersonaFormIdTipoDocumento(): void
    {
        if ($this->origenDatosPersona !== null) {
            $this->reiniciarFormularioPorCambioIdentidad(
                (string) $this->personaForm['id_tipo_documento'],
                (string) $this->personaForm['numero_documento'],
            );
        }

        $this->limpiarEstadoReniec();
    }

    public function updatedPersonaFormNumeroDocumento(string $valor): void
    {
        if ($this->esTipoDocumentoDniSeleccionado()) {
            $this->personaForm['numero_documento'] = preg_replace('/\D+/', '', $valor) ?? '';
        }

        if ($this->origenDatosPersona !== null) {
            $this->reiniciarFormularioPorCambioIdentidad(
                (string) $this->personaForm['id_tipo_documento'],
                (string) $this->personaForm['numero_documento'],
            );
        }

        $this->limpiarEstadoReniec();
    }

    public function updatedPersonaFormSoloUnApellido(bool $valor): void
    {
        if ($valor) {
            $this->personaForm['apellido_materno'] = '';
        }
    }

    public function updatedPersonaFormTieneApellidoCasada(bool $valor): void
    {
        if (! $valor) {
            $this->personaForm['apellido_casada'] = '';
        }
    }

    public function updatedPersonaFormDiscapacidad(bool $valor): void
    {
        if (! $valor) {
            $this->personaForm['id_tipo_discapacidad'] = '';
        }
    }

    public function updatedPersonaFormTipoLenguaMaterna(string $valor): void
    {
        if ($valor === TipoLenguaMaternaPersona::Nativa->value) {
            $this->personaForm['id_lengua_extranjera'] = '';
        }

        if ($valor === TipoLenguaMaternaPersona::Extranjera->value) {
            $this->personaForm['id_lengua_nativa'] = '';
        }
    }

    public function updatedGeneralFormIdPaisColegio(): void
    {
        $this->generalForm['id_colegio'] = '';
        $this->generalForm['nombre_colegio_extranjero'] = '';
        $this->colegioLabel = '';
        $this->dispatch(
            'ui-select-search-filled',
            name: 'generalForm.id_colegio',
            value: '',
            label: '',
        );
    }

    public function updatedGeneralFormIdColegio(string $value): void
    {
        if ($value === '') {
            $this->colegioLabel = '';

            return;
        }

        $colegio = Colegio::query()->find($value);

        $this->colegioLabel = $colegio === null
            ? ''
            : "{$colegio->id_col} | {$colegio->nombre_col}";
    }

    public function updatedPostulacionFormIdModalidadAdmision(): void
    {
        $this->postulacionForm['id_lugar_inscripcion'] = '';
        $this->postulacionForm['id_turno'] = '';
        $this->postulacionForm['id_proceso_oferta'] = '';
        $this->postulacionForm['id_convenio_proceso'] = '';
    }

    public function updatedPostulacionFormIdLugarInscripcion(): void
    {
        $this->postulacionForm['id_turno'] = '';
        $this->postulacionForm['id_proceso_oferta'] = '';
        $this->postulacionForm['id_convenio_proceso'] = '';
    }

    public function updatedPostulacionFormIdTurno(): void
    {
        $this->postulacionForm['id_proceso_oferta'] = '';
        $this->postulacionForm['id_convenio_proceso'] = '';
    }

    public function updatedPagoFormIdReglaPago(): void
    {
        $this->limpiarBusquedaPago();
    }

    public function updatedPagoFormNumeroOperacion(string $valor): void
    {
        $this->pagoForm['numero_operacion'] = preg_replace('/\D+/', '', $valor) ?? '';
    }

    #[Computed]
    public function esTipoDocumentoDni(): bool
    {
        return $this->esTipoDocumentoDniSeleccionado();
    }

    #[Computed]
    public function puedeConsultarReniec(): bool
    {
        return $this->esTipoDocumentoDniSeleccionado() && strlen($this->personaForm['numero_documento']) === 8;
    }

    /**
     * @return array{disponible:bool,mensaje:string,proceso:?App\Models\Proceso}
     */
    #[Computed]
    public function contextoProcesoPostulacion(): array
    {
        return $this->contextoProcesoPostulacionCache
            ?? $this->refrescarContextoProcesoPostulacion();
    }

    #[Computed]
    public function procesoPostulacionActivo(): ?Proceso
    {
        return $this->contextoProcesoPostulacion()['proceso'];
    }

    #[Computed]
    public function ofertasProcesoDisponibles(): \Illuminate\Database\Eloquent\Collection
    {
        $proceso = $this->procesoPostulacionActivo();

        if ($proceso === null) {
            return new \Illuminate\Database\Eloquent\Collection();
        }

        return ProcesoOferta::query()
            ->with([
                'proceso.semestre',
                'ofertaBase.modalidadLugarInscripcion.modalidadAdmision',
                'ofertaBase.modalidadLugarInscripcion.lugarInscripcion.sede',
                'ofertaBase.sedeCarrera.sede',
                'ofertaBase.sedeCarrera.carrera',
                'turno',
            ])
            ->where('id_proceso', $proceso->id_pro)
            ->where('estado_pof', EstadoProceso::Habilitado->value)
            ->get();
    }

    #[Computed]
    public function modalidadesPostulacionDisponibles(): \Illuminate\Support\Collection
    {
        return $this->ofertasProcesoDisponibles()
            ->map(function (ProcesoOferta $oferta): array {
                return [
                    'id' => (string) $oferta->ofertaBase?->modalidadLugarInscripcion?->modalidadAdmision?->id_mad,
                    'nombre' => $oferta->ofertaBase?->modalidadLugarInscripcion?->modalidadAdmision?->nombre_mad ?? 'SIN MODALIDAD',
                ];
            })
            ->filter(fn (array $modalidad): bool => filled($modalidad['id']))
            ->unique('id')
            ->sortBy('nombre')
            ->values();
    }

    #[Computed]
    public function lugaresPostulacionDisponibles(): \Illuminate\Support\Collection
    {
        return $this->ofertasProcesoDisponibles()
            ->filter(fn (ProcesoOferta $oferta): bool => $this->postulacionForm['id_modalidad_admision'] !== ''
                && (string) $oferta->ofertaBase?->modalidadLugarInscripcion?->id_modalidad_admision === (string) $this->postulacionForm['id_modalidad_admision'])
            ->map(function (ProcesoOferta $oferta): array {
                return [
                    'id' => (string) $oferta->ofertaBase?->modalidadLugarInscripcion?->lugarInscripcion?->id_lin,
                    'nombre' => $oferta->ofertaBase?->modalidadLugarInscripcion?->lugarInscripcion?->nombre_lin ?? 'SIN LUGAR',
                ];
            })
            ->filter(fn (array $lugar): bool => filled($lugar['id']))
            ->unique('id')
            ->sortBy('nombre')
            ->values();
    }

    #[Computed]
    public function turnosPostulacionDisponibles(): \Illuminate\Support\Collection
    {
        return $this->ofertasProcesoDisponibles()
            ->filter(function (ProcesoOferta $oferta): bool {
                return $this->postulacionForm['id_modalidad_admision'] !== ''
                    && $this->postulacionForm['id_lugar_inscripcion'] !== ''
                    && (string) $oferta->ofertaBase?->modalidadLugarInscripcion?->id_modalidad_admision === (string) $this->postulacionForm['id_modalidad_admision']
                    && (string) $oferta->ofertaBase?->modalidadLugarInscripcion?->id_lugar_inscripcion === (string) $this->postulacionForm['id_lugar_inscripcion'];
            })
            ->map(function (ProcesoOferta $oferta): array {
                return [
                    'id' => (string) $oferta->turno?->id_tur,
                    'nombre' => $oferta->turno?->nombre_tur ?? 'SIN TURNO',
                ];
            })
            ->filter(fn (array $turno): bool => filled($turno['id']))
            ->unique('id')
            ->sortBy('nombre')
            ->values();
    }

    #[Computed]
    public function procesoOfertasPostulacionDisponibles(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->ofertasProcesoDisponibles()
            ->filter(function (ProcesoOferta $oferta): bool {
                return $this->postulacionForm['id_modalidad_admision'] !== ''
                    && $this->postulacionForm['id_lugar_inscripcion'] !== ''
                    && $this->postulacionForm['id_turno'] !== ''
                    && (string) $oferta->ofertaBase?->modalidadLugarInscripcion?->id_modalidad_admision === (string) $this->postulacionForm['id_modalidad_admision']
                    && (string) $oferta->ofertaBase?->modalidadLugarInscripcion?->id_lugar_inscripcion === (string) $this->postulacionForm['id_lugar_inscripcion']
                    && (string) $oferta->id_turno === (string) $this->postulacionForm['id_turno'];
            })
            ->values();
    }

    #[Computed]
    public function conveniosPostulacionDisponibles(): \Illuminate\Database\Eloquent\Collection
    {
        $proceso = $this->procesoPostulacionActivo();

        if ($proceso === null) {
            return new \Illuminate\Database\Eloquent\Collection();
        }

        $fechaReferencia = now()->toDateString();

        return ConvenioProceso::query()
            ->with('convenio')
            ->where('id_proceso', $proceso->id_pro)
            ->where('estado_cpr', EstadoCatalogo::Habilitado->value)
            ->whereDate('fecha_inicio_cpr', '<=', $fechaReferencia)
            ->whereDate('fecha_fin_cpr', '>=', $fechaReferencia)
            ->get();
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
                'procesoOferta.proceso.semestre',
                'procesoOferta.ofertaBase.modalidadLugarInscripcion.modalidadAdmision',
                'procesoOferta.ofertaBase.modalidadLugarInscripcion.lugarInscripcion',
                'procesoOferta.ofertaBase.sedeCarrera.carrera',
                'procesoOferta.turno',
                'convenioProceso.convenio',
            ])
            ->find($this->inscripcionId);
    }

    #[Computed]
    public function reglasPagoDisponibles(): \Illuminate\Database\Eloquent\Collection
    {
        $inscripcion = $this->inscripcionBorrador;
        $procesoId = $inscripcion?->procesoOferta?->id_proceso;

        if ($inscripcion === null || $procesoId === null) {
            return new \Illuminate\Database\Eloquent\Collection();
        }

        $modalidadId = $inscripcion->procesoOferta?->ofertaBase?->modalidadLugarInscripcion?->id_modalidad_admision;
        $convenioProcesoId = $inscripcion->id_convenio_proceso;
        $fechaReferencia = now()->toDateString();

        return ReglaPago::query()
            ->with([
                'conceptoPago',
                'modalidadAdmision',
                'convenioProceso.convenio',
            ])
            ->where('id_proceso', $procesoId)
            ->where('estado_rpa', EstadoCatalogo::Habilitado->value)
            ->whereDate('vigente_desde_rpa', '<=', $fechaReferencia)
            ->whereDate('vigente_hasta_rpa', '>=', $fechaReferencia)
            ->where(function ($query) use ($modalidadId): void {
                $query->whereNull('id_modalidad_admision');

                if ($modalidadId !== null) {
                    $query->orWhere('id_modalidad_admision', $modalidadId);
                }
            })
            ->where(function ($query) use ($convenioProcesoId): void {
                $query->whereNull('id_convenio_proceso');

                if ($convenioProcesoId !== null) {
                    $query->orWhere('id_convenio_proceso', $convenioProcesoId);
                }
            })
            ->orderBy('id_convenio_proceso')
            ->orderBy('monto_total_rpa')
            ->get();
    }

    #[Computed]
    public function reglaPagoSeleccionada(): ?ReglaPago
    {
        if ($this->pagoForm['id_regla_pago'] === '') {
            return null;
        }

        return $this->reglasPagoDisponibles()
            ->first(fn (ReglaPago $reglaPago): bool => (string) $reglaPago->id_rpa === (string) $this->pagoForm['id_regla_pago']);
    }

    #[Computed]
    public function obligacionPagoSeleccionada(): ?InscripcionObligacionPago
    {
        $inscripcion = $this->inscripcionBorrador;
        $reglaPago = $this->reglaPagoSeleccionada;

        if ($inscripcion === null || $reglaPago === null) {
            return null;
        }

        return InscripcionObligacionPago::query()
            ->with([
                'reglaPago.conceptoPago',
                'pagos.importacionBancoDetalle',
            ])
            ->where('id_inscripcion', $inscripcion->id_ins)
            ->where('id_regla_pago', $reglaPago->id_rpa)
            ->first();
    }

    #[Computed]
    public function detallesPagoEncontrados(): \Illuminate\Database\Eloquent\Collection
    {
        if ($this->resultadoPagoDetalleIds === []) {
            return ImportacionBancoDetalle::query()->whereRaw('1 = 0')->get();
        }

        return ImportacionBancoDetalle::query()
            ->whereIn('id_ibd', $this->resultadoPagoDetalleIds)
            ->orderByDesc('fecha_pago_ibd')
            ->orderByDesc('id_ibd')
            ->get();
    }

    #[Computed]
    public function detallePagoSeleccionado(): ?ImportacionBancoDetalle
    {
        if ($this->pagoForm['id_detalle_banco'] === '') {
            return null;
        }

        return ImportacionBancoDetalle::query()->find($this->pagoForm['id_detalle_banco']);
    }

    #[Computed]
    public function tienePagoValidadoWizard(): bool
    {
        $inscripcion = $this->inscripcionBorrador;

        if ($inscripcion === null) {
            return false;
        }

        return InscripcionObligacionPago::query()
            ->where('id_inscripcion', $inscripcion->id_ins)
            ->where(function ($query): void {
                $query->where('estado_iop', EstadoObligacionPago::Exonerado->value)
                    ->orWhereHas('pagos', function ($pagosQuery): void {
                        $pagosQuery->where('estado_validacion_ipa', EstadoValidacionPago::Verificado->value);
                    });
            })
            ->exists();
    }

    #[Computed]
    public function requisitosExpedienteWizard(): \Illuminate\Database\Eloquent\Collection
    {
        $inscripcion = $this->inscripcionBorrador;
        $procesoId = $inscripcion?->procesoOferta?->id_proceso;

        if ($inscripcion === null || $procesoId === null) {
            return new \Illuminate\Database\Eloquent\Collection();
        }

        return RequisitoExpediente::query()
            ->with('tipoExpediente')
            ->where('id_proceso', $procesoId)
            ->where('estado_rex', EstadoCatalogo::Habilitado->value)
            ->orderBy('id_tipo_expediente')
            ->get();
    }

    #[Computed]
    public function expedientesRegistradosWizard(): \Illuminate\Database\Eloquent\Collection
    {
        $inscripcion = $this->inscripcionBorrador;

        if ($inscripcion === null) {
            return new \Illuminate\Database\Eloquent\Collection();
        }

        return InscripcionExpediente::query()
            ->with('tipoExpediente')
            ->where('id_inscripcion', $inscripcion->id_ins)
            ->orderBy('id_tipo_expediente')
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'personaForm.id_tipo_documento' => ['required', 'integer', 'exists:tbl_tipo_documento,id_tdo'],
            'personaForm.numero_documento' => [
                'required',
                'string',
                'max:25',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $tipoDocumento = $this->tipoDocumentoSeleccionado();

                    if ($tipoDocumento === null || blank($value)) {
                        return;
                    }

                    $longitud = mb_strlen(trim((string) $value));

                    if ($tipoDocumento->longitud_minima_tdo !== null && $longitud < (int) $tipoDocumento->longitud_minima_tdo) {
                        $fail("El numero de documento debe tener al menos {$tipoDocumento->longitud_minima_tdo} caracteres.");

                        return;
                    }

                    if ($tipoDocumento->longitud_maxima_tdo !== null && $longitud > (int) $tipoDocumento->longitud_maxima_tdo) {
                        $fail("El numero de documento no debe superar {$tipoDocumento->longitud_maxima_tdo} caracteres.");
                    }
                },
            ],
            'personaForm.solo_un_apellido' => ['boolean'],
            'personaForm.tiene_apellido_casada' => ['boolean'],
            'personaForm.apellido_paterno' => ['required', 'string', 'max:255'],
            'personaForm.apellido_materno' => [
                'nullable',
                'string',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! $this->personaForm['solo_un_apellido'] && blank($value)) {
                        $fail('El segundo apellido es obligatorio cuando no se marca solo un apellido.');
                    }
                },
            ],
            'personaForm.apellido_casada' => [
                'nullable',
                'string',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($this->personaForm['tiene_apellido_casada'] && blank($value)) {
                        $fail('Debe registrar el apellido de casado(a).');
                    }
                },
            ],
            'personaForm.nombres' => ['required', 'string', 'max:255'],
            'personaForm.fecha_nacimiento' => ['required', 'date'],
            'personaForm.sexo' => ['required', 'in:'.collect(SexoPersona::cases())->pluck('value')->implode(',')],
            'personaForm.id_estado_civil' => ['required', 'integer', 'exists:tbl_estado_civil,id_eci'],
            'personaForm.id_pais_nacimiento' => ['required', 'integer', 'exists:tbl_pais,id_pai'],
            'personaForm.id_nacionalidad' => ['required', 'integer', 'exists:tbl_nacionalidad,id_nac'],
            'personaForm.id_ubigeo_nacimiento' => ['required', 'string', 'exists:tbl_ubigeo,id_ubi'],
            'personaForm.id_ubigeo_domicilio' => ['required', 'string', 'exists:tbl_ubigeo,id_ubi'],
            'personaForm.direccion' => ['required', 'string', 'max:255'],
            'personaForm.celular' => ['nullable', 'string', 'max:20'],
            'personaForm.telefono' => ['nullable', 'string', 'max:20'],
            'personaForm.correo_electronico' => ['nullable', 'email', 'max:255'],
            'personaForm.discapacidad' => ['boolean'],
            'personaForm.id_tipo_discapacidad' => [
                'nullable',
                'integer',
                'exists:tbl_tipo_discapacidad,id_tdi',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($this->personaForm['discapacidad'] && blank($value)) {
                        $fail('Debe seleccionar el tipo de discapacidad.');
                    }
                },
            ],
            'personaForm.tipo_lengua_materna' => ['required', 'in:'.collect(TipoLenguaMaternaPersona::cases())->pluck('value')->implode(',')],
            'personaForm.id_identidad_etnica' => ['required', 'integer', 'exists:tbl_identidad_etnica,id_iet'],
            'personaForm.id_lengua_nativa' => [
                'nullable',
                'integer',
                'exists:tbl_lengua_nativa,id_lna',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($this->personaForm['tipo_lengua_materna'] === TipoLenguaMaternaPersona::Nativa->value && blank($value)) {
                        $fail('Debe seleccionar la lengua nativa.');
                    }
                },
            ],
            'personaForm.id_lengua_extranjera' => [
                'nullable',
                'integer',
                'exists:tbl_lengua_extranjera,id_lex',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($this->personaForm['tipo_lengua_materna'] === TipoLenguaMaternaPersona::Extranjera->value && blank($value)) {
                        $fail('Debe seleccionar la lengua extranjera.');
                    }
                },
            ],
            'personaForm.observacion' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function rulesPasoGeneral(): array
    {
        return [
            'generalForm.anio_graduacion' => ['required', 'integer', 'digits:4', 'min:1950', 'max:'.((int) now()->format('Y'))],
            'generalForm.id_pais_colegio' => ['required', 'integer', 'exists:tbl_pais,id_pai'],
            'generalForm.id_tipo_colegio' => ['required', 'integer', 'exists:tbl_tipo_colegio,id_tco'],
            'generalForm.id_colegio' => [
                'nullable',
                'string',
                'exists:tbl_colegio,id_col',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($this->esPaisPeruSeleccionado() && blank($value)) {
                        $fail('Debe seleccionar el colegio de procedencia.');
                    }
                },
            ],
            'generalForm.nombre_colegio_extranjero' => [
                'nullable',
                'string',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! $this->esPaisPeruSeleccionado() && blank($value)) {
                        $fail('Debe registrar el nombre del colegio extranjero.');
                    }
                },
            ],
            'generalForm.veces_postula_unu' => ['required', 'integer', 'min:0', 'max:99'],
            'generalForm.veces_postula_otras' => ['required', 'integer', 'min:0', 'max:99'],
            'generalForm.observacion' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'personaForm.id_tipo_documento.required' => 'El tipo de documento es obligatorio.',
            'personaForm.numero_documento.required' => 'El numero de documento es obligatorio.',
            'personaForm.apellido_paterno.required' => 'El primer apellido es obligatorio.',
            'personaForm.nombres.required' => 'Los nombres son obligatorios.',
            'personaForm.fecha_nacimiento.required' => 'La fecha de nacimiento es obligatoria.',
            'personaForm.sexo.required' => 'El sexo es obligatorio.',
            'personaForm.id_estado_civil.required' => 'El estado civil es obligatorio.',
            'personaForm.id_pais_nacimiento.required' => 'El pais de nacimiento es obligatorio.',
            'personaForm.id_nacionalidad.required' => 'La nacionalidad es obligatoria.',
            'personaForm.id_ubigeo_nacimiento.required' => 'El ubigeo de nacimiento es obligatorio.',
            'personaForm.id_ubigeo_domicilio.required' => 'El ubigeo de domicilio es obligatorio.',
            'personaForm.direccion.required' => 'La direccion es obligatoria.',
            'personaForm.tipo_lengua_materna.required' => 'El tipo de lengua materna es obligatorio.',
            'personaForm.id_identidad_etnica.required' => 'La identidad etnica es obligatoria.',
            'personaForm.correo_electronico.email' => 'El correo electronico debe ser valido.',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messagesPasoGeneral(): array
    {
        return [
            'generalForm.anio_graduacion.required' => 'El ano de graduacion es obligatorio.',
            'generalForm.anio_graduacion.integer' => 'El ano de graduacion debe ser numerico.',
            'generalForm.id_pais_colegio.required' => 'El pais del colegio es obligatorio.',
            'generalForm.id_tipo_colegio.required' => 'El tipo de colegio es obligatorio.',
            'generalForm.veces_postula_unu.required' => 'Debe indicar cuantas veces postulo a la UNU.',
            'generalForm.veces_postula_otras.required' => 'Debe indicar cuantas veces postulo a otras universidades.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function rulesPasoPostulacion(): array
    {
        return [
            'postulacionForm.id_modalidad_admision' => [
                'required',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $valido = $this->modalidadesPostulacionDisponibles()
                        ->contains(fn (array $modalidad): bool => $modalidad['id'] === (string) $value);

                    if (! $valido) {
                        $fail('La modalidad seleccionada no es valida para el proceso activo.');
                    }
                },
            ],
            'postulacionForm.id_lugar_inscripcion' => [
                'required',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $valido = $this->lugaresPostulacionDisponibles()
                        ->contains(fn (array $lugar): bool => $lugar['id'] === (string) $value);

                    if (! $valido) {
                        $fail('El lugar de inscripcion seleccionado no es valido.');
                    }
                },
            ],
            'postulacionForm.id_turno' => [
                'required',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $valido = $this->turnosPostulacionDisponibles()
                        ->contains(fn (array $turno): bool => $turno['id'] === (string) $value);

                    if (! $valido) {
                        $fail('El turno seleccionado no es valido.');
                    }
                },
            ],
            'postulacionForm.id_proceso_oferta' => [
                'required',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $valido = $this->procesoOfertasPostulacionDisponibles()
                        ->contains(fn (ProcesoOferta $oferta): bool => (string) $oferta->id_pof === (string) $value);

                    if (! $valido) {
                        $fail('La oferta seleccionada no es valida para la postulacion.');
                    }
                },
            ],
            'postulacionForm.id_convenio_proceso' => [
                'nullable',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (blank($value)) {
                        return;
                    }

                    $valido = $this->conveniosPostulacionDisponibles()
                        ->contains(fn (ConvenioProceso $convenioProceso): bool => (string) $convenioProceso->id_cpr === (string) $value);

                    if (! $valido) {
                        $fail('El convenio seleccionado no es valido para el proceso activo.');
                    }
                },
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messagesPasoPostulacion(): array
    {
        return [
            'postulacionForm.id_modalidad_admision.required' => 'La modalidad es obligatoria.',
            'postulacionForm.id_lugar_inscripcion.required' => 'El lugar de inscripcion es obligatorio.',
            'postulacionForm.id_turno.required' => 'El turno es obligatorio.',
            'postulacionForm.id_proceso_oferta.required' => 'La oferta es obligatoria.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function rulesPasoPagoBusqueda(): array
    {
        return [
            'pagoForm.id_regla_pago' => [
                'required',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $valido = $this->reglasPagoDisponibles()
                        ->contains(fn (ReglaPago $reglaPago): bool => (string) $reglaPago->id_rpa === (string) $value);

                    if (! $valido) {
                        $fail('La regla de pago seleccionada no es valida para esta inscripcion.');
                    }
                },
            ],
            'pagoForm.numero_documento' => ['required', 'string'],
            'pagoForm.numero_operacion' => ['required', 'string'],
            'pagoForm.fecha_pago' => ['required', 'date'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function rulesPasoPagoConfirmacion(): array
    {
        return array_merge($this->rulesPasoPagoBusqueda(), [
            'pagoForm.id_detalle_banco' => ['required', 'integer'],
        ]);
    }

    /**
     * @return array<string, string>
     */
    protected function messagesPasoPago(): array
    {
        return [
            'pagoForm.id_regla_pago.required' => 'Debe seleccionar una regla de pago.',
            'pagoForm.numero_documento.required' => 'El numero de documento es obligatorio.',
            'pagoForm.numero_operacion.required' => 'La operacion es obligatoria para buscar pagos.',
            'pagoForm.fecha_pago.required' => 'La fecha de pago es obligatoria.',
            'pagoForm.id_detalle_banco.required' => 'Debe seleccionar un pago importado antes de continuar.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function rulesPasoExpedientes(): array
    {
        $rules = [];

        foreach ($this->expedientesForm as $indice => $expediente) {
            $rules["expedientesForm.{$indice}.id_tipo_expediente"] = ['required', 'integer'];
            $rules["expedientesForm.{$indice}.estado_revision"] = ['required', 'in:'.collect(EstadoRevisionExpediente::cases())->pluck('value')->implode(',')];
            $rules["expedientesForm.{$indice}.observacion"] = ['nullable', 'string', 'max:255'];
            $rules["expedientesForm.{$indice}.nombre_archivo"] = ['nullable', 'string', 'max:255'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    protected function messagesPasoExpedientes(): array
    {
        return [
            'expedientesForm.*.estado_revision.required' => 'Debe seleccionar el estado de revision del expediente.',
            'expedientesForm.*.observacion.max' => 'La observacion no debe superar los 255 caracteres.',
            'expedientesForm.*.nombre_archivo.max' => 'La referencia del archivo no debe superar los 255 caracteres.',
        ];
    }

    #[Computed]
    public function pasos(): array
    {
        return [
            1 => ['titulo' => 'Informacion personal', 'descripcion' => 'Datos base del postulante', 'icono' => 'user'],
            2 => ['titulo' => 'Informacion general', 'descripcion' => 'Datos complementarios', 'icono' => 'notebook'],
            3 => ['titulo' => 'Postulacion', 'descripcion' => 'Proceso, oferta y colegio', 'icono' => 'graduation-cap'],
            4 => ['titulo' => 'Pago', 'descripcion' => 'Validacion del pago bancario', 'icono' => 'credit-card'],
            5 => ['titulo' => 'Expedientes', 'descripcion' => 'Carga y revision de documentos', 'icono' => 'folder-open'],
        ];
    }

    #[Computed]
    public function tiposDocumento(): \Illuminate\Database\Eloquent\Collection
    {
        return new \Illuminate\Database\Eloquent\Collection(
            collect($this->tiposDocumentoCatalogo)
                ->map(fn (array $tipoDocumento): TipoDocumento => tap(new TipoDocumento(), fn (TipoDocumento $model) => $model->forceFill($tipoDocumento)))
                ->all(),
        );
    }

    #[Computed]
    public function estadosCiviles(): \Illuminate\Database\Eloquent\Collection
    {
        return new \Illuminate\Database\Eloquent\Collection(
            collect($this->estadosCivilesCatalogo)
                ->map(fn (array $estadoCivil): EstadoCivil => tap(new EstadoCivil(), fn (EstadoCivil $model) => $model->forceFill($estadoCivil)))
                ->all(),
        );
    }

    #[Computed]
    public function paises(): \Illuminate\Database\Eloquent\Collection
    {
        return new \Illuminate\Database\Eloquent\Collection(
            collect($this->paisesCatalogo)
                ->map(fn (array $pais): Pais => tap(new Pais(), fn (Pais $model) => $model->forceFill($pais)))
                ->all(),
        );
    }

    #[Computed]
    public function nacionalidades(): \Illuminate\Database\Eloquent\Collection
    {
        return new \Illuminate\Database\Eloquent\Collection(
            collect($this->nacionalidadesCatalogo)
                ->map(fn (array $nacionalidad): Nacionalidad => tap(new Nacionalidad(), fn (Nacionalidad $model) => $model->forceFill($nacionalidad)))
                ->all(),
        );
    }

    #[Computed]
    public function identidadesEtnicas(): \Illuminate\Database\Eloquent\Collection
    {
        return new \Illuminate\Database\Eloquent\Collection(
            collect($this->identidadesEtnicasCatalogo)
                ->map(fn (array $identidadEtnica): IdentidadEtnica => tap(new IdentidadEtnica(), fn (IdentidadEtnica $model) => $model->forceFill($identidadEtnica)))
                ->all(),
        );
    }

    #[Computed]
    public function lenguasNativas(): \Illuminate\Database\Eloquent\Collection
    {
        return new \Illuminate\Database\Eloquent\Collection(
            collect($this->lenguasNativasCatalogo)
                ->map(fn (array $lenguaNativa): LenguaNativa => tap(new LenguaNativa(), fn (LenguaNativa $model) => $model->forceFill($lenguaNativa)))
                ->all(),
        );
    }

    #[Computed]
    public function lenguasExtranjeras(): \Illuminate\Database\Eloquent\Collection
    {
        return new \Illuminate\Database\Eloquent\Collection(
            collect($this->lenguasExtranjerasCatalogo)
                ->map(fn (array $lenguaExtranjera): LenguaExtranjera => tap(new LenguaExtranjera(), fn (LenguaExtranjera $model) => $model->forceFill($lenguaExtranjera)))
                ->all(),
        );
    }

    #[Computed]
    public function tiposDiscapacidad(): \Illuminate\Database\Eloquent\Collection
    {
        return new \Illuminate\Database\Eloquent\Collection(
            collect($this->tiposDiscapacidadCatalogo)
                ->map(fn (array $tipoDiscapacidad): TipoDiscapacidad => tap(new TipoDiscapacidad(), fn (TipoDiscapacidad $model) => $model->forceFill($tipoDiscapacidad)))
                ->all(),
        );
    }

    #[Computed]
    public function tiposColegio(): \Illuminate\Database\Eloquent\Collection
    {
        return new \Illuminate\Database\Eloquent\Collection(
            collect($this->tiposColegioCatalogo)
                ->map(fn (array $tipoColegio): TipoColegio => tap(new TipoColegio(), fn (TipoColegio $model) => $model->forceFill($tipoColegio)))
                ->all(),
        );
    }

    #[Computed]
    public function paisPeruId(): string
    {
        $paisPeru = $this->paises()
            ->first(function (Pais $pais): bool {
                $codigo = trim((string) ($pais->codigo_pai ?? ''));
                $nombre = Str::of((string) ($pais->nombre_pai ?? ''))
                    ->ascii()
                    ->upper()
                    ->trim()
                    ->value();

                return in_array($codigo, ['1', 'PE'], true)
                    || $nombre === 'PERU';
            });

        return (string) ($paisPeru?->id_pai ?? '');
    }

    public function guardarPasoPersonal(): void
    {
        $this->normalizarEstadoPersonaForm();
        $data = $this->validate();

        try {
            DB::transaction(function () use ($data): void {
                $payload = [
                    'id_tipo_documento' => (int) $data['personaForm']['id_tipo_documento'],
                    'numero_documento_per' => trim($data['personaForm']['numero_documento']),
                    'solo_un_apellido_per' => $data['personaForm']['solo_un_apellido'],
                    'apellido_paterno_per' => $this->mayusculas($data['personaForm']['apellido_paterno']),
                    'apellido_materno_per' => $data['personaForm']['solo_un_apellido']
                        ? null
                        : $this->nullableUpper($data['personaForm']['apellido_materno']),
                    'apellido_casada_per' => $data['personaForm']['tiene_apellido_casada']
                        ? $this->nullableUpper($data['personaForm']['apellido_casada'])
                        : null,
                    'nombres_per' => $this->mayusculas($data['personaForm']['nombres']),
                    'fecha_nacimiento_per' => $data['personaForm']['fecha_nacimiento'],
                    'sexo_per' => $data['personaForm']['sexo'],
                    'id_estado_civil' => (int) $data['personaForm']['id_estado_civil'],
                    'id_pais_nacimiento' => (int) $data['personaForm']['id_pais_nacimiento'],
                    'id_nacionalidad' => (int) $data['personaForm']['id_nacionalidad'],
                    'id_ubigeo_nacimiento' => $data['personaForm']['id_ubigeo_nacimiento'],
                    'id_ubigeo_domicilio' => $data['personaForm']['id_ubigeo_domicilio'],
                    'direccion_per' => $this->nullableUpper($data['personaForm']['direccion']),
                    'celular_per' => $this->nullableTrim($data['personaForm']['celular']),
                    'telefono_per' => $this->nullableTrim($data['personaForm']['telefono']),
                    'correo_electronico_per' => $this->nullableLower($data['personaForm']['correo_electronico']),
                    'discapacidad_per' => $data['personaForm']['discapacidad'],
                    'id_tipo_discapacidad' => $data['personaForm']['discapacidad']
                        ? (int) $data['personaForm']['id_tipo_discapacidad']
                        : null,
                    'tipo_lengua_materna_per' => $data['personaForm']['tipo_lengua_materna'],
                    'id_identidad_etnica' => (int) $data['personaForm']['id_identidad_etnica'],
                    'id_lengua_nativa' => $data['personaForm']['tipo_lengua_materna'] === TipoLenguaMaternaPersona::Nativa->value
                        ? (int) $data['personaForm']['id_lengua_nativa']
                        : null,
                    'id_lengua_extranjera' => $data['personaForm']['tipo_lengua_materna'] === TipoLenguaMaternaPersona::Extranjera->value
                        ? (int) $data['personaForm']['id_lengua_extranjera']
                        : null,
                    'observacion_per' => $this->nullableUpper($data['personaForm']['observacion']),
                    'estado_per' => EstadoCatalogo::Habilitado->value,
                ];

                $persona = Persona::query()->updateOrCreate(
                    [
                        'id_tipo_documento' => (int) $data['personaForm']['id_tipo_documento'],
                        'numero_documento_per' => trim($data['personaForm']['numero_documento']),
                    ],
                    $payload,
                );

                $this->personaId = $persona->id_per;
            });
        } catch (\Throwable $throwable) {
            report($throwable);
            $this->dispatch('toast', message: 'No se pudo guardar la informacion personal.', type: 'error');

            return;
        }

        $this->pasoActual = 2;
        $this->dispatch('toast', message: 'Informacion personal guardada correctamente.', type: 'success');
    }

    public function buscarDatosPersona(): void
    {
        $tipoDocumento = $this->tipoDocumentoSeleccionado();

        if ($tipoDocumento === null) {
            $this->reniecTipoMensaje = 'warning';
            $this->reniecMensaje = 'Seleccione el tipo de documento antes de buscar datos.';

            return;
        }

        $numeroDocumento = trim((string) $this->personaForm['numero_documento']);

        if ($this->esTipoDocumentoDniSeleccionado()) {
            $numeroDocumento = preg_replace('/\D+/', '', $numeroDocumento) ?? '';
            $this->personaForm['numero_documento'] = $numeroDocumento;
        }

        if ($numeroDocumento === '') {
            $this->reniecTipoMensaje = 'warning';
            $this->reniecMensaje = 'Ingrese el numero de documento antes de buscar datos.';

            return;
        }

        $persona = Persona::query()
            ->where('id_tipo_documento', (int) $this->personaForm['id_tipo_documento'])
            ->where('numero_documento_per', $numeroDocumento)
            ->first();

        if ($persona !== null) {
            $this->cargarPersonaDesdeSistema($persona);
            $this->reniecTipoMensaje = 'success';
            $this->reniecMensaje = 'Se encontraron datos registrados en el sistema. Puede revisarlos y actualizarlos si lo necesita.';

            return;
        }

        if (! $this->esTipoDocumentoDniSeleccionado()) {
            $this->reniecTipoMensaje = 'warning';
            $this->reniecMensaje = 'No se encontraron datos registrados. Complete el formulario manualmente.';

            return;
        }

        if (strlen($numeroDocumento) !== 8) {
            $this->reniecTipoMensaje = 'warning';
            $this->reniecMensaje = 'Ingrese un DNI valido de 8 digitos para consultar RENIEC.';

            return;
        }

        $resultado = app(ReniecService::class)->consultar($numeroDocumento);

        if (! ($resultado['success'] ?? false)) {
            $this->reniecTipoMensaje = 'error';
            $this->reniecMensaje = (string) ($resultado['message'] ?? 'No se pudo consultar RENIEC.');

            return;
        }

        $data = $resultado['data'] ?? [];

        $this->cargarPersonaDesdeReniec($data, $numeroDocumento);

        $this->reniecTipoMensaje = 'success';
        $this->reniecMensaje = 'Datos recuperados desde RENIEC. Puede ajustarlos manualmente si lo necesita.';
    }

    public function guardarPasoGeneral(): void
    {
        $this->normalizarEstadoGeneralForm();

        $data = $this->validate(
            $this->rulesPasoGeneral(),
            $this->messagesPasoGeneral(),
        );

        $this->generalForm = array_merge($this->generalForm, $data['generalForm']);
        $this->pasoActual = 3;

        $contextoProceso = $this->refrescarContextoProcesoPostulacion();

        if ($contextoProceso['disponible']) {
            $this->dispatch('toast', message: 'Informacion general guardada correctamente.', type: 'success');

            return;
        }

        $this->dispatch(
            'toast',
            message: 'Informacion general guardada. '.$contextoProceso['mensaje'],
            type: 'warning',
        );
    }

    public function guardarPasoPostulacion(): void
    {
        $contextoProceso = $this->refrescarContextoProcesoPostulacion();

        if (! $contextoProceso['disponible']) {
            $this->dispatch('toast', message: $contextoProceso['mensaje'], type: 'warning');

            return;
        }

        // Verificar inscripcion duplicada en el mismo proceso (excepto edicion actual)
        $proceso = $contextoProceso['proceso'];

        $inscripcionExistente = Inscripcion::query()
            ->with(['procesoOferta.ofertaBase.sedeCarrera.carrera'])
            ->where('id_persona', $this->personaId)
            ->whereNotIn('estado_ins', [EstadoInscripcion::Anulado->value])
            ->whereHas('procesoOferta', fn ($q) => $q->where('id_proceso', $proceso->id_pro))
            ->when($this->inscripcionId, fn ($q) => $q->where('id_ins', '!=', $this->inscripcionId))
            ->first();

        if ($inscripcionExistente !== null) {
            $carrera = $inscripcionExistente->procesoOferta?->ofertaBase?->sedeCarrera?->carrera?->nombre_car ?? 'otra carrera';
            $codigo = (string) $inscripcionExistente->codigo_ins;

            $this->dispatch(
                'alert',
                type: 'question',
                title: 'Postulante ya inscrito',
                message: "El postulante ya tiene una inscripcion en este proceso para \"{$carrera}\" (codigo: {$codigo}). ¿Desea continuar de todas formas?",
                showCancel: true,
                showConfirm: true,
                confirmText: 'Continuar',
                cancelText: 'Cancelar',
                confirmEvent: 'duplicado-confirmado',
                cancelEvent: 'duplicado-cancelado',
            );

            return;
        }

        $data = $this->validate(
            $this->rulesPasoPostulacion(),
            $this->messagesPasoPostulacion(),
        );

        $this->persistirPostulacion($data);
    }

    public function irPasoAnterior(): void
    {
        if ($this->pasoActual > 1) {
            $this->pasoActual--;
        }
    }

    public function confirmarGuardadoDuplicado(): void
    {
        $contextoProceso = $this->refrescarContextoProcesoPostulacion();

        if (! $contextoProceso['disponible']) {
            $this->dispatch('toast', message: $contextoProceso['mensaje'], type: 'warning');

            return;
        }

        $data = $this->validate(
            $this->rulesPasoPostulacion(),
            $this->messagesPasoPostulacion(),
        );

        $this->persistirPostulacion($data);
    }

    public function buscarPagosPasoPago(InscripcionPagoManager $manager): void
    {
        $this->prepararPasoPago();

        if ($this->inscripcionId === null) {
            $this->dispatch('toast', message: 'Primero debe guardar la postulacion antes de buscar pagos.', type: 'warning');

            return;
        }

        $data = $this->validate(
            $this->rulesPasoPagoBusqueda(),
            $this->messagesPasoPago(),
        );

        $this->resultadoPagoDetalleIds = $manager
            ->buscarDetallesDisponibles(
                $data['pagoForm']['numero_documento'],
                $data['pagoForm']['numero_operacion'],
                $data['pagoForm']['fecha_pago'],
            )
            ->pluck('id_ibd')
            ->map(fn (int|string $id): int => (int) $id)
            ->all();

        $this->pagoForm['id_detalle_banco'] = '';
        $this->busquedaPagoRealizada = true;
    }

    public function seleccionarDetallePago(int $idDetalle): void
    {
        $this->pagoForm['id_detalle_banco'] = (string) $idDetalle;
    }

    public function confirmarPasoPago(InscripcionPagoManager $manager): void
    {
        $this->prepararPasoPago();

        if ($this->inscripcionId === null) {
            $this->dispatch('toast', message: 'Primero debe guardar la postulacion antes de validar pagos.', type: 'warning');

            return;
        }

        $data = $this->validate(
            $this->rulesPasoPagoConfirmacion(),
            $this->messagesPasoPago(),
        );

        $detalle = ImportacionBancoDetalle::query()->find($data['pagoForm']['id_detalle_banco']);
        $reglaPago = $this->reglaPagoSeleccionada;

        if ($detalle === null || $reglaPago === null) {
            $this->dispatch('toast', message: 'No se pudo cargar el pago o la regla seleccionada.', type: 'error');

            return;
        }

        $obligacion = $this->resolverObligacionPagoSeleccionada($reglaPago);

        try {
            $manager->asociarDetalleAObligacion($obligacion, $detalle);
        } catch (\DomainException $exception) {
            $this->dispatch('toast', message: $exception->getMessage(), type: 'error');

            return;
        } catch (\Throwable $throwable) {
            report($throwable);
            $this->dispatch('toast', message: 'No se pudo validar el pago seleccionado.', type: 'error');

            return;
        }

        unset($this->obligacionPagoSeleccionada, $this->detallePagoSeleccionado, $this->detallesPagoEncontrados, $this->tienePagoValidadoWizard);

        $this->limpiarBusquedaPago();
        $this->pasoActual = 5;
        $this->inicializarExpedientesForm();
        $this->dispatch('toast', message: 'Pago validado correctamente. Puede continuar con los expedientes.', type: 'success');
    }

    public function guardarPasoExpedientes(): void
    {
        $inscripcion = $this->inscripcionBorrador;

        if ($inscripcion === null) {
            $this->dispatch('toast', message: 'No existe una inscripcion borrador para registrar expedientes.', type: 'warning');

            return;
        }

        if ($this->expedientesForm === []) {
            $this->inicializarExpedientesForm();
        }
        $data = $this->validate(
            $this->rulesPasoExpedientes(),
            $this->messagesPasoExpedientes(),
        );

        try {
            DB::transaction(function () use ($inscripcion, $data): void {
                foreach ($data['expedientesForm'] as $expediente) {
                    $payload = [
                        'archivo_url_iex' => null,
                        'nombre_archivo_iex' => $this->nullableTrim($expediente['nombre_archivo']),
                        'estado_revision_iex' => $expediente['estado_revision'],
                        'observacion_iex' => $this->nullableUpper($expediente['observacion']),
                        'revisado_en' => now(),
                        'id_usuario_revisor' => Auth::id(),
                    ];

                    InscripcionExpediente::query()->updateOrCreate(
                        [
                            'id_inscripcion' => $inscripcion->id_ins,
                            'id_tipo_expediente' => (int) $expediente['id_tipo_expediente'],
                        ],
                        $payload,
                    );
                }

                $requisitosObligatorios = $this->requisitosExpedienteWizard()
                    ->where('obligatorio_rex', true)
                    ->pluck('id_tipo_expediente')
                    ->map(fn (int|string $id): int => (int) $id)
                    ->all();

                $estadosAprobadosFormulario = collect($data['expedientesForm'])
                    ->filter(function (array $expediente) use ($requisitosObligatorios): bool {
                        return in_array((int) $expediente['id_tipo_expediente'], $requisitosObligatorios, true)
                            && $expediente['estado_revision'] === EstadoRevisionExpediente::Aprobado->value;
                    })
                    ->count();

                $todosAprobados = $requisitosObligatorios !== []
                    && $estadosAprobadosFormulario === count($requisitosObligatorios);

                $inscripcion->update([
                    'estado_ins' => $todosAprobados ? EstadoInscripcion::Activo->value : EstadoInscripcion::Pendiente->value,
                ]);
            });
        } catch (\Throwable $throwable) {
            report($throwable);
            $this->dispatch('toast', message: 'No se pudieron guardar los expedientes. Intente nuevamente.', type: 'error');

            return;
        }

        unset($this->inscripcionBorrador, $this->expedientesRegistradosWizard);

        $mensaje = $inscripcion->fresh()?->estado_ins === EstadoInscripcion::Activo
            ? 'Expedientes guardados correctamente. La inscripcion quedo activa.'
            : 'Expedientes guardados correctamente. La inscripcion permanece pendiente hasta completar la revision.';

        $this->dispatch('toast', message: $mensaje, type: 'success');
        $this->redirect(route('inscripciones.index'), navigate: true);
    }

    public function cancelarDuplicadoPostulacion(): void
    {
        $this->resetPostulacionForm();
        $this->resetValidation([
            'postulacionForm.id_modalidad_admision',
            'postulacionForm.id_lugar_inscripcion',
            'postulacionForm.id_turno',
            'postulacionForm.id_proceso_oferta',
            'postulacionForm.id_convenio_proceso',
        ]);
        $this->pasoActual = 3;

        $this->dispatch(
            'toast',
            message: 'Se cancelo la continuidad de la postulacion duplicada. Puede seleccionar otra oferta.',
            type: 'info',
        );
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
            } catch (\Illuminate\Validation\ValidationException $exception) {
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

    /**
     * @param  array<string, mixed>  $data
     */
    private function persistirPostulacion(array $data): void
    {
        try {
            DB::transaction(function () use ($data): void {
                $payload = [
                    'id_persona' => $this->personaId,
                    'id_proceso_oferta' => (int) $data['postulacionForm']['id_proceso_oferta'],
                    'id_convenio_proceso' => filled($data['postulacionForm']['id_convenio_proceso'])
                        ? (int) $data['postulacionForm']['id_convenio_proceso']
                        : null,
                    'fecha_inscripcion_ins' => now(),
                    'estado_ins' => EstadoInscripcion::Pendiente->value,
                    'anio_graduacion_ins' => (int) $this->generalForm['anio_graduacion'],
                    'id_pais_colegio' => (int) $this->generalForm['id_pais_colegio'],
                    'id_colegio' => filled($this->generalForm['id_colegio']) ? $this->generalForm['id_colegio'] : null,
                    'nombre_colegio_extranjero_ins' => $this->nullableUpper($this->generalForm['nombre_colegio_extranjero']),
                    'id_tipo_colegio' => (int) $this->generalForm['id_tipo_colegio'],
                    'veces_postula_unu_ins' => (int) $this->generalForm['veces_postula_unu'],
                    'veces_postula_otras_ins' => (int) $this->generalForm['veces_postula_otras'],
                    'observacion_ins' => $this->nullableUpper($this->generalForm['observacion']),
                ];

                if ($this->inscripcionId !== null) {
                    $inscripcion = Inscripcion::query()->findOrFail($this->inscripcionId);
                    $inscripcion->update($payload);
                } else {
                    $inscripcion = Inscripcion::query()->firstOrNew([
                        'id_persona' => $this->personaId,
                        'id_proceso_oferta' => (int) $data['postulacionForm']['id_proceso_oferta'],
                    ]);

                    $inscripcion->fill($payload);

                    if (! $inscripcion->exists) {
                        $inscripcion->codigo_ins = 'TMP-'.Str::upper(Str::random(10));
                    }

                    $inscripcion->save();

                    if (Str::startsWith((string) $inscripcion->codigo_ins, 'TMP-')) {
                        $inscripcion->update([
                            'codigo_ins' => $this->generarCodigoInscripcion($inscripcion),
                        ]);
                    }
                }

                $this->inscripcionId = $inscripcion->id_ins;
            });
        } catch (\Throwable $throwable) {
            report($throwable);
            $this->dispatch('toast', message: 'No se pudo guardar la postulacion. Intente nuevamente.', type: 'error');

            return;
        }

        $this->pasoActual = 4;
        $this->prepararPasoPago();
        $this->dispatch('toast', message: 'Postulacion guardada correctamente.', type: 'success');
    }

    public function descripcionReglaPago(ReglaPago $reglaPago): string
    {
        $partes = [
            $reglaPago->conceptoPago?->nombre_cpg ?? 'REGLA DE PAGO',
            'S/ '.number_format((float) $reglaPago->monto_total_rpa, 2),
        ];

        if ($reglaPago->permite_cuotas_rpa) {
            $partes[] = 'hasta '.$reglaPago->numero_maximo_cuotas_rpa.' cuotas';
        } else {
            $partes[] = 'pago unico';
        }

        return implode(' | ', $partes);
    }

    public function resumenDetallePago(ImportacionBancoDetalle $detalle): string
    {
        return collect([
            $detalle->numero_documento_ibd ?: 'SIN DOCUMENTO',
            $detalle->numero_operacion_visible_ibd ?: $detalle->numero_operacion_ibd ?: 'SIN OPERACION',
            $detalle->fecha_pago_ibd?->format('d/m/Y'),
            'S/ '.number_format((float) $detalle->monto_ibd, 2),
        ])->filter()->implode(' | ');
    }

    public function estadosRevisionExpediente(): array
    {
        return EstadoRevisionExpediente::cases();
    }

    private function mayusculas(string $valor): string
    {
        return mb_strtoupper(trim($valor), 'UTF-8');
    }

    private function esTipoDocumentoDniSeleccionado(): bool
    {
        $tipoDocumento = $this->tipoDocumentoSeleccionado();

        if ($tipoDocumento === null) {
            return false;
        }

        $codigo = trim((string) $tipoDocumento->codigo_tdo);
        $nombre = mb_strtoupper(trim((string) $tipoDocumento->nombre_tdo), 'UTF-8');
        $longitudMinima = (int) ($tipoDocumento->longitud_minima_tdo ?? 0);
        $longitudMaxima = (int) ($tipoDocumento->longitud_maxima_tdo ?? 0);

        return in_array($codigo, ['1', 'DNI'], true)
            || ($longitudMinima === 8
                && $longitudMaxima === 8
                && str_contains($nombre, 'IDENTIDAD'));
    }

    private function tipoDocumentoSeleccionado(): ?TipoDocumento
    {
        if ($this->personaForm['id_tipo_documento'] === '') {
            return null;
        }

        return $this->tiposDocumento()
            ->firstWhere('id_tdo', (int) $this->personaForm['id_tipo_documento']);
    }

    private function hidratarCatalogos(): void
    {
        $this->tiposDocumentoCatalogo = TipoDocumento::query()
            ->where('estado_tdo', EstadoCatalogo::Habilitado->value)
            ->orderBy('id_tdo')
            ->get(['id_tdo', 'codigo_tdo', 'nombre_tdo', 'longitud_minima_tdo', 'longitud_maxima_tdo'])
            ->map(fn (TipoDocumento $tipoDocumento): array => $tipoDocumento->toArray())
            ->all();

        $this->estadosCivilesCatalogo = EstadoCivil::query()
            ->where('estado_eci', EstadoCatalogo::Habilitado->value)
            ->orderBy('nombre_eci')
            ->get(['id_eci', 'nombre_eci'])
            ->map(fn (EstadoCivil $estadoCivil): array => $estadoCivil->toArray())
            ->all();

        $this->paisesCatalogo = Pais::query()
            ->where('estado_pai', EstadoCatalogo::Habilitado->value)
            ->orderBy('nombre_pai')
            ->get(['id_pai', 'nombre_pai'])
            ->map(fn (Pais $pais): array => $pais->toArray())
            ->all();

        $this->nacionalidadesCatalogo = Nacionalidad::query()
            ->where('estado_nac', EstadoCatalogo::Habilitado->value)
            ->orderBy('nombre_nac')
            ->get(['id_nac', 'nombre_nac'])
            ->map(fn (Nacionalidad $nacionalidad): array => $nacionalidad->toArray())
            ->all();

        $this->identidadesEtnicasCatalogo = IdentidadEtnica::query()
            ->where('estado_iet', EstadoCatalogo::Habilitado->value)
            ->orderBy('nombre_iet')
            ->get(['id_iet', 'nombre_iet'])
            ->map(fn (IdentidadEtnica $identidadEtnica): array => $identidadEtnica->toArray())
            ->all();

        $this->lenguasNativasCatalogo = LenguaNativa::query()
            ->where('estado_lna', EstadoCatalogo::Habilitado->value)
            ->orderBy('nombre_lna')
            ->get(['id_lna', 'nombre_lna'])
            ->map(fn (LenguaNativa $lenguaNativa): array => $lenguaNativa->toArray())
            ->all();

        $this->lenguasExtranjerasCatalogo = LenguaExtranjera::query()
            ->where('estado_lex', EstadoCatalogo::Habilitado->value)
            ->orderBy('nombre_lex')
            ->get(['id_lex', 'nombre_lex'])
            ->map(fn (LenguaExtranjera $lenguaExtranjera): array => $lenguaExtranjera->toArray())
            ->all();

        $this->tiposDiscapacidadCatalogo = TipoDiscapacidad::query()
            ->where('estado_tdi', EstadoCatalogo::Habilitado->value)
            ->orderBy('nombre_tdi')
            ->get(['id_tdi', 'nombre_tdi'])
            ->map(fn (TipoDiscapacidad $tipoDiscapacidad): array => $tipoDiscapacidad->toArray())
            ->all();

        $this->tiposColegioCatalogo = TipoColegio::query()
            ->where('estado_tco', EstadoCatalogo::Habilitado->value)
            ->orderBy('nombre_tco')
            ->get(['id_tco', 'codigo_tco', 'nombre_tco'])
            ->map(fn (TipoColegio $tipoColegio): array => $tipoColegio->toArray())
            ->all();
    }

    private function limpiarEstadoReniec(): void
    {
        $this->reniecMensaje = null;
        $this->reniecTipoMensaje = 'info';
    }

    /**
     * @return array{disponible:bool,mensaje:string,proceso:?App\Models\Proceso}
     */
    private function refrescarContextoProcesoPostulacion(): array
    {
        return $this->contextoProcesoPostulacionCache = app(ProcesoInscripcionResolver::class)->resolver();
    }

    private function generarCodigoInscripcion(Inscripcion $inscripcion): string
    {
        $procesoOferta = ProcesoOferta::query()
            ->with(['proceso.semestre'])
            ->find($inscripcion->id_proceso_oferta);

        $proceso = $procesoOferta?->proceso;
        $semestre = $proceso?->semestre;

        // Extraer año y número de semestre desde codigo_sem (ej: "2026_1" → "2026", "1")
        [$anio, $numSem] = array_pad(
            explode('_', (string) ($semestre?->codigo_sem ?? '0_0')),
            2,
            '0',
        );

        // Abreviatura del proceso (max 3 chars, mayusculas)
        $codigoPro = mb_strtoupper(
            mb_substr((string) ($proceso?->codigo_pro ?? 'PRO'), 0, 3, 'UTF-8'),
            'UTF-8',
        );

        // Correlativo secuencial dentro del proceso
        $correlativo = Inscripcion::query()
            ->whereHas(
                'procesoOferta',
                fn ($q) => $q->where('id_proceso', $proceso?->id_pro),
            )
            ->where('id_ins', '<=', $inscripcion->id_ins)
            ->count();

        return "{$anio}-{$numSem}-{$codigoPro}-".str_pad((string) $correlativo, 4, '0', STR_PAD_LEFT);
    }

    private function cargarPersonaDesdeSistema(Persona $persona): void
    {
        $persona->loadMissing(['ubigeoNacimiento', 'ubigeoDomicilio']);

        $this->origenDatosPersona = 'sistema';
        $this->personaId = $persona->id_per;
        $this->personaForm['numero_documento'] = (string) $persona->numero_documento_per;
        $this->personaForm['solo_un_apellido'] = (bool) $persona->solo_un_apellido_per;
        $this->personaForm['tiene_apellido_casada'] = filled($persona->apellido_casada_per);
        $this->personaForm['apellido_paterno'] = (string) ($persona->apellido_paterno_per ?? '');
        $this->personaForm['apellido_materno'] = (string) ($persona->apellido_materno_per ?? '');
        $this->personaForm['apellido_casada'] = (string) ($persona->apellido_casada_per ?? '');
        $this->personaForm['nombres'] = (string) ($persona->nombres_per ?? '');
        $this->personaForm['fecha_nacimiento'] = $persona->fecha_nacimiento_per?->format('Y-m-d') ?? '';
        $this->personaForm['sexo'] = $persona->sexo_per?->value ?? (string) ($persona->sexo_per ?? '');
        $this->personaForm['id_estado_civil'] = (string) ($persona->id_estado_civil ?? '');
        $this->personaForm['id_pais_nacimiento'] = (string) ($persona->id_pais_nacimiento ?? '');
        $this->personaForm['id_nacionalidad'] = (string) ($persona->id_nacionalidad ?? '');
        $this->personaForm['id_ubigeo_nacimiento'] = (string) ($persona->id_ubigeo_nacimiento ?? '');
        $this->personaForm['id_ubigeo_domicilio'] = (string) ($persona->id_ubigeo_domicilio ?? '');
        $this->personaForm['direccion'] = (string) ($persona->direccion_per ?? '');
        $this->personaForm['celular'] = (string) ($persona->celular_per ?? '');
        $this->personaForm['telefono'] = (string) ($persona->telefono_per ?? '');
        $this->personaForm['correo_electronico'] = (string) ($persona->correo_electronico_per ?? '');
        $this->personaForm['discapacidad'] = (bool) $persona->discapacidad_per;
        $this->personaForm['id_tipo_discapacidad'] = (string) ($persona->id_tipo_discapacidad ?? '');
        $this->personaForm['tipo_lengua_materna'] = $persona->tipo_lengua_materna_per?->value ?? (string) ($persona->tipo_lengua_materna_per ?? '');
        $this->personaForm['id_identidad_etnica'] = (string) ($persona->id_identidad_etnica ?? '');
        $this->personaForm['id_lengua_nativa'] = (string) ($persona->id_lengua_nativa ?? '');
        $this->personaForm['id_lengua_extranjera'] = (string) ($persona->id_lengua_extranjera ?? '');
        $this->personaForm['observacion'] = (string) ($persona->observacion_per ?? '');
        $this->normalizarEstadoPersonaForm();
        $this->sincronizarEtiquetasUbigeo(
            $persona->ubigeoNacimiento,
            $persona->ubigeoDomicilio,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function cargarPersonaDesdeReniec(array $data, string $numeroDocumento): void
    {
        $this->reiniciarFormularioPorCambioIdentidad(
            (string) $this->personaForm['id_tipo_documento'],
            $numeroDocumento,
        );

        $this->origenDatosPersona = 'reniec';
        $this->personaId = null;
        $this->personaForm['numero_documento'] = (string) ($data['numero'] ?? $numeroDocumento);
        $this->personaForm['nombres'] = (string) ($data['nombres'] ?? $this->personaForm['nombres']);
        $this->personaForm['apellido_paterno'] = (string) ($data['apellido_paterno'] ?? $this->personaForm['apellido_paterno']);

        if (! $this->personaForm['solo_un_apellido']) {
            $this->personaForm['apellido_materno'] = (string) ($data['apellido_materno'] ?? $this->personaForm['apellido_materno']);
        }
    }

    private function reiniciarFormularioPorCambioIdentidad(string $idTipoDocumento, string $numeroDocumento): void
    {
        $this->personaId = null;
        $this->origenDatosPersona = null;
        $this->personaForm = array_merge($this->formularioPersonaBase(), [
            'id_tipo_documento' => $idTipoDocumento,
            'numero_documento' => $numeroDocumento,
        ]);

        $this->sincronizarEtiquetasUbigeo(null, null);
    }

    private function sincronizarEtiquetasUbigeo(?Ubigeo $ubigeoNacimiento, ?Ubigeo $ubigeoDomicilio): void
    {
        $this->ubigeoNacimientoLabel = $this->formatearEtiquetaUbigeo($ubigeoNacimiento);
        $this->ubigeoDomicilioLabel = $this->formatearEtiquetaUbigeo($ubigeoDomicilio);

        $this->dispatch(
            'ui-select-search-filled',
            name: 'personaForm.id_ubigeo_nacimiento',
            value: $this->personaForm['id_ubigeo_nacimiento'],
            label: $this->ubigeoNacimientoLabel,
        );

        $this->dispatch(
            'ui-select-search-filled',
            name: 'personaForm.id_ubigeo_domicilio',
            value: $this->personaForm['id_ubigeo_domicilio'],
            label: $this->ubigeoDomicilioLabel,
        );
    }

    private function formatearEtiquetaUbigeo(?Ubigeo $ubigeo): string
    {
        if ($ubigeo === null) {
            return '';
        }

        return "{$ubigeo->id_ubi} | {$ubigeo->nombre_departamento_ubi} / {$ubigeo->nombre_provincia_ubi} / {$ubigeo->nombre_distrito_ubi}";
    }

    private function esPaisPeruSeleccionado(): bool
    {
        return $this->generalForm['id_pais_colegio'] !== ''
            && (string) $this->generalForm['id_pais_colegio'] === $this->paisPeruId();
    }

    public function descripcionProcesoOferta(?ProcesoOferta $procesoOferta): string
    {
        if ($procesoOferta === null) {
            return 'SIN OFERTA';
        }

        $carrera = $procesoOferta->ofertaBase?->sedeCarrera?->carrera?->nombre_car ?? 'SIN CARRERA';
        $sede = $procesoOferta->ofertaBase?->sedeCarrera?->sede?->nombre_sed ?? 'SIN SEDE';
        $modalidad = $procesoOferta->ofertaBase?->modalidadLugarInscripcion?->modalidadAdmision?->nombre_mad ?? 'SIN MODALIDAD';
        $lugar = $procesoOferta->ofertaBase?->modalidadLugarInscripcion?->lugarInscripcion?->nombre_lin ?? 'SIN LUGAR';
        $turno = $procesoOferta->turno?->nombre_tur ?? 'SIN TURNO';

        return "{$carrera} | {$sede} | {$modalidad} | {$lugar} | {$turno}";
    }

    public function descripcionConvenioProceso(?ConvenioProceso $convenioProceso): string
    {
        if ($convenioProceso === null) {
            return 'Sin convenio';
        }

        return $convenioProceso->convenio?->nombre_con ?? 'SIN CONVENIO';
    }

    /**
     * @return array<string, mixed>
     */
    private function formularioPersonaBase(): array
    {
        return [
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
    }

    private function normalizarEstadoGeneralForm(): void
    {
        if ($this->esPaisPeruSeleccionado()) {
            $this->generalForm['nombre_colegio_extranjero'] = '';

            return;
        }

        $this->generalForm['id_colegio'] = '';
        $this->colegioLabel = '';
    }

    private function resetPostulacionForm(): void
    {
        $this->postulacionForm = [
            'id_modalidad_admision' => '',
            'id_lugar_inscripcion' => '',
            'id_turno' => '',
            'id_proceso_oferta' => '',
            'id_convenio_proceso' => '',
        ];
    }

    /**
     * @return array{
     *   id_regla_pago:string,
     *   numero_documento:string,
     *   numero_operacion:string,
     *   fecha_pago:string,
     *   id_detalle_banco:string
     * }
     */
    private function formularioPagoBase(): array
    {
        return [
            'id_regla_pago' => '',
            'numero_documento' => '',
            'numero_operacion' => '',
            'fecha_pago' => now()->subDay()->toDateString(),
            'id_detalle_banco' => '',
        ];
    }

    private function limpiarBusquedaPago(): void
    {
        $numeroDocumento = $this->pagoForm['numero_documento'];
        $fechaPago = $this->pagoForm['fecha_pago'];
        $idReglaPago = $this->pagoForm['id_regla_pago'];

        $this->pagoForm = array_merge($this->formularioPagoBase(), [
            'id_regla_pago' => $idReglaPago,
            'numero_documento' => $numeroDocumento,
            'fecha_pago' => $fechaPago !== '' ? $fechaPago : now()->subDay()->toDateString(),
        ]);
        $this->resultadoPagoDetalleIds = [];
        $this->busquedaPagoRealizada = false;
    }

    private function prepararPasoPago(): void
    {
        $inscripcion = $this->inscripcionBorrador;

        if ($inscripcion === null) {
            return;
        }

        $this->pagoForm['numero_documento'] = (string) ($inscripcion->persona?->numero_documento_per ?? '');
        $this->pagoForm['fecha_pago'] = $this->pagoForm['fecha_pago'] !== ''
            ? $this->pagoForm['fecha_pago']
            : now()->subDay()->toDateString();
    }

    private function resolverObligacionPagoSeleccionada(ReglaPago $reglaPago): InscripcionObligacionPago
    {
        $inscripcion = $this->inscripcionBorrador;

        if ($inscripcion === null) {
            throw new \RuntimeException('No existe una inscripcion en borrador para registrar el pago.');
        }

        InscripcionObligacionPago::query()
            ->where('id_inscripcion', $inscripcion->id_ins)
            ->where('id_regla_pago', '!=', $reglaPago->id_rpa)
            ->whereDoesntHave('pagos')
            ->delete();

        $obligacion = InscripcionObligacionPago::query()
            ->firstOrNew([
                'id_inscripcion' => $inscripcion->id_ins,
                'id_regla_pago' => $reglaPago->id_rpa,
            ]);

        $montoPagadoActual = $obligacion->exists ? (float) $obligacion->monto_pagado_iop : 0;
        $montoTotal = round((float) $reglaPago->monto_total_rpa, 2);
        $saldo = round(max($montoTotal - $montoPagadoActual, 0), 2);

        $obligacion->monto_total_iop = $montoTotal;
        $obligacion->monto_pagado_iop = $montoPagadoActual;
        $obligacion->saldo_iop = $saldo;
        $obligacion->numero_cuotas_iop = max(1, (int) $reglaPago->numero_maximo_cuotas_rpa);
        $obligacion->estado_iop = $saldo <= 0
            ? EstadoObligacionPago::Pagado
            : ($montoPagadoActual > 0 ? EstadoObligacionPago::Parcial : EstadoObligacionPago::Pendiente);
        $obligacion->save();

        return $obligacion->fresh([
            'reglaPago.conceptoPago',
            'pagos.importacionBancoDetalle',
        ]);
    }

    private function inicializarExpedientesForm(): void
    {
        $requisitos = $this->requisitosExpedienteWizard();

        if ($requisitos->isEmpty()) {
            $this->expedientesForm = [];

            return;
        }

        $existentes = $this->expedientesRegistradosWizard()
            ->keyBy(fn (InscripcionExpediente $expediente): string => (string) $expediente->id_tipo_expediente);

        $this->expedientesForm = $requisitos
            ->map(function (RequisitoExpediente $requisito) use ($existentes): array {
                $expediente = $existentes->get((string) $requisito->id_tipo_expediente);

                return [
                    'id_tipo_expediente' => (string) $requisito->id_tipo_expediente,
                    'estado_revision' => (string) ($expediente?->estado_revision_iex->value ?? EstadoRevisionExpediente::Pendiente->value),
                    'observacion' => (string) ($expediente?->observacion_iex ?? ''),
                    'nombre_archivo' => (string) ($expediente?->nombre_archivo_iex ?? ''),
                ];
            })
            ->values()
            ->all();
    }

    private function normalizarEstadoPersonaForm(): void
    {
        if ($this->personaForm['solo_un_apellido']) {
            $this->personaForm['apellido_materno'] = '';
        }

        if (! $this->personaForm['tiene_apellido_casada']) {
            $this->personaForm['apellido_casada'] = '';
        }

        if (! $this->personaForm['discapacidad']) {
            $this->personaForm['id_tipo_discapacidad'] = '';
        }

        if ($this->personaForm['tipo_lengua_materna'] === TipoLenguaMaternaPersona::Nativa->value) {
            $this->personaForm['id_lengua_extranjera'] = '';
        }

        if ($this->personaForm['tipo_lengua_materna'] === TipoLenguaMaternaPersona::Extranjera->value) {
            $this->personaForm['id_lengua_nativa'] = '';
        }

        if (! in_array($this->personaForm['tipo_lengua_materna'], [
            TipoLenguaMaternaPersona::Nativa->value,
            TipoLenguaMaternaPersona::Extranjera->value,
        ], true)) {
            $this->personaForm['id_lengua_nativa'] = '';
            $this->personaForm['id_lengua_extranjera'] = '';
        }
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
};