@php
    $persona = $persona ?? null;
    $regla = $obligacion?->reglaPago;
    $minimo = $regla === null ? null : ($regla->permite_cuotas_rpa && ! $regla->requiere_pago_completo_rpa ? min((float) $regla->monto_minimo_primera_cuota_rpa, (float) $regla->monto_total_rpa) : (float) $regla->monto_total_rpa);
    $fila = fn (string $etiqueta, ?string $valor) => '<tr><td class="et">'.e($etiqueta).'</td><td>: '.e($valor ?: '-').'</td></tr>';
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <title>Ficha de matrícula {{ $inscripcion->codigo_ins }}</title>
    <style>
        @page { margin: 28px 34px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10.5px; color: #111; }
        table { border-collapse: collapse; width: 100%; }
        .cab td { vertical-align: middle; }
        .titulo { text-align: center; font-weight: bold; line-height: 1.35; }
        .titulo .uni { font-size: 16px; }
        .titulo .sub { font-size: 13px; }
        .foto { width: 90px; height: 120px; border: 1px solid #999; text-align: center; color: #999; font-size: 9px; }
        .foto img { width: 90px; height: 120px; }
        .reg { text-align: center; font-size: 11px; margin-top: 4px; }
        .seccion { font-weight: bold; font-size: 11px; margin: 10px 0 4px; }
        .col { width: 50%; vertical-align: top; padding-right: 10px; }
        .col + .col { border-left: 1px solid #444; padding-left: 10px; padding-right: 0; }
        .datos td { padding: 1.5px 0; vertical-align: top; }
        .datos .et { width: 42%; }
        .linea { border-top: 1px solid #444; margin: 8px 0; }
        .pagos th, .pagos td { border: 1px solid #444; padding: 4px; text-align: center; }
        .pagos th { font-weight: bold; }
        .firmas td { width: 50%; text-align: center; padding-top: 70px; font-size: 9.5px; }
        .firmas .trazo { border-top: 1px solid #111; margin: 0 18px; padding-top: 3px; }
        .jurada { text-align: center; font-size: 9px; margin-top: 18px; }
    </style>
</head>
<body>
    <table class="cab">
        <tr>
            <td style="width: 95px;">@if ($logo)<img src="{{ $logo }}" alt="" style="width: 85px;" />@endif</td>
            <td class="titulo">
                <div class="uni">UNIVERSIDAD NACIONAL DE UCAYALI</div>
                <div class="sub">CENTRO PREUNIVERSITARIO</div>
                <div class="sub">FICHA DE MATRÍCULA: PROCESO {{ $inscripcion->proceso?->nombre_pro }}</div>
            </td>
            <td style="width: 100px;">
                <div class="foto">
                    @if ($foto)
                        <img src="{{ $foto }}" alt="" />
                    @else
                        <br /><br /><br />SIN FOTO
                    @endif
                </div>
                <div class="reg">Reg. N° {{ $inscripcion->codigo_ins }}</div>
            </td>
        </tr>
    </table>

    <table>
        <tr>
            <td class="col">
                <div class="seccion">{{ $inscripcion->sede?->nombre_sed }}</div>
                <div class="seccion">INFORMACIÓN CEPRE-UNU</div>
                <table class="datos">
                    {!! $fila('Fecha de inscripción', $inscripcion->fecha_inscripcion_ins?->format('d/m/Y H:i:s')) !!}
                    {!! $fila('Modalidad', $inscripcion->modalidadAdmision?->nombre_mad) !!}
                    {!! $fila('Turno', $inscripcion->turno?->nombre_tur) !!}
                    {!! $fila('Carrera', $inscripcion->carrera?->nombre_car) !!}
                </table>
            </td>
            <td class="col">
                <div class="seccion">INFORMACIÓN ACADÉMICA</div>
                <table class="datos">
                    {!! $fila('Nombre de I.E.', $inscripcion->colegio?->nombre_col ?? $inscripcion->nombre_colegio_extranjero_ins) !!}
                    {!! $fila('Tipo de colegio', $inscripcion->tipoColegio?->nombre_tco) !!}
                    {!! $fila('Año de egreso', (string) $inscripcion->anio_graduacion_ins) !!}
                </table>
            </td>
        </tr>
    </table>
    <div class="linea"></div>
    <table>
        <tr>
            <td class="col">
                <div class="seccion">INFORMACIÓN PERSONAL</div>
                <table class="datos">
                    {!! $fila('Doc. identidad', $persona?->numero_documento_per) !!}
                    {!! $fila('Apellidos', trim($persona?->apellido_paterno_per.' '.$persona?->apellido_materno_per)) !!}
                    {!! $fila('Nombres', $persona?->nombres_per) !!}
                    {!! $fila('Fecha de nacimiento', $persona?->fecha_nacimiento_per?->format('d/m/Y')) !!}
                    {!! $fila('Sexo', $persona?->sexo_per?->value) !!}
                    {!! $fila('Estado civil', $persona?->estadoCivil?->nombre_eci) !!}
                    {!! $fila('Lengua materna', $persona?->tipo_lengua_materna_per?->value) !!}
                    {!! $fila('Ubigeo de nacimiento', trim(($persona?->id_ubigeo_nacimiento ?? '').' - '.($persona?->ubigeoNacimiento?->nombre_distrito_ubi ?? ''), ' -')) !!}
                </table>
            </td>
            <td class="col">
                <div class="seccion">INFORMACIÓN DE CONTACTO</div>
                <table class="datos">
                    {!! $fila('Domicilio', $persona?->direccion_per) !!}
                    {!! $fila('Correo electrónico', $persona?->correo_electronico_per) !!}
                    {!! $fila('Cel. (estudiante)', $persona?->celular_per) !!}
                    {!! $fila('Cont. (emergencia)', $persona?->nombre_apoderado_per) !!}
                    {!! $fila('Cel. (emergencia)', $persona?->celular_apoderado_per) !!}
                </table>
            </td>
        </tr>
    </table>
    <div class="linea"></div>

    <div class="seccion">INFORMACIÓN DE PAGO</div>
    <table class="datos" style="width: 60%;">
        {!! $fila('Modalidad de pago', $regla?->conceptoPago?->nombre_cpg) !!}
        {!! $fila('Importe mínimo', $minimo === null ? null : number_format($minimo, 2)) !!}
        {!! $fila('Importe total', $obligacion === null ? null : number_format((float) $obligacion->monto_total_iop, 2)) !!}
    </table>

    <table class="pagos" style="margin-top: 8px;">
        <thead>
            <tr><th style="width: 6%;">N°</th><th style="width: 16%;">Fecha</th><th>Ubicación</th><th style="width: 18%;">N° Operación</th><th style="width: 16%;">Importe</th></tr>
        </thead>
        <tbody>
            @forelse ($pagos as $pago)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $pago->fecha_pago_ipa?->format('d/m/Y') }}</td>
                    <td>{{ $pago->importacionBancoDetalle?->ubicacion_ibd ?? '-' }}</td>
                    <td>{{ $pago->numero_operacion_ipa }}</td>
                    <td>S/. {{ number_format((float) $pago->monto_ipa, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="5">Sin pagos registrados.</td></tr>
            @endforelse
            <tr>
                <td colspan="3" style="border: 0;"></td>
                <td><strong>TOTAL:</strong></td>
                <td><strong>S/. {{ number_format((float) $pagos->sum('monto_ipa'), 2) }}</strong></td>
            </tr>
        </tbody>
    </table>

    <p style="margin-top: 12px;"><strong>PARENTESCO UNU / BECA ACADÉMICA:</strong> {{ $obligacion?->numero_documento_beneficio_iop ?? '-' }}</p>
    <p><strong>OBSERVACIÓN:</strong> {{ $inscripcion->observacion_ins ?? '-' }}</p>

    <div class="linea"></div>
    <p class="jurada">"DECLARO BAJO JURAMENTO QUE LOS DOCUMENTOS Y LOS DATOS PRESENTADOS EN LA PRESENTE FICHA SON REALES"</p>

    <table class="firmas">
        <tr>
            <td><div class="trazo">{{ trim($persona?->nombres_per.' '.$persona?->apellido_paterno_per.' '.$persona?->apellido_materno_per) }}</div></td>
            <td><div class="trazo">APODERADO (EN CASO DE SER MENOR DE EDAD)</div></td>
        </tr>
    </table>
</body>
</html>
