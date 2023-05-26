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
        Schema::create("nr_bands", function (Blueprint $table) {
            $table->id();

            $table->smallInteger("band")->unsigned()->index();
            $table->boolean("rate_matching_lte_crs")->nullable();
            $table->tinyText("power_class")->nullable();
            $table->integer("max_uplink_duty_cycle")->nullable();
        });

        Schema::create("nr_bandwidths", function (Blueprint $table) {
            $table->id();

            $table->integer("scs")->unsigned()->index();

            $table->json('bandwidths_dl');
            $table->json('bandwidths_ul');
        });

        Schema::create("nr_bands_mimo", function (Blueprint $table) {
            $table->foreignId('nr_band_id')->index()->nullable(false)->constrained('nr_bands');
            $table->foreignId('mimo_id')->index()->nullable(false)->constrained('mimos');

            $table->primary(['nr_band_id', 'mimo_id']);
        });

        Schema::create("nr_bands_modulations", function (Blueprint $table) {
            $table->foreignId('nr_band_id')->index()->nullable(false)->constrained('nr_bands');
            $table->foreignId('modulation_id')->index()->nullable(false)->constrained('modulations');

            $table->primary(['nr_band_id', 'modulation_id']);
        });

        Schema::create("nr_bands_nr_bandwidths", function (Blueprint $table) {
            $table->foreignId('nr_band_id')->index()->nullable(false)->constrained('nr_bands');
            $table->foreignId('bandwidth_id')->index()->nullable(false)->constrained('nr_bandwidths');

            $table->primary(['nr_band_id', 'bandwidth_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop("nr_bands");
        Schema::drop("nr_bands_modulations");
        Schema::drop("nr_bands_bandwidths");
    }
};
