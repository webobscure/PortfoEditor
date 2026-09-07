<?php

declare(strict_types=1);

namespace App\Enums;

enum SectionType: string
{
    case Hero = 'hero';
    case About = 'about';
    case Experience = 'experience';
    case Education = 'education';
    case Skills = 'skills';
    case Projects = 'projects';
    case Services = 'services';
    case Achievements = 'achievements';
    case Contacts = 'contacts';
    case SocialLinks = 'social_links';

    /** Human label used by the API so the client never hardcodes copy. */
    public function label(): string
    {
        return match ($this) {
            self::Hero => 'Hero',
            self::About => 'About',
            self::Experience => 'Experience',
            self::Education => 'Education',
            self::Skills => 'Skills',
            self::Projects => 'Projects',
            self::Services => 'Services',
            self::Achievements => 'Achievements',
            self::Contacts => 'Contact',
            self::SocialLinks => 'Social links',
        };
    }

    /** Types that may only appear once in a portfolio. */
    public function isSingleton(): bool
    {
        return match ($this) {
            self::Hero, self::Contacts, self::SocialLinks, self::About => true,
            default => false,
        };
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
