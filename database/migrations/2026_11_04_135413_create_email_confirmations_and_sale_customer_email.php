<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', fn (Blueprint $table) => $table->string('customer_email')->nullable());
        Schema::create('email_confirmations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_id')->constrained()->restrictOnDelete();
            $table->string('event_key')->unique();
            $table->text('recipient');
            $table->text('payload');
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('next_attempt_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->index(['sent_at', 'next_attempt_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_confirmations');
        Schema::table('sales', fn (Blueprint $table) => $table->dropColumn('customer_email'));
    }
};
