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
        Schema::create('valuation_transfers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('valuation_id');
            $table->unsignedBigInteger('from_user_id');
            $table->unsignedBigInteger('to_user_id');
            $table->text('transfer_reason');
            $table->text('transfer_notes')->nullable();
            $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending');
            $table->timestamp('transferred_at')->useCurrent();
            $table->timestamp('responded_at')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            
            $table->foreign('valuation_id')->references('id')->on('valuations')->onDelete('cascade');
            $table->foreign('from_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('to_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            
            $table->index(['valuation_id', 'status']);
            $table->index(['to_user_id', 'status']);
            $table->index('transferred_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('valuation_transfers');
    }
};

