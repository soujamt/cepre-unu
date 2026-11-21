<?php

namespace App\Traits\InscripcionCrear;

use App\Enums\EstadoCatalogo;
use App\Enums\EstadoImportacionBancoDetalle;
use App\Enums\EstadoObligacionPago;
use App\Enums\EstadoValidacionPago;
use App\Models\ImportacionBancoDetalle;
use App\Models\Inscripcion;
use App\Models\InscripcionObligacionPago;
use App\Models\ReglaPago;
use App\Support\InscripcionPagoManager;
use App\Support\ValidadorVigenciaInscripcion;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use RuntimeException;

/**
 * Paso 1 del asistente: validar el pago antes de inscribir, como en el
 * sistema anterior. Con DNI + N° de operación se buscan los pagos LIBRES del
 * postulante, se elige la modalidad de pago y se comprueba el mínimo. Los
 * pagos se asocian recién al guardar la postulación (paso 4).
 *
 * Lo usa el componente pages::inscripciones.inscripcion-crear, que declara
 * el estado (formularios y propiedades) sobre el que trabajan estos métodos.
 */
trait GestionaPasoPago
{
    private function cargarPagoExistente(Inscripcion $inscripcion): void
    {
        $obligacion = $inscripcion->obligacionesPago->sortByDesc('id_iop')->first();

        $this->pagoForm = array_merge($this->formularioPagoBase(), [
            'id_regla_pago' => (string) ($obligacion?->id_regla_pago ?? ''),
            'numero_documento' => (string) ($inscripcion->persona?->numero_documento_per ?? ''),
            'numero_documento_beneficio' => (string) ($obligacion?->numero_documento_beneficio_iop ?? ''),
        ]);
        $this->pagoValidado = $this->tienePagoValidadoWizard;
    }

    public function updatedPagoFormNumeroDocumento(): void
    {
        $this->limpiarBusquedaPago();
    }

    public function updatedPagoFormIdReglaPago(): void
    {
        $this->pagoValidado = false;
    }

    public function updatedPagosSeleccionados(): void
    {
        $this->pagoValidado = false;
    }

    /**
     * Reglas vigentes del proceso activo. Las que dependen de la modalidad se
     * comprueban al guardar la postulación, cuando ya se conoce el año de egreso.
     */
    public function reglasPagoDisponibles(): EloquentCollection
    {
        $proceso = $this->procesoPostulacionActivo();

        if ($proceso === null) {
            return new EloquentCollection;
        }

        $hoy = now()->toDateString();

        return ReglaPago::query()
            ->with(['conceptoPago', 'modalidadAdmision'])
            ->where('id_proceso', $proceso->id_pro)
            ->where('estado_rpa', EstadoCatalogo::Habilitado->value)
            ->whereDate('vigente_desde_rpa', '<=', $hoy)
            ->where(fn ($query) => $query->whereNull('vigente_hasta_rpa')->orWhereDate('vigente_hasta_rpa', '>=', $hoy))
            ->orderByDesc('monto_total_rpa')
            ->get();
    }

    public function reglaPagoSeleccionada(): ?ReglaPago
    {
        if ($this->pagoForm['id_regla_pago'] === '') {
            return null;
        }

        return $this->reglasPagoDisponibles()
            ->first(fn (ReglaPago $regla): bool => (string) $regla->id_rpa === (string) $this->pagoForm['id_regla_pago']);
    }

    public function detallesPagoEncontrados(): EloquentCollection
    {
        if ($this->resultadoPagoDetalleIds === []) {
            return new EloquentCollection;
        }

        return ImportacionBancoDetalle::query()
            ->whereIn('id_ibd', $this->resultadoPagoDetalleIds)
            ->orderByDesc('fecha_pago_ibd')
            ->orderByDesc('id_ibd')
            ->get();
    }

    public function montoPagosSeleccionados(): float
    {
        return round((float) $this->detallesPagoEncontrados
            ->whereIn('id_ibd', $this->idsPagosSeleccionados())
            ->sum(fn (ImportacionBancoDetalle $detalle): float => (float) $detalle->monto_ibd), 2);
    }

    /**
     * Monto que deben sumar los pagos para inscribir con la regla elegida.
     */
    public function montoMinimoRegla(?ReglaPago $reglaPago): float
    {
        if ($reglaPago === null) {
            return 0;
        }

        $total = round((float) $reglaPago->monto_total_rpa, 2);

        if ($reglaPago->permite_cuotas_rpa && ! $reglaPago->requiere_pago_completo_rpa) {
            return min(round((float) $reglaPago->monto_minimo_primera_cuota_rpa, 2), $total);
        }

        return $total;
    }

