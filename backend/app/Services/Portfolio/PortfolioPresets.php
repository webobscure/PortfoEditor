<?php

declare(strict_types=1);

namespace App\Services\Portfolio;

use App\Enums\SectionType;

/**
 * What a brand-new portfolio contains.
 *
 * The product promise is that a portfolio looks like a finished site within a
 * minute of signing up, so creation seeds real, plausible copy rather than an
 * empty form. Everything seeded here is marked `placeholder` in the section's
 * settings; the editor shows it as sample content and the flag clears the first
 * time the user edits that section, so nobody is ever confused about what is
 * theirs and what is ours.
 */
final class PortfolioPresets
{
    public const KEYS = ['developer', 'designer', 'photographer', 'product', 'other'];

    /** @return array<int, array{key:string,name:string,description:string,template:string}> */
    public function all(): array
    {
        return [
            ['key' => 'developer', 'name' => 'Портфолио разработчика', 'description' => 'Инженерная работа, стек и выпущенные проекты.', 'template' => 'developer-dark'],
            ['key' => 'designer', 'name' => 'Портфолио дизайнера', 'description' => 'Визуальные работы в виде крупных кейсов.', 'template' => 'minimal'],
            ['key' => 'photographer', 'name' => 'Портфолио фотографа', 'description' => 'Серии снимков с журнальной типографикой.', 'template' => 'editorial'],
            ['key' => 'product', 'name' => 'Продуктовое / UX-портфолио', 'description' => 'Результаты, процесс и измеримый эффект.', 'template' => 'minimal'],
            ['key' => 'other', 'name' => 'Что-то другое', 'description' => 'Чистая заготовка, которую вы соберёте сами.', 'template' => 'minimal'],
        ];
    }

    public function templateFor(string $preset): string
    {
        foreach ($this->all() as $entry) {
            if ($entry['key'] === $preset) {
                return $entry['template'];
            }
        }

        return 'minimal';
    }

    public function exists(string $preset): bool
    {
        return in_array($preset, self::KEYS, true);
    }

