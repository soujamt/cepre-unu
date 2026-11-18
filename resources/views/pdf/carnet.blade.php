<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <title>Carnet CEPRE-UNU</title>
    <style>
        @page { margin: 22px 24px; }
        body { font-family: Helvetica, Arial, sans-serif; font-size: 10.5px; color: #111; }
        table { border-collapse: collapse; }
        td { vertical-align: top; padding: 0; }
        .carnet { width: 100%; border: 1.6px solid #2e7d1e; }
        .izq { width: 50%; border-right: 1.6px solid #2e7d1e; }
        .der { width: 50%; }
        .franja { width: 100%; background: #3fa52b; }
        .franja td { vertical-align: middle; }
        .t1 { font-size: 13.5px; font-weight: bold; color: #f4ea3a; }
        .t2 { font-size: 12px; color: #fff; }
        .t3 { font-size: 15px; color: #fff; }
        .cuerpo { position: relative; }
        .marca { position: absolute; left: 105px; top: 12px; width: 92px; opacity: 0.09; }
        .datos { margin: 8px 0 0 12px; }
        .datos td { padding: 1.1px 0; font-size: 10.5px; }
        .datos .et { width: 80px; }
        .foto { width: 82px; height: 104px; text-align: center; color: #999; font-size: 8px; border: 1px solid #bbb; }
        .foto.con-foto { border: 0; }
        .foto img { width: 82px; height: 104px; }
        .pie-foto { text-align: center; font-size: 8px; line-height: 1.25; margin-top: 3px; }
        .firma { height: 100px; text-align: center; }
        .firma img { max-height: 100px; max-width: 165px; }
        .director { font-size: 9.5px; text-align: center; line-height: 1.3; }
        .nota { font-size: 8px; text-align: center; padding: 4px 18px 6px; }
        .salto { page-break-after: always; }
    </style>
</head>
<body>
    @foreach ($carnets as $carnet)
        <table class="carnet">
            <tr>
                <td class="izq">
                    <table class="franja">
                        <tr>
                            <td style="width: 62px; padding: 5px 4px 5px 10px;">@if ($logo)<img src="{{ $logo }}" alt="" style="width: 48px;" />@endif</td>
                            <td style="text-align: center; padding: 5px 8px 4px 0;">
                                <div class="t1">UNIVERSIDAD NACIONAL DE UCAYALI</div>
                                <div class="t2">CARNET PREUNIVERSITARIO</div>
                                <div class="t3">CEPRE-UNU</div>
                            </td>
                        </tr>
                    </table>
                    <div class="cuerpo">
                        @if ($logo)
                            <img src="{{ $logo }}" alt="" class="marca" />
                        @endif
                        <table style="width: 100%;">
                            <tr>
                                <td>
                                    <table class="datos">
                                        <tr><td class="et">TURNO</td><td>: {{ $carnet['turno'] }}</td></tr>
                                        <tr><td class="et">MODALIDAD</td><td>: {{ $carnet['modalidad'] }}</td></tr>
                                        <tr><td class="et">CARRERA</td><td>: {{ $carnet['carrera'] }}</td></tr>
                                        <tr><td class="et">DOCUMENTO</td><td>: {{ $carnet['documento'] }}</td></tr>
                                        <tr><td class="et">APELLIDOS</td><td>: {{ $carnet['apellidos'] }}</td></tr>
                                        <tr><td class="et">NOMBRES</td><td>: {{ $carnet['nombres'] }}</td></tr>
                                    </table>
                                </td>
                                <td style="width: 96px; padding: 4px 8px 6px 0;">
                                    <div @class (['foto', 'con-foto' => $carnet['foto']])>
                                        @if ($carnet['foto'])
                                            <img src="{{ $carnet['foto'] }}" alt="" />
                                        @else
                                            <br /><br /><br /><br />SIN FOTO
                                        @endif
                                    </div>
                                    <div class="pie-foto">{{ $carnet['sede'] }}<br />{{ $carnet['proceso'] }}</div>
                                </td>
                            </tr>
                        </table>
                    </div>
                </td>
                <td class="der">
                    <table style="width: 100%;">
                        <tr>
                            <td style="padding: 10px 0 0 10px;">
                                <div class="firma">
                                    @if ($firma)
                                        <img src="{{ $firma }}" alt="" />
                                    @endif
                                </div>
                                <div class="director">{{ $director }}</div>
                                <div class="director">{{ $cargo }}</div>
                            </td>
                            <td style="width: 132px; padding: 10px 12px 0 4px; text-align: center;">
                                <img src="{{ $carnet['qr'] }}" alt="" style="width: 120px; height: 120px;" />
                            </td>
                        </tr>
                    </table>
                    @if ($nota)
                        <div class="nota">Nota: {{ $nota }}</div>
                    @endif
                </td>
            </tr>
        </table>
        @if (! $loop->last)
            <div class="salto"></div>
        @endif
    @endforeach
</body>
</html>
