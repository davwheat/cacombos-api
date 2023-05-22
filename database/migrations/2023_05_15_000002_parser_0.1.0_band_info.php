<?php

use App\Models\LteComponent;
use App\Models\Mimo;
use App\Models\Modulation;
use App\Models\NrComponent;
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
        Schema::table("nr_bands", function (Blueprint $table) {
            $table->id();

            $table->smallInteger("band")->unsigned()->index();
            $table->boolean("rate_matching_lte_crs")->nullable();
            $table->tinyText("power_class")->nullable();
            $table->integer("max_uplink_duty_cycle")->nullable();
        });

        Schema::table("nr_bands_mimo", function (Blueprint $table) {
            $table->foreignId('nr_band_id')->constrained('nr_bands')->index()->nullable(false);
            $table->foreignId('mimo_id')->constrained('mimos')->index()->nullable(false);

            $table->primary(['nr_band_id', 'mimo_id']);
        });

        Schema::table("nr_bands_modulations", function (Blueprint $table) {
            $table->foreignId('nr_band_id')->constrained('nr_bands')->index()->nullable(false);
            $table->foreignId('modulation_id')->constrained('modulations')->index()->nullable(false);

            $table->primary(['nr_band_id', 'modulation_id']);
        });

        Schema::table("nr_bands_nr_bandwidths", function (Blueprint $table) {
            $table->foreignId('nr_band_id')->constrained('nr_bands')->index()->nullable(false);
            $table->foreignId('bandwidth_id')->constrained('nr_bandwidths')->index()->nullable(false);

            $table->primary(['nr_band_id', 'bandwidth_id']);
        });

        Schema::table("nr_bandwidths", function (Blueprint $table) {
            $table->id();

            $table->integer("scs")->unsigned()->index();

            $table->json('bandwidths_dl');
            $table->json('bandwidths_ul');
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
