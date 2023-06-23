<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('lte_components', function (Blueprint $table) {
            $table->index(['band', 'dl_class', 'ul_class', 'component_index']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('lte_components', function (Blueprint $table) {
            $table->dropIndex(['band', 'dl_class', 'ul_class', 'component_index']);
        });
    }
};
