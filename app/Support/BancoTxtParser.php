<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use RuntimeException;

class BancoTxtParser
{
    /**
     * @return array{
     *     header: array<string, mixed>,
     *     details: list<array<string, mixed>>,
     *     trailer: array<string, mixed>,
     *     summary: array<string, mixed>
     * }
     */
    public function parse(string $content): array
    {
        $normalizedContent = rtrim($content, "\r\n");

        $lines = collect(preg_split('/\r\n|\r|\n/', $normalizedContent))
            ->map(fn (?string $line, int $index): array => [
                'number' => $index + 1,
                'content' => $line,
            ])
            ->filter(fn (array $line): bool => filled($line['content']))
            ->values();

        if ($lines->count() < 3) {
            throw new RuntimeException('El archivo TXT no tiene la estructura minima esperada.');
        }

        $headerLine = $lines->first();
        $trailerLine = $lines->last();
        $detailLines = $lines->slice(1, $lines->count() - 2)->values();

        if (! is_array($headerLine) || ! is_string($headerLine['content']) || ! str_starts_with($headerLine['content'], '01')) {
            throw new RuntimeException('La cabecera del archivo TXT es invalida. Revise la linea 1.');
        }

        if (! is_array($trailerLine) || ! is_string($trailerLine['content']) || ! str_starts_with($trailerLine['content'], '03')) {
            $lineaCierre = is_array($trailerLine) ? (int) $trailerLine['number'] : $lines->count();

            throw new RuntimeException("El cierre del archivo TXT es invalido. Revise la linea {$lineaCierre}.");
        }

        $header = $this->parseHeader($headerLine['content'], (int) $headerLine['number']);
        $details = $detailLines
            ->map(fn (array $line): array => $this->parseDetail($line['content'], (int) $line['number']))
            ->all();
        $trailer = $this->parseTrailer($trailerLine['content'], (int) $trailerLine['number']);

        $summary = [
            'cantidad_detalles' => count($details),
            'total_detalles' => round(collect($details)->sum('monto'), 3),
            'cuadra_registros' => count($details) === $trailer['total_registros'],
            'cuadra_montos' => round(collect($details)->sum('monto'), 3) === round($trailer['total_monto'], 3),
        ];

        return [
            'header' => $header,
            'details' => $details,
            'trailer' => $trailer,
            'summary' => $summary,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function parseHeader(string $line, int $lineNumber): array
    {
        $this->ensureFixedWidthLine($line, $lineNumber, 'cabecera');

        return [
            'tipo_registro' => substr($line, 0, 2),
            'cuenta_servicio' => trim(substr($line, 2, 14)),
            'moneda' => $this->decodeText(substr($line, 16, 3)),
            'fecha_lote' => $this->parseDate(trim(substr($line, 19, 8)))?->toDateString(),
            'referencia_lote' => $this->decodeText(substr($line, 27, 18)),
            'texto_linea' => $this->decodeLine($line),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function parseDetail(string $line, int $lineNumber): array
    {
        $this->ensureFixedWidthLine($line, $lineNumber, 'detalle');

        $documentoRaw = trim(substr($line, 32, 11));
        $nombreDepositante = $this->decodeText(substr($line, 43, 35));
        $monto = $this->parseAmount(substr($line, 80, 15));
        $montoControl = $this->parseAmount(substr($line, 95, 15));
        $numeroOperacion = trim(substr($line, 123, 12));
        $fechaPago = $this->parseDate(trim(substr($line, 135, 8)))?->toDateString();
        $origenRaw = trim(substr($line, 143, 8));
        $origen = $this->parseOrigin($origenRaw);

        return [
            'tipo_registro' => substr($line, 0, 2),
            'numero_documento' => $documentoRaw !== '' ? $documentoRaw : null,
            'numero_documento_normalizado' => $this->normalizeDocument($documentoRaw),
            'nombre_depositante' => $nombreDepositante,
            'monto' => $monto,
            'monto_control' => $montoControl,
            'numero_operacion' => $numeroOperacion !== '' ? $numeroOperacion : null,
            'numero_operacion_normalizado' => $this->normalizeOperation($numeroOperacion),
            'numero_operacion_visible' => $this->visibleOperation($numeroOperacion),
            'fecha_pago' => $fechaPago,
            'codigo_oficina' => $origen['codigo_oficina'],
            'codigo_canal' => $origen['codigo_canal'],
            'origen_raw' => $origenRaw !== '' ? $origenRaw : null,
            'texto_linea' => $this->decodeLine($line),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function parseTrailer(string $line, int $lineNumber): array
    {
        $this->ensureFixedWidthLine($line, $lineNumber, 'cierre');

        return [
            'tipo_registro' => substr($line, 0, 2),
            'total_registros' => (int) trim(substr($line, 2, 9)),
            'total_monto' => $this->parseAmount(substr($line, 11, 15)),
            'total_monto_control' => $this->parseAmount(substr($line, 26, 15)),
            'texto_linea' => $this->decodeLine($line),
        ];
    }

    private function ensureFixedWidthLine(string $line, int $lineNumber, string $recordType): void
    {
        $length = strlen($line);

        if ($length !== 152) {
            throw new RuntimeException("El archivo TXT contiene una linea con longitud invalida. Linea {$lineNumber} ({$recordType}): se encontraron {$length} caracteres y se esperaban 152.");
        }
    }

    private function parseAmount(string $value): float
    {
        $numeric = preg_replace('/\D/', '', $value) ?? '0';

        if ($numeric === '') {
            return 0.0;
        }

        return ((float) $numeric) / 100;
    }

    private function parseDate(string $value): ?CarbonImmutable
    {
        if ($value === '' || strlen($value) !== 8) {
            return null;
        }

        return CarbonImmutable::createFromFormat('Ymd', $value) ?: null;
    }

    /**
     * @return array{codigo_oficina:?string,codigo_canal:?string}
     */
    private function parseOrigin(string $value): array
    {
        if ($value === '') {
            return [
                'codigo_oficina' => null,
                'codigo_canal' => null,
            ];
        }

        if (preg_match('/^(?<oficina>\d{4,5})(?<canal>.+)$/', $value, $matches) === 1) {
            return [
                'codigo_oficina' => $matches['oficina'],
                'codigo_canal' => $matches['canal'],
            ];
        }

        if (preg_match('/^\d+$/', $value) === 1) {
            return [
                'codigo_oficina' => $value,
                'codigo_canal' => null,
            ];
        }

        return [
            'codigo_oficina' => null,
            'codigo_canal' => $value,
        ];
    }

    private function decodeText(string $value): ?string
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            return null;
        }

        $decoded = mb_convert_encoding($trimmed, 'UTF-8', 'Windows-1252');

        return trim($decoded);
    }

    private function decodeLine(string $value): string
    {
        return rtrim(mb_convert_encoding(rtrim($value), 'UTF-8', 'Windows-1252'));
    }

    private function normalizeDocument(string $value): ?string
    {
        $normalized = ltrim($value, '0');

        if ($normalized === '') {
            return null;
        }

        return $normalized;
    }

    private function normalizeOperation(string $value): ?string
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            return null;
        }

        if (preg_match('/^\d+$/', $trimmed) !== 1) {
            return $trimmed;
        }

        $normalized = ltrim($trimmed, '0');

        return $normalized !== '' ? $normalized : '0';
    }

    private function visibleOperation(string $value): ?string
    {
        $normalized = $this->normalizeOperation($value);

        if ($normalized === null || $normalized === '') {
            return null;
        }

        if (preg_match('/^\d{6,}$/', $normalized) === 1) {
            return substr($normalized, -5);
        }

        return $normalized;
    }
}
