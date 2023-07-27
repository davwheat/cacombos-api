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
        Schema::table('combos', function (Blueprint $table) {
            $table->renameColumn('bandwidth_combination_set', 'bandwidth_combination_set_eutra');
            $table->string('bandwidth_combination_set_nr', 128)->nullable()->default(null);
            $table->string('bandwidth_combination_set_intra_endc', 128)->nullable()->default(null);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('combos', function (Blueprint $table) {
            $table->renameColumn('bandwidth_combination_set_eutra', 'bandwidth_combination_set');
            $table->dropColumn('bandwidth_combination_set_nr');
            $table->dropColumn('bandwidth_combination_set_intra_endc');
        });
    }
};
