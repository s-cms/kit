<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->string('file_name');
            $table->string('name');
            $table->string('disk');
            $table->string('path');
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->json('alt')->nullable();
            $table->json('conversions')->nullable();
            $table->json('responsive_images')->nullable();
            $table->json('custom_properties')->nullable();
            $table->timestamps();

            $table->index(['disk', 'path']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
