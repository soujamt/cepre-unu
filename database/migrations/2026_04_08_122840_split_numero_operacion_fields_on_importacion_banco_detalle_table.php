<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_importacion_banco_detalle', function (Blueprint $table): void {
            $table->string('numero_operacion_raw_ibd')->nullable()->after('nombre_depositante_ibd');
            $table->string('numero_operacion_visible_ibd')->nullable()->after('numero_operacion_raw_ibd');
        });

        DB::table('tbl_importacion_banco_detalle')
            ->orderBy('id_ibd')
            ->get()
            ->each(function (object $detalle): void {
                $raw = $detalle->numero_operacion_ibd;

                DB::table('tbl_importacion_banco_detalle')
                    ->where('id_ibd', $detalle->id_ibd)
                    ->update([
                        'numero_operacion_raw_ibd' => $raw,
                        'numero_operacion_visible_ibd' => $this->visibleOperation($raw),
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('tbl_importacion_banco_detalle', function (Blueprint $table): void {
            $table->dropColumn([
                'numero_operacion_raw_ibd',
                'numero_operacion_visible_ibd',
            ]);
        });
    }

    private function visibleOperation(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $trimmed = trim($value);

        if (preg_match('/^\d{6,}$/', $trimmed) === 1) {
            return substr($trimmed, -5);
        }

        return $trimmed;
    }
};
