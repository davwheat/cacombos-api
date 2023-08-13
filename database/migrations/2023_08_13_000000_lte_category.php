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
        Schema::table('capability_sets', function (Blueprint $table) {
            $table->smallInteger('lte_category_dl')->unsigned()->nullable();
            $table->smallInteger('lte_category_ul')->unsigned()->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('capability_sets', function (Blueprint $table) {
            $table->dropColumn('lte_category_dl');
            $table->dropColumn('lte_category_ul');
        });
    }
};
