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
        Schema::table('packages', function (Blueprint $table) {
            $table->string('docs_import_status')->nullable();
            $table->text('docs_import_error')->nullable();
            $table->timestamp('changelog_imported_at')->nullable();
            $table->string('changelog_import_status')->nullable();
            $table->text('changelog_import_error')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn([
                'docs_import_status',
                'docs_import_error',
                'changelog_imported_at',
                'changelog_import_status',
                'changelog_import_error',
            ]);
        });
    }
};
