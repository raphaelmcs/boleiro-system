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
        Schema::table('products', function (Blueprint $table) {
            $table->string('gender')->nullable()->after('season');
            // Change version column to string to easily support 'infantil' and future versions without ENUM headaches
            $table->string('version')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('gender');
            // Reverting back to enum might cause data loss if there are 'infantil' records, but typically we'd do:
            // $table->enum('version', ['jogador', 'torcedor', 'retro'])->change();
        });
    }
};
