<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Lifecycle of the most recent documentation / changelog import for a
 * package: stamped `Queued` on dispatch, then `Succeeded` or `Failed`
 * by the import job.
 */
enum ImportStatus: string
{
    case Queued = 'queued';
    case Succeeded = 'succeeded';
    case Failed = 'failed';
}