    public function tienePagoValidadoWizard(): bool
    {
        if ($this->inscripcionId === null) {
            return false;
        }

        return InscripcionObligacionPago::query()
            ->where('id_inscripcion', $this->inscripcionId)
            ->where(function ($query): void {
                $query->where('estado_iop', EstadoObligacionPago::Exonerado->value)
                    ->orWhereHas('pagos', fn ($pagos) => $pagos->where('estado_validacion_ipa', EstadoValidacionPago::Verificado->value));
            })
            ->exists();
    }

    public function buscarPagosPasoPago(InscripcionPagoManager $manager): void
    {
        $data = $this->validate([
            'pagoForm.numero_documento' => ['required', 'regex:/^\d{8,12}$/'],
            'pagoForm.numero_operacion' => ['required', 'string', 'max:30'],
        ], [
            'pagoForm.numero_documento.required' => 'Ingrese el DNI o CE del postulante.',
            'pagoForm.numero_documento.regex' => 'El documento debe tener entre 8 y 12 dígitos.',
            'pagoForm.numero_operacion.required' => 'Ingrese el número de operación del pago.',
        ]);

        $dni = trim($data['pagoForm']['numero_documento']);
        $pagoBuscado = $manager->buscarDetallesDisponibles($dni, trim($data['pagoForm']['numero_operacion']))->first();

        if ($pagoBuscado === null) {
            $this->limpiarBusquedaPago();
            $this->addError('pagoForm.numero_operacion', 'No hay un pago LIBRE con ese DNI y número de operación.');

            return;
        }

        $this->resultadoPagoDetalleIds = $manager->buscarDetallesDisponibles($dni)
            ->pluck('id_ibd')
            ->map(fn (int|string $id): int => (int) $id)
            ->all();
        $this->pagosSeleccionados = [$pagoBuscado->id_ibd => true];
        $this->busquedaPagoRealizada = true;
        $this->pagoValidado = false;
        unset($this->detallesPagoEncontrados, $this->montoPagosSeleccionados);
    }

    public function confirmarPasoPago(): void
    {
        $contextoProceso = $this->refrescarContextoProcesoPostulacion();

        if (! $contextoProceso['disponible']) {
            $this->dispatch('toast', message: $contextoProceso['mensaje'], type: 'warning');

            return;
        }

        $this->validate(
            ['pagoForm.id_regla_pago' => ['required']],
            ['pagoForm.id_regla_pago.required' => 'Seleccione la modalidad de pago.'],
        );

        $reglaPago = $this->reglaPagoSeleccionada();

        if ($reglaPago === null) {
            $this->addError('pagoForm.id_regla_pago', 'La modalidad de pago no está vigente para el proceso activo.');

            return;
        }

        if ($this->idsPagosSeleccionados() === []) {
            $this->addError('pagosSeleccionados', 'Marque al menos un pago del postulante.');

            return;
        }

        if ($reglaPago->documento_requerido_rpa !== null && blank($this->pagoForm['numero_documento_beneficio'])) {
            $this->addError('pagoForm.numero_documento_beneficio', "Ingrese el {$reglaPago->documento_requerido_rpa}.");

            return;
        }

        unset($this->montoPagosSeleccionados);
        $minimo = $this->montoMinimoRegla($reglaPago);

        if ($this->montoPagosSeleccionados < $minimo) {
            $this->addError(
                'pagosSeleccionados',
                'Los pagos marcados suman S/ '.number_format($this->montoPagosSeleccionados, 2)
                .' y '.($reglaPago->conceptoPago?->nombre_cpg ?? 'la modalidad de pago').' exige al menos S/ '.number_format($minimo, 2).'.',
            );

            return;
        }

        $this->resetErrorBag();
        $this->pagoValidado = true;

        if ($this->personaId === null) {
            $this->personaForm['numero_documento'] = trim($this->pagoForm['numero_documento']);
            $this->autocompletarPersonaRegistrada();
        }

        $this->pasoActual = 2;
        $this->dispatch('toast', message: 'Pago validado. Continúe con los datos del postulante.', type: 'success');
    }

    /**
     * Ids de los pagos marcados para esta inscripción.
     *
     * @return list<int>
     */
    private function idsPagosSeleccionados(): array
    {
        $marcados = array_map('intval', array_keys(array_filter($this->pagosSeleccionados)));

        return array_values(array_intersect($marcados, array_map('intval', $this->resultadoPagoDetalleIds)));
    }

