<?php

use App\Models\LteComponent;
use App\Models\Mimo;
use App\Models\NrComponent;
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
        Schema::create('mimos', function (Blueprint $table) {
            $table->id();

            $table->tinyInteger('mimo')->unsigned();
            $table->boolean('is_ul')->nullable(false);

            $table->unique(['mimo', 'is_ul']);
        });

        Schema::create('components_mimos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('lte_component_id')->index()->nullable()->constrained('lte_components');
            $table->foreignId('nr_component_id')->index()->nullable()->constrained('nr_components');
            $table->foreignId('mimo_id')->index()->nullable(false)->constrained('mimos');

            $table->index(['lte_component_id', 'mimo_id']);
            $table->index(['nr_component_id', 'mimo_id']);
        });

        NrComponent::all()->each(function (NrComponent $nrComponent) {
            $dlMimo = $nrComponent->dl_mimo;
            $ulMimo = $nrComponent->ul_mimo;

            $newDlMimo = $dlMimo ? Mimo::firstOrCreate([
                'mimo'  => $dlMimo,
                'is_ul' => false,
            ]) : null;

            $newUlMimo = $ulMimo ? Mimo::firstOrCreate([
                'mimo'  => $ulMimo,
                'is_ul' => true,
            ]) : null;

            if ($newDlMimo !== null) {
                $nrComponent->mimos()->attach($newDlMimo);
            }
            if ($newUlMimo !== null) {
                $nrComponent->mimos()->attach($newUlMimo);
            }
        });

        LteComponent::all()->each(function (LteComponent $lteComponent) {
            $dlMimo = $lteComponent->mimo;
            $hasUl = $lteComponent->ul_class !== null;

            $newDlMimo = $dlMimo ? Mimo::firstOrCreate([
                'mimo'  => $dlMimo,
                'is_ul' => false,
            ]) : null;

            $newUlMimo = $hasUl ? Mimo::firstOrCreate([
                'mimo'  => 1,
                'is_ul' => true,
            ]) : null;

            if ($newDlMimo !== null) {
                $lteComponent->mimos()->attach($newDlMimo);
            }
            if ($newUlMimo !== null) {
                $lteComponent->mimos()->attach($newUlMimo);
            }
        });

        Schema::table('nr_components', function (Blueprint $table) {
            $table->dropColumn('dl_mimo');
            $table->dropColumn('ul_mimo');
        });

        Schema::table('lte_components', function (Blueprint $table) {
            $table->dropColumn('mimo');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('mimos');
        Schema::dropIfExists('components_mimos');

        Schema::table('nr_components', function (Blueprint $table) {
            $table->int('dl_mimo')->nullable();
            $table->int('ul_mimo')->nullable();
        });

        Schema::table('lte_components', function (Blueprint $table) {
            $table->int('mimo')->nullable();
        });
    }
};
