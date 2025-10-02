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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            // التقييم المرتبط بالعملية
            $table->foreignId('valuation_id')->constrained()->onDelete('cascade');

            // من قام بالإجراء (موظف)
            $table->foreignId('performed_by')->constrained('users')->onDelete('cascade');

            // نوع الإجراء: تعديل، حذف، إرسال، قبول، رفض، تحويل...
            $table->string('action');

            // السبب أو الملاحظات (اختياري)
            $table->text('reason')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
