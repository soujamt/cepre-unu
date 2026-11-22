<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * @var array<int, string>
     */
    private array $romanos = [1 => 'I', 2 => 'II', 3 => 'III'];

    /**
     * Los procesos se nombran con el periodo en romanos (2026-I, 2026-II, 2026-III),
     * como en el Drive y en los documentos del CEPRE. Renombra los que usaban número
     * (2026-1 → 2026-I). El código interno (2026_1) no cambia.
     */
    public function up(): void
    {
        $this->renombrar(function (string $nombre): ?string {
            return preg_match('/^(\d{4})-([1-3])$/', $nombre, $partes) ? $partes[1].'-'.$this->romanos[(int) $partes[2]] : null;
        });
    }

    public function down(): void
    {
        $this->renombrar(function (string $nombre): ?string {
            return preg_match('/^(\d{4})-(I|II|III)$/', $nombre, $partes) ? $partes[1].'-'.array_search($partes[2], $this->romanos, true) : null;
        });
    }

    /**
     * @param  callable(string): ?string  $nuevoNombre
     */
    private function renombrar(callable $nuevoNombre): void
    {
        DB::table('tbl_proceso')->orderBy('id_pro')->get(['id_pro', 'nombre_pro'])->each(function (object $proceso) use ($nuevoNombre): void {
            $nombre = $nuevoNombre((string) $proceso->nombre_pro);

            if ($nombre !== null && ! DB::table('tbl_proceso')->where('nombre_pro', $nombre)->exists()) {
                DB::table('tbl_proceso')->where('id_pro', $proceso->id_pro)->update(['nombre_pro' => $nombre]);
            }
        });
    }
};