    /**
     * The sections a new portfolio starts with.
     *
     * @param  array{name?:string,title?:string,intro?:string}  $profile  answers from the create flow
     * @return array<int, array{type:SectionType,data:array<string,mixed>}>
     */
    public function sections(string $preset, array $profile = []): array
    {
        $content = $this->content($preset);

        $name = trim((string) ($profile['name'] ?? '')) ?: $content['hero']['name'];
        $title = trim((string) ($profile['title'] ?? '')) ?: $content['hero']['title'];
        $intro = trim((string) ($profile['intro'] ?? '')) ?: $content['hero']['intro'];

        $content['hero'] = array_merge($content['hero'], [
            'name' => $name,
            'title' => $title,
            'intro' => $intro,
        ]);

        $sections = [];

        foreach ($content as $type => $data) {
            $sectionType = SectionType::from($type);
            $sections[] = ['type' => $sectionType, 'data' => $data];
        }

        return $sections;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function content(string $preset): array
    {
        return match ($preset) {
            'developer' => $this->developer(),
            'photographer' => $this->photographer(),
            'product' => $this->product(),
            'designer' => $this->designer(),
            default => $this->generic(),
        };
    }

    /** @return array<string, array<string, mixed>> */
    private function developer(): array
    {
        return [
            'hero' => [
                'name' => 'Jordan Reyes',
                'title' => 'Senior backend engineer',
                'intro' => "I build the parts of a product that have to keep working at 3am — payment pipelines, event systems, and the boring infrastructure that makes the interesting features possible.\n\nCurrently at a fintech scale-up, previously agency and consultancy work.",
                'cta_text' => 'Read the CV',
                'cta_url' => 'https://example.com/cv.pdf',
                'secondary_cta_text' => 'Email me',
                'secondary_cta_url' => 'https://example.com/contact',
                'alignment' => 'left',
                'show_social' => true,
                'photo_media_id' => null,
            ],
            'about' => [
                'heading' => 'About',
                'body' => "Nine years writing services that other teams depend on. I care about the seams: retries, idempotency, migrations that run while the site is up, and observability you can actually reason about at 2am.\n\nI like joining a codebase that already has customers and making it calmer — not rewriting it.",
                'highlights' => [
                    ['label' => 'Years shipping', 'value' => '9'],
                    ['label' => 'Uptime held', 'value' => '99.98%'],
                    ['label' => 'Teams mentored', 'value' => '4'],
                ],
                'photo_media_id' => null,
            ],
            'experience' => [
                'heading' => 'Experience',
                'items' => [
                    [
                        'role' => 'Senior Backend Engineer',
                        'company' => 'Northbeam Payments',
                        'location' => 'Remote',
                        'start' => '2022',
                        'end' => '',
                        'current' => true,
                        'description' => 'Own the ledger and settlement services. Cut reconciliation time from four hours to eleven minutes by moving batch jobs onto an event stream, and led the migration off a single Postgres primary without downtime.',
                        'tags' => ['Go', 'PostgreSQL', 'Kafka', 'Terraform'],
                    ],
                    [
                        'role' => 'Backend Engineer',
                        'company' => 'Kettle Studio',
                        'location' => 'Berlin',
                        'start' => '2019',
                        'end' => '2022',
                        'current' => false,
                        'description' => 'Built APIs for a dozen client products, from a logistics tracker to a booking platform. Introduced the testing and deploy conventions the studio still uses.',
                        'tags' => ['PHP', 'Laravel', 'Vue', 'AWS'],
                    ],
                    [
                        'role' => 'Software Engineer',
                        'company' => 'Halden Systems',
                        'location' => 'Oslo',
                        'start' => '2016',
                        'end' => '2019',
                        'current' => false,
                        'description' => 'Maintained an industrial monitoring platform used across eleven sites. First exposure to systems where a bad deploy meant somebody drove to a factory.',
                        'tags' => ['Python', 'TimescaleDB'],
                    ],
                ],
            ],
            'skills' => [
                'heading' => 'Stack',
                'groups' => [
                    ['name' => 'Languages', 'items' => ['Go', 'PHP', 'TypeScript', 'Python', 'SQL']],
                    ['name' => 'Data', 'items' => ['PostgreSQL', 'Redis', 'Kafka', 'ClickHouse']],
                    ['name' => 'Platform', 'items' => ['Docker', 'Terraform', 'AWS', 'GitHub Actions', 'Grafana']],
                ],
            ],
            'projects' => [
                'heading' => 'Selected work',
                'intro' => 'A few things I can talk about publicly.',
                'items' => [
                    [
                        'title' => 'Ledger rebuild',
                        'description' => 'Replaced a nightly batch reconciliation with an append-only event ledger. Balances became queryable in real time and the finance team stopped filing tickets about missing rows.',
                        'technologies' => ['Go', 'Kafka', 'PostgreSQL'],
                        'url' => 'https://example.com/ledger',
                        'github_url' => '',
                        'year' => '2024',
                        'image_media_id' => null,
                        'featured' => true,
                    ],
                    [
                        'title' => 'pgshift',
                        'description' => 'A small CLI for running expand-and-contract Postgres migrations safely against a live primary. Open source, used by a handful of teams beyond mine.',
                        'technologies' => ['Go', 'PostgreSQL'],
                        'url' => '',
                        'github_url' => 'https://github.com/example/pgshift',
                        'year' => '2023',
                        'image_media_id' => null,
                        'featured' => false,
                    ],
                    [
                        'title' => 'Booking platform API',
                        'description' => 'Multi-tenant booking backend for a studio client, handling 40k reservations a month with a scheduling model that survived three rounds of requirement changes.',
                        'technologies' => ['Laravel', 'MySQL', 'Redis'],
                        'url' => 'https://example.com/booking',
                        'github_url' => '',
                        'year' => '2021',
                        'image_media_id' => null,
                        'featured' => false,
                    ],
                ],
            ],
            'education' => [
                'heading' => 'Education',
                'items' => [
                    [
                        'degree' => 'BSc Computer Science',
                        'institution' => 'University of Bergen',
                        'location' => 'Norway',
                        'start' => '2012',
                        'end' => '2015',
                        'description' => '',
                    ],
                ],
            ],
            'contacts' => [
                'heading' => 'Get in touch',
                'intro' => 'Open to senior backend and platform roles, and to short consulting engagements.',
                'email' => 'jordan@example.com',
                'phone' => '',
                'location' => 'Lisbon, Portugal',
                'availability' => 'Available from March',
                'cta_text' => 'Send an email',
            ],
            'social_links' => [
                'items' => [
                    ['platform' => 'github', 'url' => 'https://github.com/example', 'label' => 'GitHub'],
                    ['platform' => 'linkedin', 'url' => 'https://linkedin.com/in/example', 'label' => 'LinkedIn'],
                    ['platform' => 'x', 'url' => 'https://x.com/example', 'label' => 'X'],
                ],
            ],
        ];
    }

    /** @return array<string, array<string, mixed>> */
    private function designer(): array
    {
        return [
            'hero' => [
                'name' => 'Alex Morgan',
                'title' => 'Product designer',
                'intro' => 'I design software that people use every day without thinking about it. Ten years across fintech, health and developer tools — mostly the unglamorous screens where the real work happens.',
                'cta_text' => 'View work',
                'cta_url' => 'https://example.com/work',
                'secondary_cta_text' => 'Download CV',
                'secondary_cta_url' => 'https://example.com/cv.pdf',
                'alignment' => 'left',
                'show_social' => true,
                'photo_media_id' => null,
            ],
            'about' => [
                'heading' => 'About',
                'body' => "I started in print, moved to interfaces, and never lost the habit of caring about type and rhythm. These days I spend most of my time on flows that are hard to get right — onboarding, permissions, billing — where a good decision saves a support team a thousand emails.\n\nI work best embedded with engineers, sketching in the morning and reviewing a build in the afternoon.",
                'highlights' => [
                    ['label' => 'Years designing', 'value' => '10'],
                    ['label' => 'Products shipped', 'value' => '24'],
                    ['label' => 'Design systems built', 'value' => '3'],
                ],
                'photo_media_id' => null,
            ],
            'experience' => [
                'heading' => 'Experience',
                'items' => [
                    [
                        'role' => 'Lead Product Designer',
                        'company' => 'Fathom Health',
                        'location' => 'London',
                        'start' => '2021',
                        'end' => '',
                        'current' => true,
                        'description' => 'Lead design for the clinician workspace used in 60 practices. Redesigned the triage flow and cut the time to log a consultation from nine minutes to under four.',
                        'tags' => ['Product design', 'Design systems', 'Research'],
                    ],
                    [
                        'role' => 'Senior Product Designer',
                        'company' => 'Ravel',
                        'location' => 'Amsterdam',
                        'start' => '2018',
                        'end' => '2021',
                        'current' => false,
                        'description' => 'Owned the billing and subscription experience for a B2B analytics product, and built the first version of the design system with two engineers.',
                        'tags' => ['SaaS', 'Design systems'],
                    ],
                    [
                        'role' => 'Designer',
                        'company' => 'Studio Mara',
                        'location' => 'Lisbon',
                        'start' => '2015',
                        'end' => '2018',
                        'current' => false,
                        'description' => 'Brand and interface work for cultural institutions and early-stage products. Where I learned to present work properly.',
                        'tags' => ['Brand', 'Web'],
                    ],
                ],
            ],
            'skills' => [
                'heading' => 'Capabilities',
                'groups' => [
                    ['name' => 'Design', 'items' => ['Product design', 'Design systems', 'Interaction', 'Prototyping', 'Typography']],
                    ['name' => 'Research', 'items' => ['User interviews', 'Usability testing', 'Journey mapping']],
                    ['name' => 'Tools', 'items' => ['Figma', 'Framer', 'Principle', 'HTML & CSS']],
                ],
            ],
            'projects' => [
                'heading' => 'Selected work',
                'intro' => 'Four projects that show how I think, not just what I shipped.',
                'items' => [
                    [
                        'title' => 'Clinician workspace',
                        'description' => 'A single screen replacing four. We shadowed twelve clinicians for a week, found that most of the time went to context switching, and rebuilt the day around one timeline. Consultation logging dropped from nine minutes to under four.',
                        'technologies' => ['Product design', 'Research', 'Design system'],
                        'url' => 'https://example.com/work/clinician',
                        'github_url' => '',
                        'year' => '2024',
                        'image_media_id' => null,
                        'featured' => true,
                    ],
                    [
                        'title' => 'Ravel design system',
                        'description' => 'Sixty components, one type scale, and a contribution model that survived the team doubling. The point was never the library — it was giving engineers a default that was already right.',
                        'technologies' => ['Design systems', 'Documentation'],
                        'url' => 'https://example.com/work/ravel',
                        'github_url' => '',
                        'year' => '2023',
                        'image_media_id' => null,
                        'featured' => true,
                    ],
                    [
                        'title' => 'Billing that explains itself',
                        'description' => 'Usage-based pricing is honest and confusing. We rebuilt the invoice around a plain-language summary and put the breakdown one click away. Billing support tickets fell by 38% in a quarter.',
                        'technologies' => ['Product design', 'Content design'],
                        'url' => 'https://example.com/work/billing',
                        'github_url' => '',
                        'year' => '2022',
                        'image_media_id' => null,
                        'featured' => false,
                    ],
                    [
                        'title' => 'Museu do Som identity',
                        'description' => 'Identity and site for a small sound archive in Lisbon. A restrained system built around one serif and a lot of silence, so the recordings carry the page.',
                        'technologies' => ['Brand', 'Web'],
                        'url' => 'https://example.com/work/museu',
                        'github_url' => '',
                        'year' => '2019',
                        'image_media_id' => null,
                        'featured' => false,
                    ],
                ],
            ],
            'education' => [
                'heading' => 'Education',
                'items' => [
                    [
                        'degree' => 'MA Graphic Design',
                        'institution' => 'Central Saint Martins',
                        'location' => 'London',
                        'start' => '2013',
                        'end' => '2015',
                        'description' => '',
                    ],
                    [
                        'degree' => 'BA Visual Communication',
                        'institution' => 'FBAUL',
                        'location' => 'Lisbon',
                        'start' => '2009',
                        'end' => '2013',
                        'description' => '',
                    ],
                ],
            ],
            'contacts' => [
                'heading' => 'Work together',
                'intro' => 'I take on one freelance engagement at a time, and I answer every email.',
                'email' => 'alex@example.com',
                'phone' => '',
                'location' => 'London, UK',
                'availability' => 'Taking projects from April',
                'cta_text' => 'Start a conversation',
            ],
            'social_links' => [
                'items' => [
                    ['platform' => 'dribbble', 'url' => 'https://dribbble.com/example', 'label' => 'Dribbble'],
                    ['platform' => 'linkedin', 'url' => 'https://linkedin.com/in/example', 'label' => 'LinkedIn'],
                    ['platform' => 'instagram', 'url' => 'https://instagram.com/example', 'label' => 'Instagram'],
                ],
            ],
        ];
    }

    /** @return array<string, array<string, mixed>> */
    private function photographer(): array
    {
        return [
            'hero' => [
                'name' => 'Nina Halvorsen',
                'title' => 'Photographer',
                'intro' => 'Documentary and portrait work, mostly on assignment. I photograph people where they actually are rather than where they look best.',
                'cta_text' => 'See the series',
                'cta_url' => 'https://example.com/series',
                'secondary_cta_text' => '',
                'secondary_cta_url' => '',
                'alignment' => 'left',
                'show_social' => true,
                'photo_media_id' => null,
            ],
            'about' => [
                'heading' => 'About',
                'body' => "I have been photographing coastal communities for eight years, which taught me patience more than technique. Assignment work for magazines and cultural institutions; long-form personal projects in between.\n\nAvailable for editorial, portrait and documentary commissions across Europe.",
                'highlights' => [
                    ['label' => 'Series completed', 'value' => '12'],
                    ['label' => 'Exhibitions', 'value' => '7'],
                    ['label' => 'Based in', 'value' => 'Oslo'],
                ],
                'photo_media_id' => null,
            ],
            'projects' => [
                'heading' => 'Series',
                'intro' => 'Long-form work, shot over months rather than days.',
                'items' => [
                    [
                        'title' => 'The last ferry',
                        'description' => 'Three winters aboard the night crossing between two islands, photographing the people for whom it is not a journey but a commute.',
                        'technologies' => ['Documentary', 'Black and white'],
                        'url' => 'https://example.com/ferry',
                        'github_url' => '',
                        'year' => '2024',
                        'image_media_id' => null,
                        'featured' => true,
                    ],
                    [
                        'title' => 'Rooms kept warm',
                        'description' => 'Portraits made in the front rooms of a village that lost its harbour. Shot on medium format, available light only.',
                        'technologies' => ['Portrait', 'Medium format'],
                        'url' => 'https://example.com/rooms',
                        'github_url' => '',
                        'year' => '2022',
                        'image_media_id' => null,
                        'featured' => true,
                    ],
                    [
                        'title' => 'Field notes, Lofoten',
                        'description' => 'A commission for a cultural quarterly on the changing season of the cod fishery.',
                        'technologies' => ['Editorial'],
                        'url' => 'https://example.com/lofoten',
                        'github_url' => '',
                        'year' => '2021',
                        'image_media_id' => null,
                        'featured' => false,
                    ],
                ],
            ],
            'services' => [
                'heading' => 'Commissions',
                'items' => [
                    ['title' => 'Editorial assignment', 'description' => 'Reportage and portraits for magazines and newspapers, delivered within 48 hours of the shoot.', 'price' => 'From €900 / day'],
                    ['title' => 'Portrait sitting', 'description' => 'An unhurried two-hour session, on location, with fifteen retouched frames.', 'price' => 'From €450'],
                    ['title' => 'Documentary project', 'description' => 'Longer-form work for institutions and NGOs, scoped per commission.', 'price' => 'On request'],
                ],
            ],
            'achievements' => [
                'heading' => 'Selected exhibitions',
                'items' => [
                    ['title' => 'The last ferry', 'issuer' => 'Fotogalleriet, Oslo', 'date' => '2024', 'url' => '', 'description' => 'Solo exhibition, 34 prints.'],
                    ['title' => 'New Nordic Documentary', 'issuer' => 'Landskrona Foto', 'date' => '2023', 'url' => '', 'description' => 'Group exhibition.'],
                    ['title' => 'Rooms kept warm', 'issuer' => 'Format Festival, Derby', 'date' => '2022', 'url' => '', 'description' => ''],
                ],
            ],
            'contacts' => [
                'heading' => 'Commissions and prints',
                'intro' => 'For assignments, print sales or archive requests.',
                'email' => 'nina@example.com',
                'phone' => '',
                'location' => 'Oslo, Norway',
                'availability' => 'Booking from June',
                'cta_text' => 'Get in touch',
            ],
            'social_links' => [
                'items' => [
                    ['platform' => 'instagram', 'url' => 'https://instagram.com/example', 'label' => 'Instagram'],
                    ['platform' => 'website', 'url' => 'https://example.com', 'label' => 'Archive'],
                ],
            ],
        ];
    }

    /** @return array<string, array<string, mixed>> */
    private function product(): array
    {
        $content = $this->designer();

        $content['hero']['name'] = 'Sam Okafor';
        $content['hero']['title'] = 'Product manager';
        $content['hero']['intro'] = "I take products from 'this roughly works' to 'people pay for this'. Mostly B2B SaaS, mostly the part where strategy has to survive contact with a roadmap.";
        $content['about']['body'] = "Seven years in product, three of them as the first PM in the room. I am at my best with a team that has shipped something real and needs to decide what to stop doing.\n\nI write specs that engineers actually read, and I would rather cut scope than a corner.";
        $content['about']['highlights'] = [
            ['label' => 'Years in product', 'value' => '7'],
            ['label' => 'ARR influenced', 'value' => '$14M'],
            ['label' => 'Teams led', 'value' => '3'],
        ];
        $content['skills']['heading'] = 'How I work';
        $content['skills']['groups'] = [
            ['name' => 'Product', 'items' => ['Discovery', 'Roadmapping', 'Pricing', 'Positioning', 'Analytics']],
            ['name' => 'Research', 'items' => ['Customer interviews', 'Win/loss analysis', 'Usability testing']],
            ['name' => 'Tools', 'items' => ['Amplitude', 'Linear', 'Figma', 'SQL']],
        ];
        $content['contacts']['heading'] = 'Get in touch';
        $content['contacts']['intro'] = 'Open to senior product roles and to advisory work with early-stage teams.';
        $content['contacts']['email'] = 'sam@example.com';

        return $content;
    }

    /** @return array<string, array<string, mixed>> */
    private function generic(): array
    {
        return [
            'hero' => [
                'name' => 'Your name',
                'title' => 'What you do',
                'intro' => 'One or two sentences about the work you want more of. Say it the way you would say it out loud — the people reading this are deciding whether to email you.',
                'cta_text' => 'Get in touch',
                'cta_url' => 'https://example.com',
                'secondary_cta_text' => '',
                'secondary_cta_url' => '',
                'alignment' => 'left',
                'show_social' => true,
                'photo_media_id' => null,
            ],
            'about' => [
                'heading' => 'About',
                'body' => 'A short paragraph about your background and what you are good at. A second one about how you like to work, or what you are looking for next.',
                'highlights' => [
                    ['label' => 'Years of experience', 'value' => '5'],
                    ['label' => 'Projects delivered', 'value' => '20'],
                ],
                'photo_media_id' => null,
            ],
            'experience' => [
                'heading' => 'Experience',
                'items' => [
                    [
                        'role' => 'Your role',
                        'company' => 'Company',
                        'location' => 'City',
                        'start' => '2022',
                        'end' => '',
                        'current' => true,
                        'description' => 'What you were responsible for, and one concrete result.',
                        'tags' => [],
                    ],
                ],
            ],
            'skills' => [
                'heading' => 'Skills',
                'groups' => [
                    ['name' => 'Core', 'items' => ['Add a skill', 'And another']],
                ],
            ],
            'projects' => [
                'heading' => 'Selected work',
                'intro' => '',
                'items' => [
                    [
                        'title' => 'A project',
                        'description' => 'What the problem was, what you did, and what changed as a result.',
                        'technologies' => [],
                        'url' => '',
                        'github_url' => '',
                        'year' => '2024',
                        'image_media_id' => null,
                        'featured' => false,
                    ],
                ],
            ],
            'contacts' => [
                'heading' => 'Get in touch',
                'intro' => '',
                'email' => 'you@example.com',
                'phone' => '',
                'location' => '',
                'availability' => '',
                'cta_text' => 'Send an email',
            ],
            'social_links' => [
                'items' => [],
            ],
        ];
    }
}
