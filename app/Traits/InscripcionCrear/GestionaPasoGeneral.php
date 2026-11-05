<?php

namespace App\Traits\InscripcionCrear;

use App\Models\Colegio;
use App\Models\Inscripcion;
use Closure;
use Illuminate\Validation\Rule;

/**
 * Paso 2 del asistente de inscripción: información general y colegio de procedencia.
 *
 * Lo usa el componente pages::inscripciones.inscripcion-crear, que declara
 * el estado (formularios y propiedades) sobre el que trabajan estos métodos.
 */
trait GestionaPasoGeneral
{
    private function cargarInformacionGeneralExistente(Inscripcion $inscripcion): void
    {
        $this->generalForm = [
            'anio_graduacion' => (string) ($inscripcion->anio_graduacion_ins ?? ''),
            'id_pais_colegio' => (string) ($inscripcion->id_pais_colegio ?? ''),
            'id_tipo_colegio' => (string) ($inscripcion->id_tipo_colegio ?? ''),
            'id_colegio' => (string) ($inscripcion->id_colegio ?? ''),
            'nombre_colegio_extranjero' => (string) ($inscripcion->nombre_colegio_extranjero_ins ?? ''),
            'veces_postula_unu' => (int) ($inscripcion->veces_postula_unu_ins ?? 0),
            'veces_postula_otras' => (int) ($inscripcion->veces_postula_otras_ins ?? 0),
            'observacion' => (string) ($inscripcion->observacion_ins ?? ''),
        ];

        $this->normalizarEstadoGeneralForm();
        $this->sincronizarEtiquetaColegio($inscripcion->colegio);
    }

    public function updatedGeneralFormIdPaisColegio(): void
    {
        $this->generalForm['nombre_colegio_extranjero'] = '';
        $this->limpiarColegioSeleccionado();
    }

    public function updatedGeneralFormIdTipoColegio(): void
    {
        $this->limpiarColegioSeleccionado();
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
            : $this->formatearEtiquetaColegio($colegio);
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
                Rule::exists('tbl_colegio', 'id_col')
                    ->where('id_tipo_colegio', $this->generalForm['id_tipo_colegio']),
                function (string $attribute, mixed $value, Closure $fail): void {
                    if ($this->esPaisPeruSeleccionado() && blank($value)) {
                        $fail('Debe seleccionar el colegio de procedencia.');
                    }

                    if ($this->esPaisPeruSeleccionado() && filled($value)) {
                        $colegioPerteneceAlTipo = Colegio::query()
                            ->whereKey($value)
                            ->where('id_tipo_colegio', $this->generalForm['id_tipo_colegio'])
                            ->exists();

                        if (! $colegioPerteneceAlTipo) {
                            $fail('El colegio seleccionado no pertenece al tipo de colegio indicado.');
                        }
                    }
                },
            ],
            'generalForm.nombre_colegio_extranjero' => [
                'nullable',
                'string',
                'max:255',
                function (string $attribute, mixed $value, Closure $fail): void {
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

    public function guardarPasoGeneral(): void
    {
        if (! $this->validarInicioInscripcionDisponible()) {
            return;
        }

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

    private function sincronizarEtiquetaColegio(?Colegio $colegio): void
    {
        $this->colegioLabel = $colegio === null
            ? ''
            : $this->formatearEtiquetaColegio($colegio);

        $this->dispatch(
            'ui-select-search-filled',
            name: 'generalForm.id_colegio',
            value: (string) ($colegio?->id_col ?? ''),
            label: $this->colegioLabel,
        );
    }

    private function formatearEtiquetaColegio(Colegio $colegio): string
    {
        $colegio->loadMissing('ubigeo');

        if ($colegio->ubigeo === null) {
            return "{$colegio->id_col} | {$colegio->nombre_col}";
        }

        return "{$colegio->id_col} | {$colegio->nombre_col} | {$colegio->ubigeo->nombre_departamento_ubi} / {$colegio->ubigeo->nombre_provincia_ubi} / {$colegio->ubigeo->nombre_distrito_ubi}";
    }

    private function limpiarColegioSeleccionado(): void
    {
        $this->generalForm['id_colegio'] = '';
        $this->colegioLabel = '';

        $this->dispatch(
            'ui-select-search-filled',
            name: 'generalForm.id_colegio',
            value: '',
            label: '',
        );
    }

    private function esPaisPeruSeleccionado(): bool
    {
        return $this->generalForm['id_pais_colegio'] !== ''
            && (string) $this->generalForm['id_pais_colegio'] === $this->paisPeruId();
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
}
