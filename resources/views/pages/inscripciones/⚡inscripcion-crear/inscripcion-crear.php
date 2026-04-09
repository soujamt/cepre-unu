<?php

use App\Enums\EstadoCatalogo;
use App\Enums\SexoPersona;
use App\Enums\TipoLenguaMaternaPersona;
use App\Services\ReniecService;
use App\Models\EstadoCivil;
use App\Models\IdentidadEtnica;
use App\Models\LenguaExtranjera;
use App\Models\LenguaNativa;
use App\Models\Nacionalidad;
use App\Models\Pais;
use App\Models\Persona;
use App\Models\TipoDiscapacidad;
use App\Models\TipoDocumento;
use App\Models\Ubigeo;
use App\Traits\AuthorizesPermisos;
use Illuminate\Support\Facades\DB;
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

    public ?int $personaId = null;

    public ?string $reniecMensaje = null;

    public string $reniecTipoMensaje = 'info';

    public ?string $origenDatosPersona = null;

    public string $ubigeoNacimientoLabel = '';

    public string $ubigeoDomicilioLabel = '';

    public function mount(): void
    {
        $this->autorizarPermiso('inscripcion', 'crear');
        $this->hidratarCatalogos();
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

    #[Computed]
    public function pasos(): array
    {
        return [
            1 => ['titulo' => 'Informacion personal', 'descripcion' => 'Datos base del postulante'],
            2 => ['titulo' => 'Informacion general', 'descripcion' => 'Datos complementarios'],
            3 => ['titulo' => 'Postulacion', 'descripcion' => 'Proceso, oferta y colegio'],
            4 => ['titulo' => 'Pago', 'descripcion' => 'Validacion del pago bancario'],
            5 => ['titulo' => 'Expedientes', 'descripcion' => 'Carga y revision de documentos'],
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

    public function irPasoAnterior(): void
    {
        if ($this->pasoActual > 1) {
            $this->pasoActual--;
        }
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
    }

    private function limpiarEstadoReniec(): void
    {
        $this->reniecMensaje = null;
        $this->reniecTipoMensaje = 'info';
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
