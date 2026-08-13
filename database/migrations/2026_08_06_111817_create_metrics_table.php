<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metrics', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id')->nullable();
            $table->unsignedTinyInteger('timescale')->default(0);
            $table->unsignedTinyInteger('location_type')->default(0);
            $table->unsignedInteger('location_id')->default(0);
            $table->unsignedSmallInteger('year')->default(0);
            $table->unsignedTinyInteger('month')->default(0);
            $table->integer('uploads')->default(0);
            $table->integer('tags')->default(0);
            $table->integer('xp')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metrics');
    }
};
