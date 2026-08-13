<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('photos', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id')->nullable();
            $table->timestamps();
            $table->softDeletes(); // adds deleted_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('photos');
    }
};
