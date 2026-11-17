<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <title>Carnet CEPRE-UNU</title>
    <style>
        @page { margin: 24px 28px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9.5px; color: #111; }
        .carnet { width: 100%; border: 1.5px solid #2f7d1f; border-collapse: collapse; }
        .carnet td { vertical-align: top; }
        .izq { width: 52%; border-right: 1.5px solid #2f7d1f; }
        .franja { background: #43a01f; color: #fff; padding: 6px 8px; }
        .franja td { vertical-align: middle; }
        .franja .t1 { font-weight: bold; font-size: 11.5px; color: #f6e94b; }
        .franja .t2 { font-size: 10.5px; }
        .franja .t3 { font-size: 13px; font-weight: bold; }
        .datos { padding: 6px 8px; }
        .datos td { padding: 1px 0; }
        .datos .et { width: 34%; font-weight: bold; }
        .foto { width: 76px; height: 101px; border: 1px solid #999; text-align: center; color: #999; font-size: 8px; }
        .foto img { width: 76px; height: 101px; }
        .pie-foto { text-align: center; font-size: 8px; margin-top: 3px; }
        .der { padding: 8px; text-align: center; }
        .firma { height: 80px; }
        .firma img { max-height: 80px; max-width: 150px; }
        .director { font-size: 9px; }
        .nota { font-size: 7.5px; margin-top: 6px; }
        .salto { page-break-after: always; }
    </style>
</head>
<body>
    @foreach ($carnets as $carnet)
        <table class="carnet">
            <tr>
                <td class="izq">
                    <table class="franja" style="width: 100%;">
                        <tr>
                            <td style="width: 48px;">@if ($logo)<img src="{{ $logo }}" alt="" style="width: 44px;" />@endif</td>
                            <td style="text-align: center;">
                                <div class="t1">UNIVERSIDAD NACIONAL DE UCAYALI</div>
                                <div class="t2">CARNET PREUNIVERSITARIO</div>
                                <div class="t3">CEPRE-UNU</div>
                            </td>
                        </tr>
                    </table>
                    <table style="width: 100%;">
                        <tr>
                            <td class="datos">
                                <table style="width: 100%;">
                                    <tr><td class="et">TURNO</td><td>: {{ $carnet['turno'] }}</td></tr>
                                    <tr><td class="et">MODALIDAD</td><td>: {{ $carnet['modalidad'] }}</td></tr>
                                    <tr><td class="et">CARRERA</td><td>: {{ $carnet['carrera'] }}</td></tr>
                                    <tr><td class="et">DOCUMENTO</td><td>: {{ $carnet['documento'] }}</td></tr>
                                    <tr><td class="et">APELLIDOS</td><td>: {{ $carnet['apellidos'] }}</td></tr>
                                    <tr><td class="et">NOMBRES</td><td>: {{ $carnet['nombres'] }}</td></tr>
                                </table>
                            </td>
                            <td style="width: 86px; padding: 6px 8px 6px 0;">
                                <div class="foto">
                    @if ($carnet['foto'])
                        <img src="{{ $carnet['foto'] }}" alt="" />
                    @else
                        <br /><br /><br />SIN FOTO
                    @endif
                </div>
                                <div class="pie-foto">{{ $carnet['sede'] }}<br />{{ $carnet['proceso'] }}</div>
                            </td>
                        </tr>
                    </table>
                </td>
                <td class="der">
                    <table style="width: 100%;">
                        <tr>
                            <td style="text-align: center; vertical-align: bottom;">
                                <div class="firma">@if ($firma)<img src="{{ $firma }}" alt="" />@endif</div>
                                <div class="director">{{ $director }}</div>
                                <div class="director">{{ $cargo }}</div>
                            </td>
                            <td style="width: 110px; text-align: center;">
                                <img src="{{ $carnet['qr'] }}" alt="" style="width: 105px; height: 105px;" />
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
