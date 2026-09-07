<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\PortfolioSection;
use App\Services\Portfolio\SectionSchemaRegistry;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The rules for `data` depend on the section's type, so they are pulled from
 * the schema registry at validation time. Combined with
 * Validator::excludeUnvalidatedArrayKeys(), this is what keeps the JSON column
 * to a known shape: anything the schema does not describe never reaches it.
 */
final class UpdateSectionRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        /** @var PortfolioSection $section */
        $section = $this->route('section');

        return array_merge([
            'enabled' => ['sometimes', 'boolean'],
            'content' => ['sometimes', 'array'],
        ], app(SectionSchemaRegistry::class)->rules($section->type, 'content'));
    }
}
