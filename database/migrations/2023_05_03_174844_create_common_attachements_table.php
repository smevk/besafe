<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('common_attachements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('incident_id'); //e.g hazard_id, unsafe_behavior_id,fire_and_property_damage_id
            $table->string('form_name'); //such as hazard,unsafe_behavior,fire_and_property_damage
            $table->string('form_input_name'); //reports, attachements, solution attachements, other attachements
            $table->string('file_name'); //name of each file
            $table->foreignId('user_id')->constrained(); //uploaded by user
            $table->boolean('status')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('common_attachements');
    }
};