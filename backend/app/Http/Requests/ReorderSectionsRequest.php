<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ReorderSectionsRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'section_ids' => ['required', 'array', 'min:1', 'max:100'],
            'section_ids.*' => ['integer'],
        ];
    }
}
