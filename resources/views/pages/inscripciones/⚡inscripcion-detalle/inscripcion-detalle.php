<?php

use App\Enums\EstadoInscripcion;
use App\Enums\EstadoObligacionPago;
use App\Enums\EstadoRevisionExpediente;
use App\Enums\EstadoValidacionPago;
use App\Models\Inscripcion;
use App\Models\InscripcionExpediente;
use App\Models\InscripcionPago;
use App\Models\RequisitoExpediente;
use App\Models\Ubigeo;
use App\Traits\AuthorizesPermisos;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

new
#[Layout('layouts.app')]
#[Title('DETALLE DE INSCRIPCION')]
class extends Component
{
    use AuthorizesPermisos;

    public Inscripcion $inscripcionActual;

    public function mount(string $inscripcion): void
    {
        $this->autorizarPermiso('inscripcion', 'indice');

        $idInscripcion = decode($inscripcion);

        abort_unless($idInscripcion !== null, 404);

        $this->inscripcionActual = Inscripcion::query()
            ->with([
                'persona.tipoDocumento',
                'persona.estadoCivil',
                'persona.paisNacimiento',
                'persona.nacionalidad',
                'persona.ubigeoNacimiento',
                'persona.ubigeoDomicilio',
                'persona.tipoDiscapacidad',
                'persona.identidadEtnica',
                'persona.lenguaNativa',
                'persona.lenguaExtranjera',
                'procesoOferta.proceso.semestre',
                'procesoOferta.ofertaBase.modalidadLugarInscripcion.modalidadAdmision',
                'procesoOferta.ofertaBase.modalidadLugarInscripcion.lugarInscripcion.sede',
                'procesoOferta.ofertaBase.sedeCarrera.carrera',
                'procesoOferta.ofertaBase.sedeCarrera.sede',
                'procesoOferta.turno',
                'convenioProceso.convenio',
                'paisColegio',
                'colegio.tipoColegio',
                'tipoColegio',
                'obligacionesPago.reglaPago.conceptoPago',
                'obligacionesPago.reglaPago.convenioProceso.convenio',
                'obligacionesPago.pagos.importacionBancoDetalle',
                'obligacionesPago.pagos.usuarioValidador',
                'expedientes.tipoExpediente',
                'expedientes.usuarioRevisor',
            ])
            ->findOrFail($idInscripcion);
    }

    public function nombrePostulante(): string
    {
        $persona = $this->inscripcionActual->persona;

        if ($persona === null) {
            return 'SIN POSTULANTE';
        }

        return trim(implode(' ', array_filter([
            $persona->apellido_paterno_per,
            $persona->apellido_materno_per,
            $persona->apellido_casada_per,
            $persona->nombres_per,
        ])));
    }

    public function descripcionPostulacion(): string
    {
        $procesoOferta = $this->inscripcionActual->procesoOferta;

        $carrera = $procesoOferta?->ofertaBase?->sedeCarrera?->carrera?->nombre_car ?? 'SIN CARRERA';
        $sede = $procesoOferta?->ofertaBase?->sedeCarrera?->sede?->nombre_sed ?? 'SIN SEDE';
        $modalidad = $procesoOferta?->ofertaBase?->modalidadLugarInscripcion?->modalidadAdmision?->nombre_mad ?? 'SIN MODALIDAD';
        $lugar = $procesoOferta?->ofertaBase?->modalidadLugarInscripcion?->lugarInscripcion?->nombre_lin ?? 'SIN LUGAR';
        $turno = $procesoOferta?->turno?->nombre_tur ?? 'SIN TURNO';

        return implode(' | ', [$carrera, $sede, $modalidad, $lugar, $turno]);
    }

