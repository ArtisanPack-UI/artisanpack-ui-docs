<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Widens the documentation and changelog content columns from TEXT to LONGTEXT.
 *
 * MySQL's TEXT holds at most 65,535 bytes. A larger page (the design system's
 * Livewire upgrade guide is about 72 KB) failed to save, which aborted the import
 * before stale pages were removed and parent relationships were set.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('documentation', function (Blueprint $table) {
            $table->longText('content')->change();
        });

        Schema::table('changelogs', function (Blueprint $table) {
            $table->longText('content')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documentation', function (Blueprint $table) {
            $table->text('content')->change();
        });

        Schema::table('changelogs', function (Blueprint $table) {
            $table->text('content')->change();
        });
    }
};
