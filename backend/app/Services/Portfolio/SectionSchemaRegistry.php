<?php

declare(strict_types=1);

namespace App\Services\Portfolio;

use App\Enums\SectionType;
use InvalidArgumentException;

/**
 * The single source of truth for what may live inside a section's `data` column.
 *
 * `data` is JSONB, which is only safe because nothing reaches it unvalidated.
 * Every write goes through these rules, and because the application enables
 * Validator::excludeUnvalidatedArrayKeys(), any key that is not listed here is
 * stripped before the payload is persisted. That keeps the column from turning
 * into a dumping ground and keeps the renderers' assumptions honest.
 *
 * URLs are restricted to http/https so that `javascript:` can never reach an
 * href in the preview or in an exported archive.
 */
final class SectionSchemaRegistry
{
    /** @var array<string, array<int, string>> */
    private const SOCIAL_PLATFORMS = [
        'platforms' => [
            'github', 'linkedin', 'x', 'dribbble', 'behance', 'instagram',
            'youtube', 'medium', 'figma', 'threads', 'mastodon', 'website',
        ],
    ];

    /**
     * Validation rules for one section type, rooted at $prefix (usually "data").
     *
     * @return array<string, mixed>
     */
    public function rules(SectionType $type, string $prefix = 'data'): array
    {
        $rules = match ($type) {
            SectionType::Hero => $this->heroRules(),
            SectionType::About => $this->aboutRules(),
            SectionType::Experience => $this->experienceRules(),
            SectionType::Education => $this->educationRules(),
            SectionType::Skills => $this->skillsRules(),
            SectionType::Projects => $this->projectsRules(),
            SectionType::Services => $this->servicesRules(),
            SectionType::Achievements => $this->achievementsRules(),
            SectionType::Contacts => $this->contactsRules(),
            SectionType::SocialLinks => $this->socialLinksRules(),
        };

        $prefixed = [];
        foreach ($rules as $key => $rule) {
            $prefixed[$prefix.'.'.$key] = $rule;
        }

        return $prefixed;
    }

    /**
     * Empty-but-valid data for a freshly created section.
     *
     * @return array<string, mixed>
     */
    public function defaults(SectionType $type): array
    {
        return match ($type) {
            SectionType::Hero => [
                'name' => '', 'title' => '', 'intro' => '', 'photo_media_id' => null,
                'cta_text' => '', 'cta_url' => '', 'secondary_cta_text' => '',
                'secondary_cta_url' => '', 'alignment' => 'left', 'show_social' => true,
            ],
            SectionType::About => [
                'heading' => 'About', 'body' => '', 'photo_media_id' => null, 'highlights' => [],
            ],
            SectionType::Experience => ['heading' => 'Experience', 'items' => []],
            SectionType::Education => ['heading' => 'Education', 'items' => []],
            SectionType::Skills => ['heading' => 'Skills', 'groups' => []],
            SectionType::Projects => ['heading' => 'Selected work', 'intro' => '', 'items' => []],
            SectionType::Services => ['heading' => 'Services', 'items' => []],
            SectionType::Achievements => ['heading' => 'Achievements', 'items' => []],
            SectionType::Contacts => [
                'heading' => 'Get in touch', 'intro' => '', 'email' => '', 'phone' => '',
                'location' => '', 'availability' => '', 'cta_text' => 'Send an email',
            ],
            SectionType::SocialLinks => ['items' => []],
        };
    }

    /**
     * Merge a partial payload over the defaults so a half-filled section never
     * hands the renderer a missing key.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function normalise(SectionType $type, array $data): array
    {
        return array_replace($this->defaults($type), $data);
    }

    /** @return array<int, string> */
    public function socialPlatforms(): array
    {
        return self::SOCIAL_PLATFORMS['platforms'];
    }

    public function assertSupported(string $type): SectionType
    {
        return SectionType::tryFrom($type)
            ?? throw new InvalidArgumentException("Unsupported section type [{$type}].");
    }

    // ---------------------------------------------------------------- schemas

    /** @return array<string, mixed> */
    private function heroRules(): array
    {
        return [
            'name' => ['sometimes', 'nullable', 'string', 'max:120'],
            'title' => ['sometimes', 'nullable', 'string', 'max:160'],
            'intro' => ['sometimes', 'nullable', 'string', 'max:600'],
            'photo_media_id' => ['sometimes', 'nullable', 'integer'],
            'cta_text' => ['sometimes', 'nullable', 'string', 'max:40'],
            'cta_url' => ['sometimes', 'nullable', 'string', 'max:500', 'url:http,https'],
            'secondary_cta_text' => ['sometimes', 'nullable', 'string', 'max:40'],
            'secondary_cta_url' => ['sometimes', 'nullable', 'string', 'max:500', 'url:http,https'],
            'alignment' => ['sometimes', 'nullable', 'string', 'in:left,center'],
            'show_social' => ['sometimes', 'boolean'],
        ];
    }

    /** @return array<string, mixed> */
    private function aboutRules(): array
    {
        return [
            'heading' => ['sometimes', 'nullable', 'string', 'max:120'],
            'body' => ['sometimes', 'nullable', 'string', 'max:3000'],
            'photo_media_id' => ['sometimes', 'nullable', 'integer'],
            'highlights' => ['sometimes', 'array', 'max:6'],
            'highlights.*.label' => ['required', 'string', 'max:60'],
            'highlights.*.value' => ['required', 'string', 'max:60'],
        ];
    }

