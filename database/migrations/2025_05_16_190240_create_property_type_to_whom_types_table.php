<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('property_type_to_whom_types', function (Blueprint $table) {
            $table->id();

            $table->foreignId('to_whom_type_id')
                ->constrained()
                ->onDelete('cascade');

            $table->foreignId('property_type_id')
                ->constrained()
                ->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('property_type_to_whom_types');
    }
};
