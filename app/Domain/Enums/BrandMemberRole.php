<?php

namespace App\Domain\Enums;

enum BrandMemberRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Editor = 'editor';
    case Viewer = 'viewer';

    public function canManageBrand(): bool
    {
        return in_array($this, [self::Owner, self::Admin], true);
    }

    public function canPublish(): bool
    {
        return in_array($this, [self::Owner, self::Admin, self::Editor], true);
    }
}
