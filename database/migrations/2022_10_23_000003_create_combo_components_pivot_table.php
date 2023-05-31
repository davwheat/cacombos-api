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
        Schema::create('combo_components', function (Blueprint $table) {
            $table->id();

            $table->foreignId('combo_id')->index()->constrained('combos')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('nr_component_id')->nullable()->constrained('nr_components')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('lte_component_id')->nullable()->constrained('lte_components')->cascadeOnDelete()->cascadeOnUpdate();

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
        Schema::dropIfExists('combo_components');
    }
};
