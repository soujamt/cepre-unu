@php
    $regla = $obligacion?->reglaPago;
    $minimo = $regla === null ? null : ($regla->permite_cuotas_rpa && ! $regla->requiere_pago_completo_rpa ? min((float) $regla->monto_minimo_primera_cuota_rpa, (float) $regla->monto_total_rpa) : (float) $regla->monto_total_rpa);
    $lengua = match ($persona?->tipo_lengua_materna_per?->value) {
        'NATIVA' => $persona?->lenguaNativa?->nombre_lna,
        'EXTRANJERA' => $persona?->lenguaExtranjera?->nombre_lex,
        default => null,
    } ?? $persona?->tipo_lengua_materna_per?->value;
    $ubigeoNacimiento = $persona?->id_ubigeo_nacimiento ? $persona->id_ubigeo_nacimiento.' - '.$persona->ubigeoNacimiento?->nombre_distrito_ubi : null;
    $nombreCompleto = trim($persona?->nombres_per.' '.$persona?->apellido_paterno_per.' '.$persona?->apellido_materno_per);
    $fila = fn (string $etiqueta, ?string $valor, string $ancho = '42%') => '<tr><td class="et" style="width: '.$ancho.';">'.e($etiqueta).'</td><td class="dos">:</td><td>'.e(filled($valor) ? $valor : '-').'</td></tr>';
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <title>Ficha de matrícula {{ $inscripcion->codigo_ins }}</title>
    <style>
        @page { margin: 30px 34px 26px 34px; }
        body { font-family: Helvetica, Arial, sans-serif; font-size: 11.5px; color: #111; }
        table { border-collapse: collapse; width: 100%; }
        td { vertical-align: top; }
        .titulo { text-align: center; font-weight: bold; line-height: 1.3; }
        .titulo .uni { font-size: 19px; }
        .titulo .sub { font-size: 16px; }
        .foto { width: 108px; height: 140px; text-align: center; color: #999; font-size: 9px; border: 1px solid #bbb; }
        .foto.con-foto { border: 0; }
        .foto img { width: 108px; height: 140px; }
        .reg { text-align: center; font-size: 12.5px; margin-top: 4px; }
        .seccion { font-weight: bold; font-size: 12px; margin: 0 0 7px; }
        .izq { width: 52%; padding: 4px 12px 8px 0; border-right: 1.2px solid #222; }
        .der { width: 48%; padding: 4px 0 8px 14px; }
        .datos td { padding: 2.2px 0; }
        .datos .dos { width: 8px; }
        .linea { border-top: 1.2px solid #222; }
        .pagos td, .pagos th { border: 1.2px solid #222; padding: 4px 5px; text-align: center; font-size: 12px; }
        .pagos th { font-weight: normal; }
        .negrita { font-weight: bold; font-size: 12.5px; }
        .jurada { text-align: center; font-size: 9.5px; margin: 10px 0 0; }
        .firmas td { width: 50%; text-align: center; padding: 96px 14px 0; font-size: 11px; }
        .firmas .trazo { border-top: 1.2px solid #222; padding-top: 3px; }
    </style>
</head>
<body>
    <table>
        <tr>
            <td style="width: 122px; vertical-align: middle;">@if ($logo)<img src="{{ $logo }}" alt="" style="width: 112px;" />@endif</td>
            <td class="titulo" style="vertical-align: middle;">
                <div class="uni">UNIVERSIDAD NACIONAL DE UCAYALI</div>
                <div class="sub">CENTRO PREUNIVERSITARIO</div>
                <div class="sub">FICHA DE MATRÍCULA: PROCESO {{ $inscripcion->proceso?->nombre_pro }}</div>
            </td>
            <td style="width: 118px;">
                <div @class (['foto', 'con-foto' => $foto])>
                    @if ($foto)
                        <img src="{{ $foto }}" alt="" />
                    @else
                        <br /><br /><br /><br />SIN FOTO
                    @endif
                </div>
                <div class="reg">Reg. Nº {{ $inscripcion->codigo_ins }}</div>
            </td>
        </tr>
    </table>

    <table style="margin-top: 14px;">
        <tr>
            <td class="izq">
                <div class="seccion">{{ $inscripcion->sede?->nombre_sed }}</div>
                <div class="seccion">INFORMACIÓN CEPRE-UNU</div>
                <table class="datos">
                    {!! $fila('Fecha de inscripción', $inscripcion->fecha_inscripcion_ins?->format('d/m/Y H:i:s')) !!}
                    {!! $fila('Modalidad', $inscripcion->modalidadAdmision?->nombre_mad) !!}
                    {!! $fila('Turno', $inscripcion->turno?->nombre_tur) !!}
                    {!! $fila('Carrera', $inscripcion->carrera?->nombre_car) !!}
                </table>
            </td>
            <td class="der">
                <div class="seccion">INFORMACIÓN ACADÉMICA</div>
                <table class="datos">
                    {!! $fila('Nombre de I.E.', $inscripcion->colegio?->nombre_col ?? $inscripcion->nombre_colegio_extranjero_ins, '34%') !!}
                    {!! $fila('Tipo de colegio', $inscripcion->tipoColegio?->nombre_tco, '34%') !!}
                    {!! $fila('Año de egreso', (string) $inscripcion->anio_graduacion_ins, '34%') !!}
                </table>
            </td>
        </tr>
        <tr>
            <td class="izq linea" style="padding-top: 10px;">
                <div class="seccion">INFORMACIÓN PERSONAL</div>
                <table class="datos">
                    {!! $fila('Doc. identidad', $persona?->numero_documento_per) !!}
                    {!! $fila('Apellidos', trim($persona?->apellido_paterno_per.' '.$persona?->apellido_materno_per)) !!}
                    {!! $fila('Nombres', $persona?->nombres_per) !!}
                    {!! $fila('Fecha de nacimiento', $persona?->fecha_nacimiento_per?->format('j/n/Y')) !!}
                    {!! $fila('Sexo', $persona?->sexo_per?->value) !!}
                    {!! $fila('Estado civil', $persona?->estadoCivil?->nombre_eci) !!}
                    {!! $fila('Lengua materna', $lengua) !!}
                    {!! $fila('Ubigeo de nacimiento', $ubigeoNacimiento) !!}
                </table>
            </td>
            <td class="der linea" style="padding-top: 10px;">
                <div class="seccion">INFORMACIÓN DE CONTACTO</div>
                <table class="datos">
                    {!! $fila('Domicilio', $persona?->direccion_per, '38%') !!}
                    {!! $fila('Correo electrónico', $persona?->correo_electronico_per, '38%') !!}
                    {!! $fila('Cel. (estudiante)', $persona?->celular_per, '38%') !!}
                    {!! $fila('Cont. (emergencia)', $persona?->nombre_apoderado_per, '38%') !!}
                    {!! $fila('Cel. (emergencia)', $persona?->celular_apoderado_per, '38%') !!}
                </table>
            </td>
        </tr>
    </table>

    <div class="seccion" style="margin-top: 14px;">INFORMACIÓN DE PAGO</div>
    <table class="datos" style="width: 62%;">
        {!! $fila('Modalidad de pago', $regla?->conceptoPago?->nombre_cpg, '34%') !!}
        {!! $fila('Importe mínimo', $minimo === null ? null : number_format($minimo, 2), '34%') !!}
        {!! $fila('Importe total', $obligacion === null ? null : number_format((float) $obligacion->monto_total_iop, 2), '34%') !!}
    </table>

    <table class="pagos" style="margin-top: 10px;">
        <tr>
            <th style="width: 6%;">N°</th>
            <th style="width: 13%;">Fecha</th>
            <th>Ubicación</th>
            <th style="width: 20%;">N° Operación</th>
            <th style="width: 20%;">Importe</th>
        </tr>
        @forelse ($pagos as $pago)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $pago->fecha_pago_ipa?->format('j/n/Y') }}</td>
                <td>{{ $pago->importacionBancoDetalle?->ubicacion_ibd ?? '-' }}</td>
                <td>{{ $pago->numero_operacion_ipa }}</td>
                <td>S/. {{ number_format((float) $pago->monto_ipa, 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="5">Sin pagos registrados.</td></tr>
        @endforelse
        <tr>
            <td colspan="3" style="border: 0; border-top: 1.2px solid #222;"></td>
            <td><strong>TOTAL:</strong></td>
            <td><strong>S/. {{ number_format((float) $pagos->sum('monto_ipa'), 2) }}</strong></td>
        </tr>
    </table>

    <p class="negrita" style="margin: 24px 0 0;">PARENTESCO UNU / BECA ACADÉMICA: {{ $obligacion?->numero_documento_beneficio_iop ?? '-' }}</p>
    <p class="negrita" style="margin: 2px 0 16px;">OBSERVACIÓN: {{ $inscripcion->observacion_ins ?? '-' }}</p>

    <div class="linea"></div>
    <p class="jurada">“DECLARO BAJO JURAMENTO QUE, LOS DOCUMENTOS Y LOS DATOS PRESENTADOS EN LA PRESENTE FICHA SON REALES”</p>

    <table class="firmas">
        <tr>
            <td><div class="trazo">{{ $nombreCompleto }}</div></td>
            <td><div class="trazo">APODERADO (EN CASO DE SER MENOR DE EDAD)</div></td>
        </tr>
    </table>
</body>
</html>
