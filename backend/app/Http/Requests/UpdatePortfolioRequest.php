<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Services\Templates\TemplateRegistry;
use App\Services\Templates\ThemeSchema;
use Illuminate\Foundation\Http\FormRequest;

final class UpdatePortfolioRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return array_merge([
            'name' => ['sometimes', 'string', 'max:120'],
            'template_key' => ['sometimes', 'string', 'in:'.implode(',', app(TemplateRegistry::class)->keys())],
            'status' => ['sometimes', 'string', 'in:draft,published'],

            'meta' => ['sometimes', 'array'],
            'meta.title' => ['sometimes', 'nullable', 'string', 'max:70'],
            'meta.description' => ['sometimes', 'nullable', 'string', 'max:180'],
            'meta.og_image_media_id' => ['sometimes', 'nullable', 'integer'],
        ], app(ThemeSchema::class)->rules('settings'));
    }
}
