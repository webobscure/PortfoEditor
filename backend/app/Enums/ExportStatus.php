<?php

declare(strict_types=1);

namespace App\Enums;

enum ExportStatus: string
{
    case Queued = 'queued';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';
}
