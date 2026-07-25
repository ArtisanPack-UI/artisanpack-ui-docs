<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Row in the `audit_log` table (V2_REFACTOR_PLAN.md §4.3, §9.6 item #46).
 *
 * Written exclusively via {@see AuditLogger}. `updated_at`
 * is intentionally absent — audit rows are immutable.
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $actor_name
 * @property string $source
 * @property string $resource_type
 * @property int|null $resource_id
 * @property string $action
 * @property array<string, array{0: mixed, 1: mixed}>|null $changes
 * @property string|null $ip_address
 * @property Carbon $created_at
 */
class AuditLog extends Model
{
    protected $table = 'audit_log';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'actor_name',
        'source',
        'resource_type',
        'resource_id',
        'action',
        'changes',
        'ip_address',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'changes' => 'array',
            'created_at' => 'datetime',
            'resource_id' => 'integer',
            'user_id' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
