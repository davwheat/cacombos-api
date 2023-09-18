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
        Schema::dropColumns('lte_components', ['created_at', 'updated_at']);
        Schema::dropColumns('nr_components', ['created_at', 'updated_at']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('lte_components', function (Blueprint $table) {
            $table->timestamps();
        });

        Schema::table('nr_components', function (Blueprint $table) {
            $table->timestamps();
        });
    }
};
