<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sms_messages', function (Blueprint $table) {
            $table->id();
            $table->string('type', 50);
            $table->string('api_key_last4', 10)->nullable();

            $table->unsignedBigInteger('sender')->nullable();
            $table->text('text')->nullable();

            $table->unsignedBigInteger('provider_message_id')->nullable();
            $table->unsignedBigInteger('user_trace_id')->nullable();

            $table->json('payload')->nullable();
            $table->json('raw_response')->nullable();
            $table->text('error')->nullable();

            $table->timestamps();

            $table->index(['type']);
            $table->index(['provider_message_id']);
            $table->index(['user_trace_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_messages');
    }
};
