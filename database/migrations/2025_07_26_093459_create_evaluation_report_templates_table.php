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
        Schema::create('evaluation_report_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('to_whom_type_id'); // ربط مع جدول to_whom_types
            $table->json('template_json');

            $table->foreign('to_whom_type_id')->references('id')->on('to_whom_types')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluation_report_templates');
    }
};
