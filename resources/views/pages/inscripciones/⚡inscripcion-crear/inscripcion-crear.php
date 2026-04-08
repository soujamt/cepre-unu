<?php

use App\Enums\EstadoCatalogo;
use App\Enums\SexoPersona;
use App\Enums\TipoLenguaMaternaPersona;
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

    /**
     * @var array{
     *   id_tipo_documento:string,
     *   numero_documento:string,
     *   solo_un_apellido:string,
     *   tiene_apellido_casada:string,
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
     *   discapacidad:string,
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
        'solo_un_apellido' => '0',
        'tiene_apellido_casada' => '0',
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
        'discapacidad' => '0',
        'id_tipo_discapacidad' => '',
        'tipo_lengua_materna' => '',
        'id_identidad_etnica' => '',
        'id_lengua_nativa' => '',
        'id_lengua_extranjera' => '',
        'observacion' => '',
    ];

    public ?int $personaId = null;

    public function mount(): void
    {
        $this->autorizarPermiso('inscripcion', 'crear');
    }

    public function updatedPersonaFormSoloUnApellido(string $valor): void
    {
        if ($valor === '1') {
            $this->personaForm['apellido_materno'] = '';
        }
    }

    public function updatedPersonaFormTieneApellidoCasada(string $valor): void
    {
        if ($valor !== '1') {
            $this->personaForm['apellido_casada'] = '';
        }
    }

    public function updatedPersonaFormDiscapacidad(string $valor): void
    {
        if ($valor !== '1') {
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

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'personaForm.id_tipo_documento' => ['required', 'integer', 'exists:tbl_tipo_documento,id_tdo'],
            'personaForm.numero_documento' => ['required', 'string', 'max:25'],
            'personaForm.solo_un_apellido' => ['required', 'in:0,1'],
            'personaForm.tiene_apellido_casada' => ['required', 'in:0,1'],
            'personaForm.apellido_paterno' => ['required', 'string', 'max:255'],
            'personaForm.apellido_materno' => [
                'nullable',
                'string',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($this->personaForm['solo_un_apellido'] === '0' && blank($value)) {
                        $fail('El segundo apellido es obligatorio cuando no se marca solo un apellido.');
                    }
                },
            ],
            'personaForm.apellido_casada' => [
                'nullable',
                'string',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($this->personaForm['tiene_apellido_casada'] === '1' && blank($value)) {
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
            'personaForm.discapacidad' => ['required', 'in:0,1'],
            'personaForm.id_tipo_discapacidad' => [
                'nullable',
                'integer',
                'exists:tbl_tipo_discapacidad,id_tdi',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($this->personaForm['discapacidad'] === '1' && blank($value)) {
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
        return TipoDocumento::query()
            ->where('estado_tdo', EstadoCatalogo::Habilitado->value)
            ->orderBy('id_tdo')
            ->get();
    }

    #[Computed]
    public function estadosCiviles(): \Illuminate\Database\Eloquent\Collection
    {
        return EstadoCivil::query()
            ->where('estado_eci', EstadoCatalogo::Habilitado->value)
            ->orderBy('nombre_eci')
            ->get();
    }

    #[Computed]
    public function paises(): \Illuminate\Database\Eloquent\Collection
    {
        return Pais::query()
            ->where('estado_pai', EstadoCatalogo::Habilitado->value)
            ->orderBy('nombre_pai')
            ->limit(50)
            ->get();
    }

    #[Computed]
    public function nacionalidades(): \Illuminate\Database\Eloquent\Collection
    {
        return Nacionalidad::query()
            ->where('estado_nac', EstadoCatalogo::Habilitado->value)
            ->orderBy('nombre_nac')
            ->limit(80)
            ->get();
    }

    #[Computed]
    public function ubigeos(): \Illuminate\Database\Eloquent\Collection
    {
        return Ubigeo::query()
            ->where('estado_ubi', EstadoCatalogo::Habilitado->value)
            ->orderBy('nombre_departamento_ubi')
            ->orderBy('nombre_provincia_ubi')
            ->orderBy('nombre_distrito_ubi')
            ->limit(200)
            ->get();
    }

    #[Computed]
    public function identidadesEtnicas(): \Illuminate\Database\Eloquent\Collection
    {
        return IdentidadEtnica::query()
            ->where('estado_iet', EstadoCatalogo::Habilitado->value)
            ->orderBy('nombre_iet')
            ->get();
    }

    #[Computed]
    public function lenguasNativas(): \Illuminate\Database\Eloquent\Collection
    {
        return LenguaNativa::query()
            ->where('estado_lna', EstadoCatalogo::Habilitado->value)
            ->orderBy('nombre_lna')
            ->get();
    }

    #[Computed]
    public function lenguasExtranjeras(): \Illuminate\Database\Eloquent\Collection
    {
        return LenguaExtranjera::query()
            ->where('estado_lex', EstadoCatalogo::Habilitado->value)
            ->orderBy('nombre_lex')
            ->get();
    }

    #[Computed]
    public function tiposDiscapacidad(): \Illuminate\Database\Eloquent\Collection
    {
        return TipoDiscapacidad::query()
            ->where('estado_tdi', EstadoCatalogo::Habilitado->value)
            ->orderBy('nombre_tdi')
            ->get();
    }

    public function guardarPasoPersonal(): void
    {
        $data = $this->validate();

        try {
            DB::transaction(function () use ($data): void {
                $payload = [
                    'id_tipo_documento' => (int) $data['personaForm']['id_tipo_documento'],
                    'numero_documento_per' => trim($data['personaForm']['numero_documento']),
                    'solo_un_apellido_per' => $data['personaForm']['solo_un_apellido'] === '1',
                    'apellido_paterno_per' => $this->mayusculas($data['personaForm']['apellido_paterno']),
                    'apellido_materno_per' => $data['personaForm']['solo_un_apellido'] === '1'
                        ? null
                        : $this->nullableUpper($data['personaForm']['apellido_materno']),
                    'apellido_casada_per' => $data['personaForm']['tiene_apellido_casada'] === '1'
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
                    'discapacidad_per' => $data['personaForm']['discapacidad'] === '1',
                    'id_tipo_discapacidad' => $data['personaForm']['discapacidad'] === '1'
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
