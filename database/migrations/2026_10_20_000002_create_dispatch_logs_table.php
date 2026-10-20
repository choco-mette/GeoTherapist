<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispatch_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('therapist_id')->constrained('therapists')->cascadeOnDelete();
            $table->string('customer_name', 150)->nullable();
            $table->text('customer_address');
            $table->decimal('customer_lat', 10, 7);
            $table->decimal('customer_lng', 10, 7);
            $table->decimal('distance_km', 6, 2);
            $table->unsignedInteger('est_minutes_motor');
            $table->string('status', 20)->default('drafted'); // drafted, sent, completed
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispatch_logs');
    }
};
