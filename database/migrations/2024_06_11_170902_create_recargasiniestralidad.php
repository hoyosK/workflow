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
        Schema::create('recargaSiniestralidad', function (Blueprint $table) {
            $table->integer('id', true);
            $table->float('valormin', 10)->nullable();
            $table->float('valormax', 10)->nullable();
            $table->float('recargo', 10)->nullable();
            $table->boolean('renovar')->nullable()->default(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('recargaSiniestralidad');
    }
};