    /**
     * @return array{label:string,color:string,detalle:string}
     */
    public function resumenPagoGeneral(): array
    {
        $obligaciones = $this->inscripcionActual->obligacionesPago;

        if ($obligaciones->isEmpty()) {
            return [
                'label' => 'SIN REGLA',
                'color' => 'zinc',
                'detalle' => 'No existen obligaciones registradas.',
            ];
        }

        $montoPagado = (float) $obligaciones->sum('monto_pagado_iop');
        $montoTotal = (float) $obligaciones->sum('monto_total_iop');

        if ($obligaciones->every(fn ($obligacion) => $obligacion->estado_iop === EstadoObligacionPago::Exonerado)) {
            return [
                'label' => 'EXONERADO',
                'color' => 'violet',
                'detalle' => 'La inscripcion no requiere pago bancario.',
            ];
        }

        if ($obligaciones->every(fn ($obligacion) => in_array($obligacion->estado_iop, [EstadoObligacionPago::Pagado, EstadoObligacionPago::Exonerado], true))) {
            return [
                'label' => 'PAGADO',
                'color' => 'green',
                'detalle' => 'Monto cubierto: S/ '.number_format($montoPagado, 2).' de S/ '.number_format($montoTotal, 2),
            ];
        }

        if ($obligaciones->contains(fn ($obligacion) => $obligacion->estado_iop === EstadoObligacionPago::Parcial)) {
            return [
                'label' => 'PARCIAL',
                'color' => 'sky',
                'detalle' => 'Monto cubierto: S/ '.number_format($montoPagado, 2).' de S/ '.number_format($montoTotal, 2),
            ];
        }

        return [
            'label' => 'PENDIENTE',
            'color' => 'amber',
            'detalle' => 'Monto cubierto: S/ '.number_format($montoPagado, 2).' de S/ '.number_format($montoTotal, 2),
        ];
    }

    /**
     * @return array{label:string,color:string,detalle:string}
     */
    public function resumenExpedientesGeneral(): array
    {
        $expedientes = $this->inscripcionActual->expedientes;
        $total = $expedientes->count();

        if ($total === 0) {
            return [
                'label' => 'SIN CARGA',
                'color' => 'amber',
                'detalle' => 'No hay documentos registrados.',
            ];
        }

        if ($expedientes->contains(fn ($expediente) => $expediente->estado_revision_iex === EstadoRevisionExpediente::Rechazado)) {
            return [
                'label' => 'RECHAZADO',
                'color' => 'red',
                'detalle' => "{$total} documento(s) revisados con rechazo.",
            ];
        }

        if ($expedientes->contains(fn ($expediente) => $expediente->estado_revision_iex === EstadoRevisionExpediente::Observado)) {
            return [
                'label' => 'OBSERVADO',
                'color' => 'sky',
                'detalle' => "{$total} documento(s) revisados con observacion.",
            ];
        }

        if ($expedientes->every(fn ($expediente) => $expediente->estado_revision_iex === EstadoRevisionExpediente::Aprobado)) {
            return [
                'label' => 'COMPLETO',
                'color' => 'green',
                'detalle' => "{$total} documento(s) aprobados.",
            ];
        }

        return [
            'label' => 'PENDIENTE',
            'color' => 'amber',
            'detalle' => "{$total} documento(s) en proceso de revision.",
        ];
    }

    public function convenioResumen(): string
    {
        return $this->inscripcionActual->convenioProceso?->convenio?->nombre_con ?? 'Sin convenio';
    }

    public function turnoResumen(): string
    {
        return $this->inscripcionActual->procesoOferta?->turno?->nombre_tur ?? 'Sin turno';
    }

    public function ubigeoCompleto(?Ubigeo $ubigeo): string
    {
        if ($ubigeo === null) {
            return 'No registrado';
        }

        return "{$ubigeo->nombre_departamento_ubi} / {$ubigeo->nombre_provincia_ubi} / {$ubigeo->nombre_distrito_ubi}";
    }

