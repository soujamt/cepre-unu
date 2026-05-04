<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_regla_pago', function (Blueprint $table): void {
            $table->foreignId('id_convenio_proceso')
                ->nullable()
                ->after('id_modalidad_admision')
                ->constrained('tbl_convenio_proceso', 'id_cpr')
                ->restrictOnDelete();

            $table->boolean('permite_cuotas_rpa')
                ->default(false)
                ->after('monto_total_rpa');

            $table->boolean('requiere_pago_completo_rpa')
                ->default(false)
                ->after('numero_maximo_cuotas_rpa');
        });

        DB::table('tbl_regla_pago')
            ->where('numero_maximo_cuotas_rpa', '>', 1)
            ->update(['permite_cuotas_rpa' => true]);

        Schema::table('tbl_regla_pago', function (Blueprint $table): void {
            $table->unsignedInteger('id_proceso')->nullable(false)->change();

            $table->index(
                ['id_proceso', 'id_modalidad_admision', 'id_convenio_proceso', 'estado_rpa'],
                'idx_regla_pago_contexto_detallado'
            );
        });
    }

    public function down(): void
    {
        Schema::table('tbl_regla_pago', function (Blueprint $table): void {
            $table->dropIndex('idx_regla_pago_contexto_detallado');
            $table->dropForeign(['id_convenio_proceso']);
            $table->dropColumn([
                'id_convenio_proceso',
                'permite_cuotas_rpa',
                'requiere_pago_completo_rpa',
            ]);
        });

        Schema::table('tbl_regla_pago', function (Blueprint $table): void {
            $table->unsignedInteger('id_proceso')->nullable()->change();
        });
    }
};
