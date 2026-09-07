<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Services\Portfolio\PortfolioPresets;
use App\Services\Templates\TemplateRegistry;
use Illuminate\Foundation\Http\FormRequest;

final class StorePortfolioRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'preset' => ['sometimes', 'string', 'in:'.implode(',', PortfolioPresets::KEYS)],
            'template_key' => ['sometimes', 'string', 'in:'.implode(',', app(TemplateRegistry::class)->keys())],

            // The three answers from the create flow. They seed the hero so the
            // portfolio reads as the user's own the moment the editor opens.
            'profile' => ['sometimes', 'array'],
            'profile.name' => ['sometimes', 'nullable', 'string', 'max:120'],
            'profile.title' => ['sometimes', 'nullable', 'string', 'max:160'],
            'profile.intro' => ['sometimes', 'nullable', 'string', 'max:600'],
        ];
    }
}
