<?php

namespace App\Domain\Enums;

enum ContentStatus: string
{
    case Draft = 'draft';
    case Approved = 'approved';
    case Scheduled = 'scheduled';
    case Published = 'published';
    case Failed = 'failed';
}
