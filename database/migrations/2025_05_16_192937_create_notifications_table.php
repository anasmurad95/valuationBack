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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();

            // ربط الإشعار بتقييم محدد (اختياري)
            $table->foreignId('valuation_id')->nullable()->constrained()->onDelete('set null');

            // نوع الإشعار: sms, email, whatsapp
            $table->enum('channel', ['sms', 'email', 'whatsapp'])->default('email');

            // إلى من أُرسل (الإيميل أو رقم الهاتف)
            $table->string('recipient');

            // حالة الإرسال
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');

            // نص الرسالة
            $table->text('message')->nullable();

            // خطأ في الإرسال (إن وُجد)
            $table->text('error_message')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
