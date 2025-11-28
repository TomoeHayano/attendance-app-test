<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('break_records', function (Blueprint $table): void {
            $table->bigIncrements('id');

            $table->foreignId('attendance_id')
                ->constrained('attendances')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->time('break_start');
            $table->time('break_end')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('break_records');
    }
};
