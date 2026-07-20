<?php

namespace App\Policies;

use App\Models\ContentItem;
use App\Models\User;

class ContentPolicy
{
    public function update(User $user, ContentItem $item): bool
    {
        return $item->brand->user_id === $user->id;
    }

    public function delete(User $user, ContentItem $item): bool
    {
        return $item->brand->user_id === $user->id;
    }
}
