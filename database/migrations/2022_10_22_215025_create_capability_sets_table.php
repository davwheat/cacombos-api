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

        Schema::create('device_capability_sets', function (Blueprint $table) {
            $table->foreignId('device_id')->index()->constrained('devices')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('device_firmware_id')->index()->nullable()->index()->constrained('device_firmwares')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('capability_set_id')->index()->constrained('capability_sets')->cascadeOnDelete()->cascadeOnUpdate();

            $table->primary(['device_id', 'capability_set_id']);

            $table->timestamps();
            $table->index('updated_at');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('device_capability_sets');
        Schema::dropIfExists('capability_sets');
    }
};
