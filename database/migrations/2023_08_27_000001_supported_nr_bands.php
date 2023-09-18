<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('supported_nr_bands', function (Blueprint $table) {
            $table->id();

            $table->integer('band')->index()->unsigned();
            $table->integer('max_uplink_duty_cycle')->nullable();
            $table->string('power_class', 32)->nullable()->index();
            $table->boolean('rate_matching_lte_crs')->nullable();
            $table->json('bandwidths')->nullable();

            $table->boolean('supports_endc')->nullable()->index();
            $table->boolean('supports_sa')->nullable()->index();

            $table->foreignId('capability_set_id')->index()->constrained('capability_sets')->cascadeOnDelete()->cascadeOnUpdate();
        });

        Schema::create('supported_nr_bands_mimos', function (Blueprint $table) {
            $table->foreignId('supported_nr_band_id')->index()->nullable(false)->constrained('supported_nr_bands');
            $table->foreignId('mimo_id')->index()->nullable(false)->constrained('mimos');

            $table->primary(['supported_nr_band_id', 'mimo_id']);
        });

        Schema::create('supported_nr_bands_modulations', function (Blueprint $table) {
            $table->foreignId('supported_nr_band_id')->index()->nullable(false)->constrained('supported_nr_bands');
            $table->foreignId('modulation_id')->index()->nullable(false)->constrained('modulations');

            $table->primary(['supported_nr_band_id', 'modulation_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('supported_nr_bands');
        Schema::dropIfExists('supported_nr_bands_mimos');
        Schema::dropIfExists('supported_nr_bands_modulations');
    }
};
