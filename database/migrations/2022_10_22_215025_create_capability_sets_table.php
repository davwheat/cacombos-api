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
        Schema::create('capability_sets', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();


            $table->timestamps();
        });

        Schema::create('device_capability_set', function (Blueprint $table) {
            $table->foreignId('device_id')->constrained('devices')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('device_firmware_id')->nullable()->index()->constrained('device_firmwares')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('capability_set_id')->constrained('capability_sets')->cascadeOnDelete()->cascadeOnUpdate();

            $table->primary(['device_id', 'capability_set_id']);

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
        Schema::dropIfExists('capability_sets');
    }
};
