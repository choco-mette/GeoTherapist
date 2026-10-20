<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('therapists', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('phone', 25);
            $table->string('specialization', 100)->default('General Massage');
            $table->decimal('rating', 2, 1)->default(5.0);
            $table->unsignedInteger('review_count')->default(0);
            $table->string('address_label', 255);
            $table->string('subdistrict', 100)->nullable();
            $table->string('city', 100)->default('Surabaya');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->string('status', 20)->default('available'); // available, on_duty, off
            $table->string('avatar_url', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('therapists');
    }
};
