<?php

namespace App\Http\Requests\Admin;

use App\Models\Admin;
use Illuminate\Foundation\Http\FormRequest;

abstract class AdminFormRequest extends FormRequest
{
    protected function admin(): ?Admin
    {
        $user = $this->user();

        return $user instanceof Admin ? $user : $this->user('admin');
    }
}
