<?php

namespace App\Http\Requests\Api\Schedule;

use Illuminate\Foundation\Http\FormRequest;

class StoreScheduledPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content_item_id' => ['required', 'integer', 'exists:content_items,id'],
            'social_account_id' => ['required', 'integer', 'exists:social_accounts,id'],
            'scheduled_at' => ['required', 'date', 'after:now'],
        ];
    }
}
