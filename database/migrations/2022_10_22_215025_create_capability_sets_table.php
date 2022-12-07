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

            $table->foreignId('device_firmware_id')->index()->nullable()->index()->constrained('device_firmwares')->nullOnDelete()->cascadeOnUpdate();

            $table->string('description', 255)->index();
            $table->string('plmn', 16)->nullable()->index();

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
