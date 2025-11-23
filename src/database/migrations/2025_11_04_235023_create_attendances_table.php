<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table): void {
            $table->bigIncrements('id');

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->date('date');
            $table->time('clock_in')->nullable();
            $table->time('clock_out')->nullable();
            $table->tinyInteger('status')->default(0);
            $table->timestamps();
            $table->unique(['user_id', 'date'], 'attendances_user_date_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};