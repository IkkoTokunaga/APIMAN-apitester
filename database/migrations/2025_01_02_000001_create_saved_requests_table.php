<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_id')
                ->nullable()
                ->constrained('collections')
                ->nullOnDelete();
            $table->string('title');
            $table->string('method', 10);
            $table->text('url');
            $table->json('request_headers')->nullable();
            $table->text('request_body')->nullable();
            $table->string('content_type')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_requests');
    }
};
