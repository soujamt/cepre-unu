<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_importacion_banco', function (Blueprint $table): void {
            $table->string('moneda_iba', 3)->nullable()->after('nombre_archivo_iba');
            $table->date('fecha_lote_iba')->nullable()->after('fecha_importacion_iba');
            $table->unsignedInteger('total_registros_reportado_iba')->nullable()->after('total_registros_iba');
            $table->decimal('total_monto_reportado_iba', 14, 3)->nullable()->after('total_monto_iba');
        });

        Schema::table('tbl_importacion_banco_detalle', function (Blueprint $table): void {
            $table->string('codigo_canal_ibd', 4)->nullable()->after('numero_operacion_ibd');
            $table->string('codigo_oficina_ibd', 5)->nullable()->after('codigo_canal_ibd');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_importacion_banco_detalle', function (Blueprint $table): void {
            $table->dropColumn(['codigo_canal_ibd', 'codigo_oficina_ibd']);
        });

        Schema::table('tbl_importacion_banco', function (Blueprint $table): void {
            $table->dropColumn([
                'moneda_iba',
                'fecha_lote_iba',
                'total_registros_reportado_iba',
                'total_monto_reportado_iba',
            ]);
        });
    }
};
