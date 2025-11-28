<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('correction_breaks', function (Blueprint $table): void {
            $table->bigIncrements('id');

            $table->foreignId('correction_request_id')
                ->constrained('correction_requests')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->time('corrected_break_start');
            $table->time('corrected_break_end')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('correction_breaks');
    }
};
