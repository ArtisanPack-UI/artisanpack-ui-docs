<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Audit-log table (V2_REFACTOR_PLAN.md §4.3, §9.6 item #46).
 *
 * Records every write to Packages / Documentation / Changelogs from
 * both the web admin and the v1 API so admins have a single feed at
 * `/dashboard/audit-log` showing who changed what, when, and how.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_log', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_name');
            $table->string('source', 16);
            $table->string('resource_type', 64);
            $table->unsignedBigInteger('resource_id')->nullable();
            $table->string('action', 32);
            $table->json('changes')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['resource_type', 'resource_id']);
            $table->index('user_id');
            $table->index('source');
            $table->index('action');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_log');
    }
};
