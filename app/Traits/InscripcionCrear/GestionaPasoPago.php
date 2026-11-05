<?php

namespace App\Traits\InscripcionCrear;

use App\Enums\EstadoCatalogo;
use App\Enums\EstadoObligacionPago;
use App\Enums\EstadoValidacionPago;
use App\Models\ImportacionBancoDetalle;
use App\Models\Inscripcion;
use App\Models\InscripcionObligacionPago;
use App\Models\ReglaPago;
use App\Support\InscripcionPagoManager;
use App\Support\ValidadorVigenciaInscripcion;
use Closure;
use DomainException;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Livewire\Attributes\Computed;
use RuntimeException;
use Throwable;

/**
 * Paso 4 del asistente de inscripción: regla de pago y validación del pago bancario.
 *
 * Lo usa el componente pages::inscripciones.inscripcion-crear, que declara
 * el estado (formularios y propiedades) sobre el que trabajan estos métodos.
 */
trait GestionaPasoPago
{
    private function cargarPagoExistente(Inscripcion $inscripcion): void
    {
        $obligacion = $inscripcion->obligacionesPago
            ->sortByDesc('id_iop')
            ->first();

        $detalle = $obligacion?->pagos
            ?->sortByDesc('id_ipa')
            ->first()
            ?->importacionBancoDetalle;

        $this->pagoForm = array_merge($this->formularioPagoBase(), [
            'id_regla_pago' => (string) ($obligacion?->id_regla_pago ?? ''),
            'numero_documento' => (string) ($inscripcion->persona?->numero_documento_per ?? ''),
            'numero_operacion' => (string) ($detalle?->numero_operacion_visible_ibd ?? ''),
            'fecha_pago' => $detalle?->fecha_pago_ibd?->format('Y-m-d')
                ?? now()->subDay()->toDateString(),
            'id_detalle_banco' => (string) ($detalle?->id_ibd ?? ''),
        ]);

        $this->resultadoPagoDetalleIds = $detalle !== null
            ? [$detalle->id_ibd]
            : [];
        $this->busquedaPagoRealizada = $detalle !== null;
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
    public function reglasPagoDisponibles(): EloquentCollection
    {
        $inscripcion = $this->inscripcionBorrador;
        $procesoId = $inscripcion?->procesoOferta?->id_proceso;

        if ($inscripcion === null || $procesoId === null) {
            return new EloquentCollection;
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
    public function detallesPagoEncontrados(): EloquentCollection
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

    /**
     * @return array<string, mixed>
     */
    protected function rulesPasoPagoBusqueda(): array
    {
        return [
            'pagoForm.id_regla_pago' => [
                'required',
                function (string $attribute, mixed $value, Closure $fail): void {
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

    public function buscarPagosPasoPago(InscripcionPagoManager $manager): void
    {
        $this->prepararPasoPago();

        $contextoProceso = $this->refrescarContextoProcesoPostulacion();

        if (! $contextoProceso['disponible']) {
            $this->dispatch('toast', message: $contextoProceso['mensaje'], type: 'warning');

            return;
        }

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

        $contextoProceso = $this->refrescarContextoProcesoPostulacion();

        if (! $contextoProceso['disponible']) {
            $this->dispatch('toast', message: $contextoProceso['mensaje'], type: 'warning');

            return;
        }

        if ($this->inscripcionId === null) {
            $this->dispatch('toast', message: 'Primero debe guardar la postulacion antes de validar pagos.', type: 'warning');

            return;
        }

        $data = $this->validate(
            $this->rulesPasoPagoConfirmacion(),
            $this->messagesPasoPago(),
        );

        $detalle = ImportacionBancoDetalle::query()->find($data['pagoForm']['id_detalle_banco']);

        if ($detalle === null) {
            $this->dispatch('toast', message: 'No se pudo cargar el pago o la regla seleccionada.', type: 'error');

            return;
        }

        try {
            $inscripcion = Inscripcion::query()
                ->with([
                    'procesoOferta.ofertaBase.modalidadLugarInscripcion',
                    'convenioProceso.convenio',
                ])
                ->findOrFail($this->inscripcionId);

            $this->validarInscripcionBorradorVigente($inscripcion);
            $reglaPago = app(ValidadorVigenciaInscripcion::class)->reglaPagoVigente(
                (int) $data['pagoForm']['id_regla_pago'],
                $inscripcion,
            );
        } catch (RuntimeException $exception) {
            $this->dispatch('toast', message: $exception->getMessage(), type: 'warning');

            return;
        } catch (Throwable $throwable) {
            report($throwable);
            $this->dispatch('toast', message: 'No se pudo validar la vigencia de la inscripción antes de registrar el pago.', type: 'error');

            return;
        }

        $obligacion = $this->resolverObligacionPagoSeleccionada($reglaPago);

        try {
            $manager->asociarDetalleAObligacion($obligacion, $detalle);
        } catch (DomainException $exception) {
            $this->dispatch('toast', message: $exception->getMessage(), type: 'error');

            return;
        } catch (Throwable $throwable) {
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
            $this->formatearDocumentoIdentidad($detalle->numero_documento_ibd),
            $detalle->numero_operacion_visible_ibd ?: $detalle->numero_operacion_ibd ?: 'SIN OPERACION',
            $detalle->fecha_pago_ibd?->format('d/m/Y'),
            'S/ '.number_format((float) $detalle->monto_ibd, 2),
        ])->filter()->implode(' | ');
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
            throw new RuntimeException('No existe una inscripcion en borrador para registrar el pago.');
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
}
