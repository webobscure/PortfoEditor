<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreUploadRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            // The mime list is enforced again by MediaService against the
            // sniffed type; this rule is the cheap first pass.
            'file' => [
                'required',
                'file',
                'image',
                'mimetypes:'.implode(',', (array) config('portfolio.uploads.mimes')),
                'max:'.(int) config('portfolio.uploads.max_kilobytes'),
            ],
            'portfolio_id' => ['sometimes', 'nullable', 'integer', 'exists:portfolios,id'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'file.mimetypes' => 'Upload a JPEG, PNG or WebP image.',
            'file.max' => 'Images must be smaller than :max kB.',
        ];
    }
}
