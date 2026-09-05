<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monthly_statistics', function (Blueprint $table) {
            $table->id();
            $table->string('period', 7)->unique();
            $table->unsignedInteger('physical_clients')->default(0);
            $table->unsignedInteger('moral_clients')->default(0);
            $table->unsignedInteger('monthly_engagements')->default(0);
            $table->decimal('monthly_engagement_amount', 20, 2)->default(0);
            $table->decimal('total_outstanding_amount', 20, 2)->default(0);
            $table->unsignedInteger('unpaid_clients')->default(0);
            $table->timestamp('extracted_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_statistics');
    }
};
