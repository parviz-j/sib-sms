<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sms_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sms_message_id')->constrained('sms_messages')->cascadeOnDelete();

            $table->string('destination', 20);
            $table->unsignedBigInteger('user_trace_id')->nullable();

            $table->integer('status_code')->nullable();
            $table->string('status_text')->nullable();
            $table->text('final_text')->nullable();

            $table->timestamps();

            $table->index(['destination']);
            $table->index(['user_trace_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_recipients');
    }
};
