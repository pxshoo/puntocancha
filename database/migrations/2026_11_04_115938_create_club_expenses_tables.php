<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_id')->constrained()->restrictOnDelete();
            $table->string('name', 100);
            $table->timestamps();
            $table->unique(['club_id', 'name']);
        });
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_id')->constrained()->restrictOnDelete();
            $table->foreignId('expense_category_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('concept');
            $table->decimal('amount', 18, 2);
            $table->date('expense_date');
            $table->string('receipt_number')->nullable();
            $table->timestamps();
            $table->index(['club_id', 'expense_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('expense_categories');
    }
};
