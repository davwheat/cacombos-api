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
        Schema::create('capability_set_combo', function (Blueprint $table) {
            $table->uuid()->unique();

            $table->foreignId('capability_set_id')->index()->constrained('capability_sets')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('combo_id')->constrained('combos')->cascadeOnDelete()->cascadeOnUpdate();

            $table->primary(['capability_set_id', 'combo_id']);

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
        Schema::dropIfExists('capability_set_combo');
    }
};
