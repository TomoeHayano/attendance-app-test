<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('correction_requests', function (Blueprint $table): void {
            $table->bigIncrements('id');

            $table->foreignId('attendance_id')
                ->constrained('attendances')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->time('corrected_clock_in')->nullable();
            $table->time('corrected_clock_out')->nullable();

            $table->string('remarks', 255);
            $table->tinyInteger('status');

            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('admins')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('correction_requests');
    }
};