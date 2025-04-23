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
        Schema::table('catDepartamento', function (Blueprint $table) {
            $table->string('pais')->nullable();
        });

        Schema::table('catMunicipio', function (Blueprint $table) {
            $table->string('departamento')->nullable();
        });

        Schema::table('catZonas', function (Blueprint $table) {
            $table->string('municipio')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('catDepartamento', function (Blueprint $table) {
            $table->dropColumn('pais');
        });

        Schema::table('catMunicipio', function (Blueprint $table) {
            $table->dropColumn('departamento');
        });

        Schema::table('catZonas', function (Blueprint $table) {
            $table->dropColumn('municipio');
        });

    }
};
