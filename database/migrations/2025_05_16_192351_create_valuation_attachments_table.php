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
        Schema::create('valuation_attachments', function (Blueprint $table) {
            $table->id();

            // ربط بالمُقيم
            $table->foreignId('valuation_id')->constrained()->onDelete('cascade');

            // نوع الملف: صورة، PDF، Word، أو غير ذلك
            $table->enum('type', ['image', 'pdf', 'word', 'other'])->nullable();

            // مسار الملف
            $table->string('file_path');

            // الاسم الأصلي للملف (اختياري)
            $table->string('file_name')->nullable();

            // من رفع الملف (اختياري)
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('valuation_attachments');
    }
};