    /** @return array<string, mixed> */
    private function experienceRules(): array
    {
        return [
            'heading' => ['sometimes', 'nullable', 'string', 'max:120'],
            'items' => ['sometimes', 'array', 'max:20'],
            'items.*.role' => ['required', 'string', 'max:120'],
            'items.*.company' => ['sometimes', 'nullable', 'string', 'max:120'],
            'items.*.location' => ['sometimes', 'nullable', 'string', 'max:120'],
            'items.*.start' => ['sometimes', 'nullable', 'string', 'max:32'],
            'items.*.end' => ['sometimes', 'nullable', 'string', 'max:32'],
            'items.*.current' => ['sometimes', 'boolean'],
            'items.*.description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'items.*.tags' => ['sometimes', 'array', 'max:8'],
            'items.*.tags.*' => ['string', 'max:32'],
        ];
    }

    /** @return array<string, mixed> */
    private function educationRules(): array
    {
        return [
            'heading' => ['sometimes', 'nullable', 'string', 'max:120'],
            'items' => ['sometimes', 'array', 'max:12'],
            'items.*.degree' => ['required', 'string', 'max:140'],
            'items.*.institution' => ['sometimes', 'nullable', 'string', 'max:140'],
            'items.*.location' => ['sometimes', 'nullable', 'string', 'max:120'],
            'items.*.start' => ['sometimes', 'nullable', 'string', 'max:32'],
            'items.*.end' => ['sometimes', 'nullable', 'string', 'max:32'],
            'items.*.description' => ['sometimes', 'nullable', 'string', 'max:600'],
        ];
    }

    /** @return array<string, mixed> */
    private function skillsRules(): array
    {
        return [
            'heading' => ['sometimes', 'nullable', 'string', 'max:120'],
            'groups' => ['sometimes', 'array', 'max:8'],
            'groups.*.name' => ['required', 'string', 'max:60'],
            'groups.*.items' => ['sometimes', 'array', 'max:24'],
            'groups.*.items.*' => ['string', 'max:40'],
        ];
    }

    /** @return array<string, mixed> */
    private function projectsRules(): array
    {
        return [
            'heading' => ['sometimes', 'nullable', 'string', 'max:120'],
            'intro' => ['sometimes', 'nullable', 'string', 'max:400'],
            'items' => ['sometimes', 'array', 'max:24'],
            'items.*.title' => ['required', 'string', 'max:140'],
            'items.*.description' => ['sometimes', 'nullable', 'string', 'max:800'],
            'items.*.image_media_id' => ['sometimes', 'nullable', 'integer'],
            'items.*.technologies' => ['sometimes', 'array', 'max:12'],
            'items.*.technologies.*' => ['string', 'max:32'],
            'items.*.url' => ['sometimes', 'nullable', 'string', 'max:500', 'url:http,https'],
            'items.*.github_url' => ['sometimes', 'nullable', 'string', 'max:500', 'url:http,https'],
            'items.*.year' => ['sometimes', 'nullable', 'string', 'max:16'],
            'items.*.featured' => ['sometimes', 'boolean'],
        ];
    }

    /** @return array<string, mixed> */
    private function servicesRules(): array
    {
        return [
            'heading' => ['sometimes', 'nullable', 'string', 'max:120'],
            'items' => ['sometimes', 'array', 'max:12'],
            'items.*.title' => ['required', 'string', 'max:120'],
            'items.*.description' => ['sometimes', 'nullable', 'string', 'max:600'],
            'items.*.price' => ['sometimes', 'nullable', 'string', 'max:40'],
        ];
    }

    /** @return array<string, mixed> */
    private function achievementsRules(): array
    {
        return [
            'heading' => ['sometimes', 'nullable', 'string', 'max:120'],
            'items' => ['sometimes', 'array', 'max:16'],
            'items.*.title' => ['required', 'string', 'max:160'],
            'items.*.issuer' => ['sometimes', 'nullable', 'string', 'max:120'],
            'items.*.date' => ['sometimes', 'nullable', 'string', 'max:32'],
            'items.*.url' => ['sometimes', 'nullable', 'string', 'max:500', 'url:http,https'],
            'items.*.description' => ['sometimes', 'nullable', 'string', 'max:600'],
        ];
    }

    /** @return array<string, mixed> */
    private function contactsRules(): array
    {
        return [
            'heading' => ['sometimes', 'nullable', 'string', 'max:120'],
            'intro' => ['sometimes', 'nullable', 'string', 'max:600'],
            'email' => ['sometimes', 'nullable', 'string', 'max:190', 'email:rfc'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:40'],
            'location' => ['sometimes', 'nullable', 'string', 'max:120'],
            'availability' => ['sometimes', 'nullable', 'string', 'max:140'],
            'cta_text' => ['sometimes', 'nullable', 'string', 'max:40'],
        ];
    }

    /** @return array<string, mixed> */
    private function socialLinksRules(): array
    {
        return [
            'items' => ['sometimes', 'array', 'max:10'],
            'items.*.platform' => ['required', 'string', 'in:'.implode(',', self::SOCIAL_PLATFORMS['platforms'])],
            'items.*.url' => ['required', 'string', 'max:500', 'url:http,https'],
            'items.*.label' => ['sometimes', 'nullable', 'string', 'max:40'],
        ];
    }
}
