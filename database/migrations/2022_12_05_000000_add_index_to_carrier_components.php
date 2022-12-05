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
        Schema::table('lte_components', function (Blueprint $table) {
            $table->tinyInteger('component_index')->unsigned()->nullable(false);
        });

        Schema::table('nr_components', function (Blueprint $table) {
            $table->tinyInteger('component_index')->unsigned()->nullable(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropColumns('lte_components', ['component_index']);
        Schema::dropColumns('nr_components', ['component_index']);
    }
};
