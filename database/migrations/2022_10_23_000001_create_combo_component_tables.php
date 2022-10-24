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
        Schema::create('nr_components', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();

            $table->integer('band')->index();
            $table->string('dl_class', 8)->nullable()->index();
            $table->string('ul_class', 8)->nullable()->index();
            $table->integer('bandwidth')->nullable();
            $table->integer('subcarrier_spacing')->nullable();
            $table->integer('dl_mimo')->nullable();
            $table->integer('ul_mimo')->nullable();
            $table->string('dl_modulation', 16)->nullable();
            $table->string('ul_modulation', 16)->nullable();

            $table->timestamps();
        });

        Schema::create('lte_components', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();

            $table->integer('band')->index();
            $table->string('dl_class', 8)->nullable()->index();
            $table->string('ul_class', 8)->nullable()->index();
            $table->integer('mimo')->nullable();
            $table->string('dl_modulation', 16)->nullable();
            $table->string('ul_modulation', 16)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('nr_components');
        Schema::dropIfExists('lte_components');
    }
};
