<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->boolean('is_superadmin')->default(false));
        Schema::table('clubs', fn (Blueprint $table) => $table->string('access_override')->nullable());
        Schema::create('admin_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('club_id')->constrained()->restrictOnDelete();
            $table->string('action');
            $table->string('payment_id')->nullable();
            $table->text('reason');
            $table->json('changes');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_audits');
        Schema::table('clubs', fn (Blueprint $table) => $table->dropColumn('access_override'));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('is_superadmin'));
    }
};
