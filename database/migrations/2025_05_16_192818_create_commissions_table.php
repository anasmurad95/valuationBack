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
        Schema::create('commissions', function (Blueprint $table) {
            $table->id();

            // الموظف الذي يحصل على العمولة
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // التقييم المرتبط بالعمولة (اختياري إذا كانت العمولة مبنية على تقييم واحد)
            $table->foreignId('valuation_id')->nullable()->constrained()->onDelete('set null');

            // قيمة العمولة
            $table->decimal('amount', 10, 2);

            // نوع الفترة
            $table->enum('period', ['monthly', 'quarterly', 'yearly']);

            // هل تم إعلام الموظف؟
            $table->boolean('notified')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commissions');
    }
};
