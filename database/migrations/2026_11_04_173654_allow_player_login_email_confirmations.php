<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_confirmations', fn (Blueprint $table) => $table->unsignedBigInteger('club_id')->nullable()->change());
    }

    public function down(): void
    {
        Schema::table('email_confirmations', fn (Blueprint $table) => $table->unsignedBigInteger('club_id')->nullable(false)->change());
    }
};
