<?php

namespace App\Traits\InscripcionCrear;

use App\Enums\EstadoCatalogo;
use App\Enums\EstadoInscripcion;
use App\Enums\EstadoRevisionExpediente;
use App\Models\InscripcionExpediente;
use App\Models\RequisitoExpediente;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use RuntimeException;
use Throwable;

/**
 * Paso 5 del asistente de inscripción: carga y revisión de expedientes.
 *
 * Lo usa el componente pages::inscripciones.inscripcion-crear, que declara
 * el estado (formularios y propiedades) sobre el que trabajan estos métodos.
 */
trait GestionaPasoExpedientes
{
    #[Computed]
    public function requisitosExpedienteWizard(): EloquentCollection
    {
        $inscripcion = $this->inscripcionBorrador;
        $procesoId = $inscripcion?->procesoOferta?->id_proceso;

        if ($inscripcion === null || $procesoId === null) {
            return new EloquentCollection;
        }

        return RequisitoExpediente::query()
            ->with('tipoExpediente')
            ->where('id_proceso', $procesoId)
            ->where('estado_rex', EstadoCatalogo::Habilitado->value)
            ->orderBy('id_tipo_expediente')
            ->get();
    }

    #[Computed]
    public function expedientesRegistradosWizard(): EloquentCollection
    {
        $inscripcion = $this->inscripcionBorrador;

        if ($inscripcion === null) {
            return new EloquentCollection;
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
    protected function rulesPasoExpedientes(): array
    {
        $rules = [];

        foreach ($this->expedientesForm as $indice => $expediente) {
            $requisito = $this->requisitosExpedienteWizard()->firstWhere('id_tipo_expediente', (int) $expediente['id_tipo_expediente']);
            $esObligatorio = $requisito?->obligatorio_rex ?? false;
            $tieneArchivoExistente = filled($expediente['archivo_url'] ?? '');

            $rules["expedientesForm.{$indice}.id_tipo_expediente"] = ['required', 'integer'];
            $rules["expedientesForm.{$indice}.estado_revision"] = ['required', 'in:'.collect(EstadoRevisionExpediente::cases())->pluck('value')->implode(',')];
            $rules["expedientesForm.{$indice}.observacion"] = ['nullable', 'string', 'max:255'];
            $rules["archivosExpediente.{$indice}"] = $esObligatorio && ! $tieneArchivoExistente
                ? ['required', 'file', 'mimes:pdf', 'max:5120']
                : ['nullable', 'file', 'mimes:pdf', 'max:5120'];
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
            'archivosExpediente.*.required' => 'El archivo PDF es obligatorio para este expediente.',
            'archivosExpediente.*.mimes' => 'El archivo debe ser un PDF.',
            'archivosExpediente.*.max' => 'El archivo PDF no debe superar los 5 MB.',
        ];
    }

    public function guardarPasoExpedientes(): void
    {
        $contextoProceso = $this->refrescarContextoProcesoPostulacion();

        if (! $contextoProceso['disponible']) {
            $this->dispatch('toast', message: $contextoProceso['mensaje'], type: 'warning');

            return;
        }

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

        $todosAprobados = false;
        $archivosNuevos = [];

        try {
            foreach ($this->expedientesForm as $indice => $expediente) {
                $archivoUpload = $this->archivosExpediente[$indice] ?? null;

                if ($archivoUpload instanceof TemporaryUploadedFile) {
                    $urlActual = $this->nullableTrim($expediente['archivo_url'] ?? '');

                    if ($urlActual !== null) {
                        Storage::disk('public')->delete($urlActual);
                    }

                    $storedPath = $archivoUpload->store('expedientes/'.$inscripcion->id_ins, 'public');

                    if ($storedPath === false) {
                        throw new RuntimeException('No se pudo guardar el archivo PDF del expediente.');
                    }

                    $archivosNuevos[$indice] = [
                        'url' => $storedPath,
                        'nombre' => $archivoUpload->getClientOriginalName(),
                    ];
                }
            }

            DB::transaction(function () use ($inscripcion, $data, $archivosNuevos, &$todosAprobados): void {
                foreach ($data['expedientesForm'] as $indice => $expediente) {
                    $archivoNuevo = $archivosNuevos[$indice] ?? null;

                    $payload = [
                        'archivo_url_iex' => $archivoNuevo !== null
                            ? $archivoNuevo['url']
                            : $this->nullableTrim($this->expedientesForm[$indice]['archivo_url'] ?? ''),
                        'nombre_archivo_iex' => $archivoNuevo !== null
                            ? $archivoNuevo['nombre']
                            : $this->nullableTrim($this->expedientesForm[$indice]['nombre_archivo']),
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
        } catch (Throwable $throwable) {
            report($throwable);
            $this->dispatch('toast', message: 'No se pudieron guardar los expedientes. Intente nuevamente.', type: 'error');

            return;
        }

        unset($this->inscripcionBorrador, $this->expedientesRegistradosWizard);

        $mensaje = $todosAprobados
            ? 'Expedientes guardados correctamente. La inscripcion quedo activa.'
            : 'Expedientes guardados correctamente. La inscripcion permanece pendiente hasta completar la revision.';

        $this->dispatch('toast', message: $mensaje, type: 'success');
        $this->redirect(route('inscripciones.index'), navigate: true);
    }

    public function continuarPasoExpedientes(): void
    {
        $this->inicializarExpedientesForm();
        $this->pasoActual = 5;
    }

    public function estadosRevisionExpediente(): array
    {
        return EstadoRevisionExpediente::cases();
    }

    private function inicializarExpedientesForm(): void
    {
        $requisitos = $this->requisitosExpedienteWizard();

        if ($requisitos->isEmpty()) {
            $this->expedientesForm = [];
            $this->archivosExpediente = [];

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
                    'archivo_url' => (string) ($expediente?->archivo_url_iex ?? ''),
                ];
            })
            ->values()
            ->all();

        $this->archivosExpediente = [];
    }
}
