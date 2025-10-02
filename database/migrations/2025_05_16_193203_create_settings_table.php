<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();

            // مفتاح الإعداد
            $table->string('key')->unique();

            // القيمة (نص عام يمكن تخزين أي شيء فيه)
            $table->text('value')->nullable();

            // وصف لما يفعل هذا الإعداد
            $table->string('description')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
