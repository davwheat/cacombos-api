<?php

use App\Models\Combo;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class() extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('combos')->update([
            'combo_string' => DB::raw('REGEXP_REPLACE(combo_string, "-(\\\\d+|mAll|m\\\\d+)$", "")')
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
};
