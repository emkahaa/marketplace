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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique(); // ID unik publik untuk API
            $table->foreignId('customer_id')->constrained('users')->onDelete('restrict');
            $table->string('order_number')->unique();
            $table->decimal('total_amount', 10, 2);
            $table->foreignId('shipping_address_id')->constrained('addresses')->onDelete('restrict');
            $table->decimal('shipping_cost', 10, 2);
            $table->string('shipping_method')->nullable();
            $table->string('payment_method')->nullable();
            $table->enum('order_status', ['pending', 'processing', 'shipped', 'delivered', 'cancelled', 'returned'])->default('pending');
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
