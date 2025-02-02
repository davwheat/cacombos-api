<?php

use App\Models\LteComponent;
use App\Models\Modulation;
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
        Schema::create('modulations', function (Blueprint $table) {
            $table->id();

            $table->string('modulation', 16);
            $table->boolean('is_ul')->nullable(false);

            $table->unique(['modulation', 'is_ul']);
        });

        Schema::create('components_modulations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('lte_component_id')->index()->nullable()->constrained('lte_components');
            $table->foreignId('nr_component_id')->index()->nullable()->constrained('nr_components');
            $table->foreignId('modulation_id')->index()->nullable(false)->constrained('modulations');

            $table->index(['lte_component_id', 'modulation_id']);
            $table->index(['nr_component_id', 'modulation_id']);
        });

        NrComponent::all()->each(function (NrComponent $nrComponent) {
            $dlMod = $nrComponent->dl_modulation;
            $ulMod = $nrComponent->ul_modulation;

            // Convert qam256 to 256qam, etc.
            if (str_starts_with($dlMod, 'qam')) {
                $dlMod = str_replace('qam', '', $dlMod);
                $dlMod .= 'qam';
            }

            if (str_starts_with($ulMod, 'qam')) {
                $ulMod = str_replace('qam', '', $ulMod);
                $ulMod .= 'qam';
            }

            $newDlMod = $dlMod ? Modulation::firstOrCreate([
                'modulation' => $dlMod,
                'is_ul'      => false,
            ]) : null;

            $newUlMod = $ulMod ? Modulation::firstOrCreate([
                'modulation' => $ulMod,
                'is_ul'      => true,
            ]) : null;

            if ($newDlMod !== null) {
                $nrComponent->modulations()->attach($newDlMod);
            }
            if ($newUlMod !== null) {
                $nrComponent->modulations()->attach($newUlMod);
            }
        });

        LteComponent::all()->each(function ($lteComponent) {
            $dlMod = $lteComponent->dl_modulation;
            $ulMod = $lteComponent->ul_modulation;

            $newDlMod = $dlMod ? Modulation::firstOrCreate([
                'modulation' => $dlMod,
                'is_ul'      => false,
            ]) : null;

            $newUlMod = $ulMod ? Modulation::firstOrCreate([
                'modulation' => $ulMod,
                'is_ul'      => true,
            ]) : null;

            if ($newDlMod !== null) {
                $lteComponent->modulations()->attach($newDlMod);
            }
            if ($newUlMod !== null) {
                $lteComponent->modulations()->attach($newUlMod);
            }
        });

        Schema::table('nr_components', function (Blueprint $table) {
            $table->dropColumn('dl_modulation');
            $table->dropColumn('ul_modulation');
        });

        Schema::table('lte_components', function (Blueprint $table) {
            $table->dropColumn('dl_modulation');
            $table->dropColumn('ul_modulation');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('modulations');
        Schema::dropIfExists('components_modulations');

        Schema::table('nr_components', function (Blueprint $table) {
            $table->string('dl_modulation', 16)->nullable();
            $table->string('ul_modulation', 16)->nullable();
        });

        Schema::table('lte_components', function (Blueprint $table) {
            $table->string('dl_modulation', 16)->nullable();
            $table->string('ul_modulation', 16)->nullable();
        });
    }
};
