<?php

namespace Database\Seeders\Concerns;

use Illuminate\Support\Facades\DB;
use JsonException;
use RuntimeException;

trait SeedsJsonCatalog
{
    /**
     * @return list<array<string, mixed>>
     */
    protected function loadRecords(string $fileName): array
    {
        $contents = file_get_contents(database_path("seeders/data/{$fileName}"));

        if ($contents === false) {
            throw new RuntimeException("No se pudo leer el archivo de semillas [{$fileName}].");
        }

        $contents = preg_replace('/^\xEF\xBB\xBF/', '', $contents) ?? $contents;

        try {
            /** @var list<array<string, mixed>> $records */
            $records = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException("No se pudo decodificar el archivo de semillas [{$fileName}].", previous: $exception);
        }

        return $records;
    }

    /**
     * @param  list<array<string, mixed>>  $records
     * @param  list<string>  $uniqueBy
     * @param  list<string>  $updateColumns
     */
    protected function upsertRecords(
        string $table,
        array $records,
        array $uniqueBy,
        array $updateColumns,
        int $chunkSize = 500
    ): void {
        foreach (array_chunk($records, $chunkSize) as $chunk) {
            DB::table($table)->upsert($chunk, $uniqueBy, $updateColumns);
        }
    }
}
