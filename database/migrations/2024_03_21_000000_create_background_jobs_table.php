<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('background_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('job_class');
            $table->string('method');
            $table->json('parameters')->nullable();
            $table->integer('priority')->default(3);
            $table->integer('delay')->default(0);
            $table->string('status')->default('pending');
            $table->integer('attempts')->default(0);
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('error')->nullable();
            $table->string('process_id')->nullable();
            $table->timestamps();
            
            $table->index(['status', 'priority', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('background_jobs');
    }
}; 