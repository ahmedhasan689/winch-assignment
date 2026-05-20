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
            $table->string('customer_name');
            $table->string('customer_phone', 20);
            $table->decimal('pickup_lat', 10, 7);
            $table->decimal('pickup_lng', 10, 7);
            $table->decimal('dropoff_lat', 10, 7);
            $table->decimal('dropoff_lng', 10, 7);
            $table->string('status', 20)->default('pending'); // Statuses => [ pending, assigned, in_progress, completed, cancelled ]
            $table->foreignId('driver_id')
                ->nullable()
                ->constrained('drivers')
                ->restrictOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('status'); // Indexes
            $table->index(['driver_id', 'status']);
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
