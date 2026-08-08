<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('todos', function (Blueprint $table) {
            $table->string('source')->nullable()->after('id');
            $table->unsignedInteger('external_id')->nullable()->after('source');
            $table->unique(['source', 'external_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('todos', function (Blueprint $table) {
            $table->dropUnique(['source', 'external_id']);
            $table->dropColumn(['source', 'external_id']);
        });
    }
};
