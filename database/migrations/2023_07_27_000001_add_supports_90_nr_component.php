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
        Schema::table('nr_components', function (Blueprint $table) {
            $table->boolean('supports_90mhz_bw')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('nr_components', function (Blueprint $table) {
            $table->dropColumn('supports_90mhz_bw');
        });
    }
};
