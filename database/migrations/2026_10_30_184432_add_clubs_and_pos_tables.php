<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clubs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        foreach (['users', 'courts', 'bookings'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->foreignId('club_id')->nullable()->constrained()->restrictOnDelete();
            });
        }
        $clubId = DB::table('clubs')->insertGetId([
            'name' => 'Club principal', 'created_at' => now(), 'updated_at' => now(),
        ]);
        foreach (['users', 'courts', 'bookings'] as $name) {
            DB::table($name)->update(['club_id' => $clubId]);
            Schema::table($name, function (Blueprint $table) {
                $table->unsignedBigInteger('club_id')->nullable(false)->change();
            });
        }

        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_id')->constrained()->restrictOnDelete();
            $table->string('name', 100);
            $table->timestamps();
            $table->unique(['club_id', 'name']);
        });
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_id')->constrained()->restrictOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('product_categories')->restrictOnDelete();
            $table->string('name');
            $table->string('barcode', 100)->nullable();
            $table->decimal('cost_price', 12, 2);
            $table->decimal('sale_price', 12, 2);
            $table->unsignedInteger('stock')->default(0);
            $table->unsignedInteger('alert_min_stock')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['club_id', 'barcode']);
        });
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_id')->constrained()->restrictOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->uuid('request_key');
            $table->decimal('total_amount', 18, 2);
            $table->decimal('cost_amount', 18, 2);
            $table->string('status')->default('paid');
            $table->string('payment_method')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->unique(['club_id', 'request_key']);
            $table->index(['club_id', 'status']);
        });
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_id')->constrained()->restrictOnDelete();
            $table->foreignId('sale_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('product_name');
            $table->unsignedInteger('quantity');
            $table->decimal('unit_cost_price', 12, 2);
            $table->decimal('unit_sale_price', 12, 2);
            $table->decimal('subtotal', 18, 2);
            $table->timestamps();
        });
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained()->restrictOnDelete();
            $table->integer('quantity');
            $table->unsignedInteger('stock_after');
            $table->string('reason');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['stock_movements', 'sale_items', 'sales', 'products', 'product_categories'] as $name) {
            Schema::dropIfExists($name);
        }
        foreach (['bookings', 'courts', 'users'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->dropConstrainedForeignId('club_id'));
        }
        Schema::dropIfExists('clubs');
    }
};
