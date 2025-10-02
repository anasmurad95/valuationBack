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
        Schema::create('report_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar');
            $table->string('name_en');
            $table->unsignedBigInteger('to_whom_type_id')->nullable();
            $table->enum('template_type', ['bank', 'government', 'private_company', 'court', 'individual', 'general']);
            $table->text('description')->nullable();
            $table->longText('template_content')->nullable();
            $table->string('template_file_path')->nullable();
            $table->json('features')->nullable();
            $table->json('sections')->nullable(); // أقسام التقرير
            $table->json('styling')->nullable(); // إعدادات التنسيق
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->foreign('to_whom_type_id')->references('id')->on('to_whom_types')->onDelete('set null');
            $table->index(['template_type', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_templates');
    }
};

