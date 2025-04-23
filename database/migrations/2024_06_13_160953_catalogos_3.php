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
        Schema::create('catTipoAsignacion', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('codigo', 150)->nullable();
            $table->string('descripcion', 250)->nullable();
            $table->boolean('activo')->nullable()->default(true);
            $table->boolean('flock')->nullable()->default(false);
        });

        Schema::create('catTipoUsuario', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('codigo', 150)->nullable();
            $table->string('descripcion', 250)->nullable();
            $table->boolean('activo')->nullable()->default(true);
            $table->boolean('flock')->nullable()->default(false);
        });

        Schema::create('catSeleccion', function (Blueprint $table) {
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
        Schema::dropIfExists('catTipoAsignacion');
        Schema::dropIfExists('catTipoUsuario');
        Schema::dropIfExists('catSeleccion');
    }
};
