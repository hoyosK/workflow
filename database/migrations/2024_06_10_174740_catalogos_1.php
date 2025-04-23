<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('catSexo', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('codigo', 150)->nullable();
            $table->string('descripcion', 250)->nullable();
            $table->boolean('activo')->nullable()->default(true);
            $table->boolean('flock')->nullable()->default(false);
        });

        Schema::create('catZonaEmision', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('codigo', 150)->nullable();
            $table->string('descripcion', 250)->nullable();
            $table->boolean('activo')->nullable()->default(true);
            $table->boolean('flock')->nullable()->default(false);
        });

        Schema::create('catNacionalidad', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('codigo', 150)->nullable();
            $table->string('descripcion', 250)->nullable();
            $table->boolean('activo')->nullable()->default(true);
            $table->boolean('flock')->nullable()->default(false);
        });

        Schema::create('catTipoCliente', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('codigo', 150)->nullable();
            $table->string('descripcion', 250)->nullable();
            $table->boolean('activo')->nullable()->default(true);
            $table->boolean('flock')->nullable()->default(false);
        });

        Schema::create('catTipoSociedad', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('codigo', 150)->nullable();
            $table->string('descripcion', 250)->nullable();
            $table->boolean('activo')->nullable()->default(true);
            $table->boolean('flock')->nullable()->default(false);
        });

        Schema::create('catActividadEconomica', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('codigo', 150)->nullable();
            $table->string('descripcion', 250)->nullable();
            $table->boolean('activo')->nullable()->default(true);
            $table->boolean('flock')->nullable()->default(false);
        });

        Schema::create('catTipoUso', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('codigo', 150)->nullable();
            $table->string('descripcion', 250)->nullable();
            $table->boolean('activo')->nullable()->default(true);
            $table->boolean('flock')->nullable()->default(false);
        });

        Schema::create('catTipoCombustible', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('codigo', 150)->nullable();
            $table->string('descripcion', 250)->nullable();
            $table->boolean('activo')->nullable()->default(true);
            $table->boolean('flock')->nullable()->default(false);
        });

        Schema::create('catTipoTecnologia', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('codigo', 150)->nullable();
            $table->string('descripcion', 250)->nullable();
            $table->boolean('activo')->nullable()->default(true);
            $table->boolean('flock')->nullable()->default(false);
        });

        Schema::create('catTipoCartera', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('codigo', 150)->nullable();
            $table->string('descripcion', 250)->nullable();
            $table->boolean('activo')->nullable()->default(true);
            $table->boolean('flock')->nullable()->default(false);
        });

        Schema::create('catSubtipoMovimiento', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('codigo', 150)->nullable();
            $table->string('descripcion', 250)->nullable();
            $table->boolean('activo')->nullable()->default(true);
            $table->boolean('flock')->nullable()->default(false);
        });

        Schema::create('catDepartamento', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('codigo', 150)->nullable();
            $table->string('descripcion', 250)->nullable();
            $table->boolean('activo')->nullable()->default(true);
            $table->boolean('flock')->nullable()->default(false);
        });

        Schema::create('catMunicipio', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('codigo', 150)->nullable();
            $table->string('descripcion', 250)->nullable();
            $table->boolean('activo')->nullable()->default(true);
            $table->boolean('flock')->nullable()->default(false);
        });

        Schema::create('catCodigoAlarma', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('codigo', 150)->nullable();
            $table->string('descripcion', 250)->nullable();
            $table->boolean('activo')->nullable()->default(true);
            $table->boolean('flock')->nullable()->default(false);
        });

        Schema::create('catPromociones', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('codigo', 150)->nullable();
            $table->string('descripcion', 250)->nullable();
            $table->boolean('activo')->nullable()->default(true);
            $table->boolean('flock')->nullable()->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('catSexo');
        Schema::dropIfExists('catZonaEmision');
        Schema::dropIfExists('catNacionalidad');
        Schema::dropIfExists('catTipoCliente');
        Schema::dropIfExists('catTipoSociedad');
        Schema::dropIfExists('catActividadEconomica');
        Schema::dropIfExists('catTipoUso');
        Schema::dropIfExists('catTipoCombustible');
        Schema::dropIfExists('catTipoTecnologia');
        Schema::dropIfExists('catTipoCartera');
        Schema::dropIfExists('catSubtipoMovimiento');
        Schema::dropIfExists('catDepartamento');
        Schema::dropIfExists('catMunicipio');
        Schema::dropIfExists('catCodigoAlarma');
        Schema::dropIfExists('catPromociones');
    }
};