    public function resumenPago(InscripcionPago $pago): string
    {
        $detalle = $pago->importacionBancoDetalle;

        return implode(' | ', array_filter([
            $this->formatearDocumentoIdentidad($detalle?->numero_documento_ibd),
            $detalle?->numero_operacion_visible_ibd ?: $detalle?->numero_operacion_ibd,
            $pago->fecha_pago_ipa?->format('d/m/Y'),
            'S/ '.number_format((float) $pago->monto_ipa, 2),
        ]));
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

    public function urlArchivoExpediente(InscripcionExpediente $expediente): ?string
    {
        if (blank($expediente->archivo_url_iex)) {
            return null;
        }

        return Storage::disk('public')->url($expediente->archivo_url_iex);
    }

    public ?int $idExpedienteRevisar = null;

    public string $estadoRevision = '';

    #[Validate('required|string|min:5|max:500')]
    public string $observacionRevision = '';

    public function aprobarExpediente(int $idExpediente): void
    {
        $this->autorizarPermiso('inscripcion', 'editar');

        $expediente = InscripcionExpediente::query()->findOrFail($idExpediente);

        $expediente->update([
            'estado_revision_iex' => EstadoRevisionExpediente::Aprobado->value,
            'observacion_iex' => null,
            'revisado_en' => now(),
            'id_usuario_revisor' => Auth::id(),
        ]);

        $this->inscripcionActual->load('expedientes.tipoExpediente', 'expedientes.usuarioRevisor');
        $this->recalcularEstadoInscripcion();
        $this->dispatch('toast', message: 'Expediente aprobado.', type: 'success');
    }

    public function abrirModalRevision(int $idExpediente, string $estado): void
    {
        $this->idExpedienteRevisar = $idExpediente;
        $this->estadoRevision = $estado;
        $this->observacionRevision = '';
        $this->resetValidation('observacionRevision');

        $this->modal('modal-revisar-expediente')->show();
    }

    public function confirmarRevision(): void
    {
        $this->autorizarPermiso('inscripcion', 'editar');

        $this->validate([
            'observacionRevision' => 'required|string|min:5|max:500',
        ], [
            'observacionRevision.required' => 'La observación es obligatoria.',
            'observacionRevision.min' => 'La observación debe tener al menos 5 caracteres.',
            'observacionRevision.max' => 'La observación no puede exceder 500 caracteres.',
        ]);

        $estadoEnum = match ($this->estadoRevision) {
            'OBSERVADO' => EstadoRevisionExpediente::Observado,
            'RECHAZADO' => EstadoRevisionExpediente::Rechazado,
            default => null,
        };

        if ($estadoEnum === null) {
            $this->dispatch('toast', message: 'Estado de revisión no válido.', type: 'error');

            return;
        }

        try {
            $expediente = InscripcionExpediente::query()->findOrFail($this->idExpedienteRevisar);

            $expediente->update([
                'estado_revision_iex' => $estadoEnum->value,
                'observacion_iex' => $this->observacionRevision,
                'revisado_en' => now(),
                'id_usuario_revisor' => Auth::id(),
            ]);
        } catch (\Throwable $throwable) {
            report($throwable);
            $this->dispatch('toast', message: 'No se pudo actualizar el expediente.', type: 'error');

            return;
        }

        $this->modal('modal-revisar-expediente')->close();
        $this->idExpedienteRevisar = null;
        $this->estadoRevision = '';
        $this->observacionRevision = '';

        $this->inscripcionActual->load('expedientes.tipoExpediente', 'expedientes.usuarioRevisor');

        $label = $estadoEnum === EstadoRevisionExpediente::Observado ? 'observado' : 'rechazado';
        $this->dispatch('toast', message: "Expediente marcado como {$label}.", type: 'success');
    }

    public function volverListado(): void
    {
        $this->redirect(route('inscripciones.index'), navigate: true);
    }

    private function recalcularEstadoInscripcion(): void
    {
        $inscripcion = $this->inscripcionActual;

        if ($inscripcion->estado_ins !== EstadoInscripcion::Pendiente) {
            return;
        }

        $procesoId = $inscripcion->procesoOferta?->id_proceso;

        if ($procesoId === null) {
            return;
        }

        $requisitosObligatorios = RequisitoExpediente::query()
            ->where('id_proceso', $procesoId)
            ->where('obligatorio_rex', true)
            ->pluck('id_tipo_expediente')
            ->map(fn (int|string $id): int => (int) $id)
            ->all();

        if ($requisitosObligatorios === []) {
            return;
        }

        $aprobados = $inscripcion->expedientes
            ->filter(fn ($expediente): bool => in_array((int) $expediente->id_tipo_expediente, $requisitosObligatorios, true)
                && $expediente->estado_revision_iex === EstadoRevisionExpediente::Aprobado)
            ->count();

        if ($aprobados !== count($requisitosObligatorios)) {
            return;
        }

        $tienePagoValido = $inscripcion->obligacionesPago->contains(
            fn ($obligacion): bool => $obligacion->estado_iop === EstadoObligacionPago::Exonerado
                || $obligacion->pagos->contains(
                    fn ($pago): bool => $pago->estado_validacion_ipa === EstadoValidacionPago::Verificado
                )
        );

        if (! $tienePagoValido) {
            return;
        }

        $inscripcion->update(['estado_ins' => EstadoInscripcion::Activo->value]);
        $this->dispatch('toast', message: 'Todos los expedientes obligatorios aprobados. La inscripcion quedo activa.', type: 'success');
    }
};
