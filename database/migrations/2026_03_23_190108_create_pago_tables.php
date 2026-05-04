<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_concepto_pago', function (Blueprint $table): void {
            $table->increments('id_cpg');
            $table->string('codigo_cpg');
            $table->string('nombre_cpg');
            $table->text('descripcion_cpg')->nullable();
            $table->boolean('permite_cuotas_cpg')->default(false);
            $table->enum('estado_cpg', ['HABILITADO', 'DESHABILITADO'])->default('HABILITADO');
            $this->addAuditColumns($table);
            $table->unique('codigo_cpg', 'uk_codigo_cpg');
            $table->unique('nombre_cpg', 'uk_nombre_cpg');
        });

        Schema::create('tbl_regla_pago', function (Blueprint $table): void {
            $table->bigIncrements('id_rpa');
            $table->unsignedInteger('id_concepto_pago');
            $table->unsignedInteger('id_proceso')->nullable();
            $table->unsignedTinyInteger('id_modalidad_admision')->nullable();
            $table->decimal('monto_total_rpa', 10, 2);
            $table->decimal('monto_minimo_primera_cuota_rpa', 10, 2)->default(0);
            $table->unsignedTinyInteger('numero_maximo_cuotas_rpa')->default(1);
            $table->date('vigente_desde_rpa');
            $table->date('vigente_hasta_rpa')->nullable();
            $table->enum('estado_rpa', ['HABILITADO', 'DESHABILITADO'])->default('HABILITADO');
            $this->addAuditColumns($table);
            $table->index(['id_proceso', 'id_modalidad_admision', 'estado_rpa'], 'idx_regla_pago_contexto');
            $table->index(['vigente_desde_rpa', 'vigente_hasta_rpa'], 'idx_regla_pago_vigencia');
        });

        Schema::create('tbl_inscripcion_obligacion_pago', function (Blueprint $table): void {
            $table->bigIncrements('id_iop');
            $table->foreignId('id_inscripcion')->constrained('tbl_inscripcion', 'id_ins')->restrictOnDelete();
            $table->foreignId('id_regla_pago')->constrained('tbl_regla_pago', 'id_rpa')->restrictOnDelete();
            $table->decimal('monto_total_iop', 10, 2);
            $table->decimal('monto_pagado_iop', 10, 2)->default(0);
            $table->decimal('saldo_iop', 10, 2);
            $table->unsignedTinyInteger('numero_cuotas_iop')->default(1);
            $table->enum('estado_iop', ['PENDIENTE', 'PARCIAL', 'PAGADO', 'EXONERADO', 'ANULADO'])->default('PENDIENTE');
            $this->addAuditColumns($table);
            $table->index(['id_inscripcion', 'estado_iop'], 'idx_iop_inscripcion_estado');
        });

        Schema::create('tbl_importacion_banco', function (Blueprint $table): void {
            $table->bigIncrements('id_iba');
            $table->string('nombre_archivo_iba');
            $table->dateTime('fecha_importacion_iba');
            $table->unsignedInteger('total_registros_iba');
            $table->decimal('total_monto_iba', 14, 2);
            $table->enum('estado_iba', ['PENDIENTE', 'PROCESADO', 'OBSERVADO'])->default('PENDIENTE');
            $table->string('observacion_iba')->nullable();
            $this->addAuditColumns($table);
            $table->index(['estado_iba', 'fecha_importacion_iba'], 'idx_importacion_banco_estado_fecha');
        });

        Schema::create('tbl_importacion_banco_detalle', function (Blueprint $table): void {
            $table->bigIncrements('id_ibd');
            $table->foreignId('id_importacion_banco')->constrained('tbl_importacion_banco', 'id_iba')->restrictOnDelete();
            $table->string('numero_documento_ibd')->nullable();
            $table->string('nombre_depositante_ibd')->nullable();
            $table->string('numero_operacion_ibd')->nullable();
            $table->date('fecha_pago_ibd')->nullable();
            $table->decimal('monto_ibd', 10, 2);
            $table->text('texto_linea_ibd');
            $table->enum('estado_ibd', ['PENDIENTE', 'ASOCIADO', 'OBSERVADO'])->default('PENDIENTE');
            $this->addAuditColumns($table);
            $table->index('numero_documento_ibd', 'idx_ibd_numero_documento');
            $table->index('numero_operacion_ibd', 'idx_ibd_numero_operacion');
            $table->index('fecha_pago_ibd', 'idx_ibd_fecha_pago');
            $table->index(['estado_ibd', 'id_importacion_banco'], 'idx_ibd_estado_importacion');
        });

        Schema::create('tbl_inscripcion_pago', function (Blueprint $table): void {
            $table->bigIncrements('id_ipa');
            $table->foreignId('id_inscripcion_obligacion_pago')->constrained('tbl_inscripcion_obligacion_pago', 'id_iop')->restrictOnDelete();
            $table->foreignId('id_importacion_banco_detalle')->nullable()->constrained('tbl_importacion_banco_detalle', 'id_ibd')->restrictOnDelete();
            $table->unsignedTinyInteger('numero_cuota_ipa')->default(1);
            $table->decimal('monto_ipa', 10, 2);
            $table->date('fecha_pago_ipa');
            $table->string('numero_operacion_ipa')->nullable();
            $table->string('voucher_url_ipa')->nullable();
            $table->enum('estado_validacion_ipa', ['PENDIENTE', 'VERIFICADO', 'RECHAZADO'])->default('PENDIENTE');
            $table->string('observacion_validacion_ipa')->nullable();
            $table->dateTime('validado_en')->nullable();
            $table->foreignId('id_usuario_validador')->nullable()->constrained('tbl_usuario', 'id_usu')->restrictOnDelete();
            $this->addAuditColumns($table);
            $table->unique('id_importacion_banco_detalle', 'uk_ipa_importacion_detalle');
            $table->index('numero_operacion_ipa', 'idx_ipa_numero_operacion');
            $table->index(['id_inscripcion_obligacion_pago', 'estado_validacion_ipa'], 'idx_ipa_obligacion_estado');
            $table->index(['fecha_pago_ipa', 'estado_validacion_ipa'], 'idx_ipa_fecha_estado');
        });

        $this->addAuditForeignKeys('tbl_concepto_pago');
        $this->addAuditForeignKeys('tbl_regla_pago');
        $this->addAuditForeignKeys('tbl_inscripcion_obligacion_pago');
        $this->addAuditForeignKeys('tbl_importacion_banco');
        $this->addAuditForeignKeys('tbl_importacion_banco_detalle');
        $this->addAuditForeignKeys('tbl_inscripcion_pago');

        Schema::table('tbl_regla_pago', function (Blueprint $table): void {
            $table->foreign('id_concepto_pago', 'fk_regla_pago_concepto')->references('id_cpg')->on('tbl_concepto_pago')->restrictOnDelete();
            $table->foreign('id_proceso', 'fk_regla_pago_proceso')->references('id_pro')->on('tbl_proceso')->restrictOnDelete();
            $table->foreign('id_modalidad_admision', 'fk_regla_pago_modalidad')->references('id_mad')->on('tbl_modalidad_admision')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_inscripcion_pago');
        Schema::dropIfExists('tbl_importacion_banco_detalle');
        Schema::dropIfExists('tbl_importacion_banco');
        Schema::dropIfExists('tbl_inscripcion_obligacion_pago');
        Schema::dropIfExists('tbl_regla_pago');
        Schema::dropIfExists('tbl_concepto_pago');
    }

    private function addAuditColumns(Blueprint $table): void
    {
        $table->timestamp('creado_en')->useCurrent();
        $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();
        $table->timestamp('eliminado_en')->nullable();
        $table->unsignedBigInteger('id_usuario_creador')->nullable();
        $table->unsignedBigInteger('id_usuario_actualizador')->nullable();
        $table->unsignedBigInteger('id_usuario_eliminador')->nullable();
    }

    private function addAuditForeignKeys(string $tableName): void
    {
        Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
            $table->foreign('id_usuario_creador', "{$tableName}_fk_usuario_creador")->references('id_usu')->on('tbl_usuario')->restrictOnDelete();
            $table->foreign('id_usuario_actualizador', "{$tableName}_fk_usuario_actualizador")->references('id_usu')->on('tbl_usuario')->restrictOnDelete();
            $table->foreign('id_usuario_eliminador', "{$tableName}_fk_usuario_eliminador")->references('id_usu')->on('tbl_usuario')->restrictOnDelete();
        });
    }
};
