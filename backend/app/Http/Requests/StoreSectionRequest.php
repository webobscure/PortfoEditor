<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\SectionType;
use Illuminate\Foundation\Http\FormRequest;

final class StoreSectionRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:'.implode(',', SectionType::values())],
            'position' => ['sometimes', 'integer', 'min:0', 'max:100'],
        ];
    }
}