    /**
     * Crea la obligación de la inscripción y le asocia los pagos marcados en
     * el paso 1. Se llama dentro de la transacción que guarda la postulación.
     */
    private function registrarPagosDeInscripcion(Inscripcion $inscripcion): void
    {
        $ids = $this->idsPagosSeleccionados();

        if (! $this->pagoValidado || $ids === []) {
            return;
        }

        $reglaPago = app(ValidadorVigenciaInscripcion::class)
            ->reglaPagoVigente((int) $this->pagoForm['id_regla_pago'], $inscripcion);

        $obligacion = InscripcionObligacionPago::query()->firstOrNew([
            'id_inscripcion' => $inscripcion->id_ins,
            'id_regla_pago' => $reglaPago->id_rpa,
        ]);

        if (! $obligacion->exists) {
            $montoTotal = round((float) $reglaPago->monto_total_rpa, 2);
            $obligacion->monto_total_iop = $montoTotal;
            $obligacion->monto_pagado_iop = 0;
            $obligacion->saldo_iop = $montoTotal;
            $obligacion->numero_cuotas_iop = max(1, (int) $reglaPago->numero_maximo_cuotas_rpa);
            $obligacion->estado_iop = EstadoObligacionPago::Pendiente;
        }

        $obligacion->numero_documento_beneficio_iop = $this->nullableUpper($this->pagoForm['numero_documento_beneficio']);
        $obligacion->save();

        $detalles = ImportacionBancoDetalle::query()
            ->whereIn('id_ibd', $ids)
            ->where('estado_ibd', EstadoImportacionBancoDetalle::Pendiente->value)
            ->whereDoesntHave('inscripcionPago')
            ->orderBy('fecha_pago_ibd')
            ->get();

        if ($detalles->count() !== count($ids)) {
            throw new RuntimeException('Uno de los pagos marcados ya fue usado en otra inscripción. Vuelva a validar el pago.');
        }

        if ($reglaPago->documento_requerido_rpa !== null && blank($this->pagoForm['numero_documento_beneficio'])) {
            throw new RuntimeException("Falta el {$reglaPago->documento_requerido_rpa}. Vuelva a validar el pago.");
        }

        if (round((float) $detalles->sum('monto_ibd'), 2) < $this->montoMinimoRegla($reglaPago)) {
            throw new RuntimeException('Los pagos marcados no alcanzan el mínimo de la modalidad de pago. Vuelva a validar el pago.');
        }

        app(InscripcionPagoManager::class)->asociarDetalles($obligacion, $detalles);
    }

    public function descripcionReglaPago(ReglaPago $reglaPago): string
    {
        $partes = [
            $reglaPago->conceptoPago?->nombre_cpg ?? 'REGLA DE PAGO',
            'S/ '.number_format((float) $reglaPago->monto_total_rpa, 2),
        ];

        if ($reglaPago->modalidadAdmision !== null) {
            $partes[] = 'solo '.$reglaPago->modalidadAdmision->nombre_mad;
        }

        return implode(' | ', $partes);
    }

    /**
     * Resumen de la postulación: carrera, sede, modalidad y turno.
     */
    public function descripcionPostulacion(?Inscripcion $inscripcion): string
    {
        if ($inscripcion === null) {
            return 'SIN POSTULACION';
        }

        return implode(' | ', [
            $inscripcion->carrera?->nombre_car ?? 'SIN CARRERA',
            $inscripcion->sede?->nombre_sed ?? 'SIN SEDE',
            $inscripcion->modalidadAdmision?->nombre_mad ?? 'SIN MODALIDAD',
            $inscripcion->turno?->nombre_tur ?? 'SIN TURNO',
        ]);
    }

    /**
     * @return array{id_regla_pago:string,numero_documento:string,numero_operacion:string,numero_documento_beneficio:string}
     */
    private function formularioPagoBase(): array
    {
        return [
            'id_regla_pago' => '',
            'numero_documento' => '',
            'numero_operacion' => '',
            'numero_documento_beneficio' => '',
        ];
    }

    private function limpiarBusquedaPago(): void
    {
        $this->resultadoPagoDetalleIds = [];
        $this->pagosSeleccionados = [];
        $this->busquedaPagoRealizada = false;
        $this->pagoValidado = false;
        unset($this->detallesPagoEncontrados, $this->montoPagosSeleccionados);
    }
}
