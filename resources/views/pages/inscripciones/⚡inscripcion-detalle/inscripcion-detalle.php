<?php

use App\Enums\EstadoObligacionPago;
use App\Enums\EstadoRevisionExpediente;
use App\Models\Inscripcion;
use App\Models\InscripcionExpediente;
use App\Models\InscripcionPago;
use App\Models\Ubigeo;
use App\Traits\AuthorizesPermisos;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
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
            $detalle?->numero_documento_ibd,
            $detalle?->numero_operacion_visible_ibd ?: $detalle?->numero_operacion_ibd,
            $pago->fecha_pago_ipa?->format('d/m/Y'),
            'S/ '.number_format((float) $pago->monto_ipa, 2),
        ]));
    }

    public function urlArchivoExpediente(InscripcionExpediente $expediente): ?string
    {
        if (blank($expediente->archivo_url_iex)) {
            return null;
        }

        return Storage::disk('public')->url($expediente->archivo_url_iex);
    }

    public function volverListado(): void
    {
        $this->redirect(route('inscripciones.index'), navigate: true);
    }
};