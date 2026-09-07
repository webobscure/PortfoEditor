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
            self::Hero => 'Обложка',
            self::About => 'О себе',
            self::Experience => 'Опыт',
            self::Education => 'Образование',
            self::Skills => 'Навыки',
            self::Projects => 'Проекты',
            self::Services => 'Услуги',
            self::Achievements => 'Достижения',
            self::Contacts => 'Контакты',
            self::SocialLinks => 'Соцсети',
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
