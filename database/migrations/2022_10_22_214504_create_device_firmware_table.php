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
        Schema::create('device_firmwares', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();

            $table->foreignId('device_id')->index()->constrained('devices')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('name')->index();
            $table->string('plmn', 16)->nullable()->index();

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
        Schema::dropIfExists('device_firmwares');
    }
};
