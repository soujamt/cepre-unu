<?php

use App\Support\BancoTxtParser;

it('parses the fixed-width bank txt structure correctly', function () {
    $content = collect([
        '0120154598244000PEN20260319001104410100008635',
        '02                              00047467212                                     0000000000450000000000000450000000000000000007799041670202603190204WNET ',
        '02                              00047467212                                     0000000000450000000000000450000000000000000007799041672202603190204WNET ',
        '02                              00047467212                                     0000000000450000000000000450000000000000000007799041674202603190204WNET ',
        '02                              00007450332LUCY BETTY FALCON ROJAS              00000000000300000000000000300000000000000000008170416762026031901068Y%G ',
        '02                              00007450332LUCY BETTY FALCON ROJAS              00000000001200000000000001200000000000000000008170416782026031901069S%U ',
        '02                              00070365280                                     0000000000450000000000000450000000000000000007799041680202603190204WNET ',
        '02                              00040013531FREDY FERNANDEZ MARTINEZ             0000000000200000000000000200000000000000000000982041640202603190101RA13 ',
        '02                              00072913064                                     0000000000337500000000000337500000000000000007799041702202603190204WNET ',
        '02                              00041031424BETTY EDELVINA PUN CARDENA           00000000004500000000000004500000000000000000008170417082026031901069N%Z ',
        '02                              00043178854                                     0000000000600000000000000600000000000000000007799041710202603190204WNET ',
        '02                              00075423553LEIDY Y. PINAN ESPINOZA              00000000009000000000000009000000000000000000002330416962026031901016H15 ',
        '02                              00071214636                                     0000000000400000000000000400000000000000000007799041692202603190204WNET ',
        '02                              00071447628                                     0000000000600000000000000600000000000000000007799041656202603190204WNET ',
        '02                              00028270673                                     0000000000150000000000000150000000000000000007799041668202603190204WNET ',
        '02                              00076438523                                     0000000000300000000000000300000000000000000007799041666202603190204WNET ',
        '02                              00044175290                                     0000000000450000000000000450000000000000000007799041618202603190204WNET ',
        '02                              00075616115SULLYKATTIA TORRES VASQUEZ           00000000004500000000000004500000000000000000008170416602026031901068X%V ',
        '02                              00040869180LOURDES SAJAMI AREVALO               00000000004500000000000004500000000000000000008170416642026031901068T%H ',
        '03000000018000000000723750000000000723750000000000000000',
    ])
        ->map(fn (string $line): string => str_pad($line, 152, ' '))
        ->implode(PHP_EOL);

    $result = app(BancoTxtParser::class)->parse($content);

    expect($result['header']['tipo_registro'])->toBe('01')
        ->and($result['header']['cuenta_servicio'])->toBe('20154598244000')
        ->and($result['header']['moneda'])->toBe('PEN')
        ->and($result['header']['fecha_lote'])->toBe('2026-03-19');

    expect($result['details'])->toHaveCount(18)
        ->and($result['details'][0]['numero_documento'])->toBe('00047467212')
        ->and($result['details'][0]['numero_documento_normalizado'])->toBe('47467212')
        ->and($result['details'][0]['nombre_depositante'])->toBeNull()
        ->and($result['details'][0]['monto'])->toBe(450.0)
        ->and($result['details'][0]['numero_operacion'])->toBe('007799041670')
        ->and($result['details'][0]['numero_operacion_normalizado'])->toBe('7799041670')
        ->and($result['details'][0]['numero_operacion_visible'])->toBe('41670')
        ->and($result['details'][0]['fecha_pago'])->toBe('2026-03-19')
        ->and($result['details'][0]['codigo_oficina'])->toBe('0204')
        ->and($result['details'][0]['codigo_canal'])->toBe('WNET');

    expect($result['details'][3]['nombre_depositante'])->toBe('LUCY BETTY FALCON ROJAS')
        ->and($result['details'][3]['monto'])->toBe(30.0)
        ->and($result['details'][3]['codigo_oficina'])->toBe('01068')
        ->and($result['details'][3]['codigo_canal'])->toBe('Y%G');

    expect($result['details'][6]['nombre_depositante'])->toBe('FREDY FERNANDEZ MARTINEZ')
        ->and($result['details'][6]['codigo_oficina'])->toBe('0101')
        ->and($result['details'][6]['codigo_canal'])->toBe('RA13');

    expect($result['details'][10]['nombre_depositante'])->toBe('LEIDY Y. PINAN ESPINOZA')
        ->and($result['details'][10]['codigo_oficina'])->toBe('01016')
        ->and($result['details'][10]['codigo_canal'])->toBe('H15');

    expect($result['trailer']['tipo_registro'])->toBe('03')
        ->and($result['trailer']['total_registros'])->toBe(18)
        ->and($result['trailer']['total_monto'])->toBe(7237.5)
        ->and($result['summary']['cantidad_detalles'])->toBe(18)
        ->and($result['summary']['total_detalles'])->toBe(7237.5)
        ->and($result['summary']['cuadra_registros'])->toBeTrue()
        ->and($result['summary']['cuadra_montos'])->toBeTrue();
});

it('decodes depositor names encoded in windows-1252', function () {
    $lineaCabecera = str_pad('0120154598244000PEN20260319001104410100008635', 152, ' ');
    $lineaDetalle = '02                              00075423553LEIDY Y. PI'.chr(209).'AN ESPINOZA              00000000009000000000000009000000000000000000002330416962026031901016H15 ';
    $lineaDetalle = str_pad($lineaDetalle, 152, ' ');
    $lineaCierre = str_pad('03000000001000000000090000000000000090000000000000000000', 152, ' ');

    $content = implode(PHP_EOL, [$lineaCabecera, $lineaDetalle, $lineaCierre]);

    $result = app(BancoTxtParser::class)->parse($content);

    expect($result['details'][0]['nombre_depositante'])->toBe('LEIDY Y. PIÑAN ESPINOZA')
        ->and($result['details'][0]['texto_linea'])->toContain('PIÑAN');
});

it('fails when the txt file does not contain the expected structure', function () {
    expect(fn () => app(BancoTxtParser::class)->parse("01CABECERA\n03CIERRE"))
        ->toThrow(RuntimeException::class);
});
