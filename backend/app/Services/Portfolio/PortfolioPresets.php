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
 *
 * The copy is Russian, and written as a Russian professional would actually
 * write it — not translated from an English original. Sample content is read
 * as a suggestion of the register to aim for, so a stilted translation would
 * teach the wrong thing. Cities, companies and currencies are local for the
 * same reason.
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
                'name' => 'Ярослав Кузнецов',
                'title' => 'Ведущий backend-разработчик',
                'intro' => "Делаю те части продукта, которые обязаны работать в три часа ночи: платёжные конвейеры, событийные системы и скучную инфраструктуру, без которой не бывает интересных фич.\n\nСейчас в финтех-компании на стадии роста, до этого — агентство и консалтинг.",
                'cta_text' => 'Смотреть резюме',
                'cta_url' => 'https://example.com/cv.pdf',
                'secondary_cta_text' => 'Написать',
                'secondary_cta_url' => 'https://example.com/contact',
                'alignment' => 'left',
                'show_social' => true,
                'photo_media_id' => null,
            ],
            'about' => [
                'heading' => 'О себе',
                'body' => "Девять лет пишу сервисы, от которых зависят другие команды. Мне важны стыки: повторные попытки, идемпотентность, миграции, которые проходят без остановки сайта, и наблюдаемость, в которой можно разобраться в два часа ночи.\n\nЛюблю приходить в кодовую базу, у которой уже есть клиенты, и делать её спокойнее — а не переписывать с нуля.",
                'highlights' => [
                    ['label' => 'Лет в разработке', 'value' => '9'],
                    ['label' => 'Аптайм', 'value' => '99,98%'],
                    ['label' => 'Команд наставлял', 'value' => '4'],
                ],
                'photo_media_id' => null,
            ],
            'experience' => [
                'heading' => 'Опыт',
                'items' => [
                    [
                        'role' => 'Ведущий backend-разработчик',
                        'company' => 'Северный Платёж',
                        'location' => 'Удалённо',
                        'start' => '2022',
                        'end' => '',
                        'current' => true,
                        'description' => 'Отвечаю за сервисы реестра и расчётов. Сократил сверку с четырёх часов до одиннадцати минут, переведя пакетные задания на поток событий, и провёл уход от единственного primary в PostgreSQL без простоя.',
                        'tags' => ['Go', 'PostgreSQL', 'Kafka', 'Terraform'],
                    ],
                    [
                        'role' => 'Backend-разработчик',
                        'company' => 'Студия «Котёл»',
                        'location' => 'Тбилиси',
                        'start' => '2019',
                        'end' => '2022',
                        'current' => false,
                        'description' => 'Собрал API для десятка клиентских продуктов — от трекера логистики до платформы бронирования. Ввёл конвенции тестов и деплоя, которыми студия пользуется до сих пор.',
                        'tags' => ['PHP', 'Laravel', 'Vue', 'AWS'],
                    ],
                    [
                        'role' => 'Инженер-программист',
                        'company' => 'Халден Системы',
                        'location' => 'Казань',
                        'start' => '2016',
                        'end' => '2019',
                        'current' => false,
                        'description' => 'Поддерживал платформу промышленного мониторинга на одиннадцати площадках. Первый опыт систем, где неудачный деплой означал, что кто-то едет на завод.',
                        'tags' => ['Python', 'TimescaleDB'],
                    ],
                ],
            ],
            'skills' => [
                'heading' => 'Стек',
                'groups' => [
                    ['name' => 'Языки', 'items' => ['Go', 'PHP', 'TypeScript', 'Python', 'SQL']],
                    ['name' => 'Данные', 'items' => ['PostgreSQL', 'Redis', 'Kafka', 'ClickHouse']],
                    ['name' => 'Платформа', 'items' => ['Docker', 'Terraform', 'AWS', 'GitHub Actions', 'Grafana']],
                ],
            ],
            'projects' => [
                'heading' => 'Избранные работы',
                'intro' => 'Несколько вещей, о которых можно говорить публично.',
                'items' => [
                    [
                        'title' => 'Перестройка реестра',
                        'description' => 'Заменил ночную пакетную сверку журналом событий, в который можно только дописывать. Остатки стали видны в реальном времени, а финансы перестали заводить тикеты о пропавших строках.',
                        'technologies' => ['Go', 'Kafka', 'PostgreSQL'],
                        'url' => 'https://example.com/ledger',
                        'github_url' => '',
                        'year' => '2024',
                        'image_media_id' => null,
                        'featured' => true,
                    ],
                    [
                        'title' => 'pgshift',
                        'description' => 'Небольшая утилита для безопасных миграций PostgreSQL по схеме expand-and-contract на живом primary. Открытый код, им пользуются несколько команд помимо моей.',
                        'technologies' => ['Go', 'PostgreSQL'],
                        'url' => '',
                        'github_url' => 'https://github.com/example/pgshift',
                        'year' => '2023',
                        'image_media_id' => null,
                        'featured' => false,
                    ],
                    [
                        'title' => 'API платформы бронирования',
                        'description' => 'Мультиарендный бэкенд бронирования для клиента студии: 40 тысяч записей в месяц и модель расписания, пережившая три круга смены требований.',
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
                'heading' => 'Образование',
                'items' => [
                    [
                        'degree' => 'Бакалавр, прикладная математика и информатика',
                        'institution' => 'Университет ИТМО',
                        'location' => 'Санкт-Петербург',
                        'start' => '2012',
                        'end' => '2016',
                        'description' => '',
                    ],
                ],
            ],
            'contacts' => [
                'heading' => 'Связаться',
                'intro' => 'Открыт к сильным backend- и платформенным ролям, а также к коротким консультациям.',
                'email' => 'yaroslav@example.com',
                'phone' => '',
                'location' => 'Тбилиси',
                'availability' => 'Свободен с марта',
                'cta_text' => 'Написать письмо',
            ],
            'social_links' => [
                'items' => [
                    ['platform' => 'github', 'url' => 'https://github.com/example', 'label' => 'GitHub'],
                    ['platform' => 'linkedin', 'url' => 'https://linkedin.com/in/example', 'label' => 'LinkedIn'],
                    ['platform' => 'telegram', 'url' => 'https://t.me/example', 'label' => 'Telegram'],
                ],
            ],
        ];
    }

    /** @return array<string, array<string, mixed>> */
    private function designer(): array
    {
        return [
            'hero' => [
                'name' => 'Алиса Морозова',
                'title' => 'Продуктовый дизайнер',
                'intro' => 'Проектирую софт, которым пользуются каждый день не задумываясь. Десять лет в финтехе, медицине и инструментах для разработчиков — в основном те неэффектные экраны, где и делается настоящая работа.',
                'cta_text' => 'Смотреть работы',
                'cta_url' => 'https://example.com/work',
                'secondary_cta_text' => 'Скачать резюме',
                'secondary_cta_url' => 'https://example.com/cv.pdf',
                'alignment' => 'left',
                'show_social' => true,
                'photo_media_id' => null,
            ],
            'about' => [
                'heading' => 'О себе',
                'body' => "Начинала в печати, ушла в интерфейсы и не растеряла привычку следить за шрифтом и ритмом. Сейчас почти всё время занимают сценарии, которые трудно сделать хорошо: онбординг, права доступа, биллинг — там одно верное решение экономит поддержке тысячу писем.\n\nЛучше всего работаю внутри команды разработки: утром эскизы, днём разбор собранного билда.",
                'highlights' => [
                    ['label' => 'Лет в дизайне', 'value' => '10'],
                    ['label' => 'Продуктов выпущено', 'value' => '24'],
                    ['label' => 'Дизайн-систем собрано', 'value' => '3'],
                ],
                'photo_media_id' => null,
            ],
            'experience' => [
                'heading' => 'Опыт',
                'items' => [
                    [
                        'role' => 'Ведущий продуктовый дизайнер',
                        'company' => 'Фатом Health',
                        'location' => 'Москва',
                        'start' => '2021',
                        'end' => '',
                        'current' => true,
                        'description' => 'Веду дизайн рабочего места врача, которым пользуются 60 клиник. Переделала сценарий приёма и сократила запись консультации с девяти минут до четырёх.',
                        'tags' => ['Продуктовый дизайн', 'Дизайн-системы', 'Исследования'],
                    ],
                    [
                        'role' => 'Старший продуктовый дизайнер',
                        'company' => 'Равель',
                        'location' => 'Санкт-Петербург',
                        'start' => '2018',
                        'end' => '2021',
                        'current' => false,
                        'description' => 'Отвечала за биллинг и подписки в B2B-аналитике, вместе с двумя разработчиками собрала первую версию дизайн-системы.',
                        'tags' => ['SaaS', 'Дизайн-системы'],
                    ],
                    [
                        'role' => 'Дизайнер',
                        'company' => 'Студия «Мара»',
                        'location' => 'Калининград',
                        'start' => '2015',
                        'end' => '2018',
                        'current' => false,
                        'description' => 'Айдентика и интерфейсы для культурных институций и ранних продуктов. Здесь научилась нормально показывать работу.',
                        'tags' => ['Бренд', 'Веб'],
                    ],
                ],
            ],
            'skills' => [
                'heading' => 'Что умею',
                'groups' => [
                    ['name' => 'Дизайн', 'items' => ['Продуктовый дизайн', 'Дизайн-системы', 'Взаимодействие', 'Прототипы', 'Типографика']],
                    ['name' => 'Исследования', 'items' => ['Интервью с пользователями', 'Юзабилити-тесты', 'Карты пути']],
                    ['name' => 'Инструменты', 'items' => ['Figma', 'Framer', 'Principle', 'HTML и CSS']],
                ],
            ],
            'projects' => [
                'heading' => 'Избранные работы',
                'intro' => 'Четыре проекта, которые показывают ход мысли, а не только результат.',
                'items' => [
                    [
                        'title' => 'Рабочее место врача',
                        'description' => 'Один экран вместо четырёх. Неделю ходили за двенадцатью врачами, увидели, что время уходит на переключение контекста, и пересобрали день вокруг одной ленты. Запись консультации сократилась с девяти минут до четырёх.',
                        'technologies' => ['Продуктовый дизайн', 'Исследования', 'Дизайн-система'],
                        'url' => 'https://example.com/work/clinician',
                        'github_url' => '',
                        'year' => '2024',
                        'image_media_id' => null,
                        'featured' => true,
                    ],
                    [
                        'title' => 'Дизайн-система «Равель»',
                        'description' => 'Шестьдесят компонентов, одна шкала кеглей и модель вклада, пережившая удвоение команды. Смысл был не в библиотеке, а в том, чтобы у разработчиков по умолчанию получалось правильно.',
                        'technologies' => ['Дизайн-системы', 'Документация'],
                        'url' => 'https://example.com/work/ravel',
                        'github_url' => '',
                        'year' => '2023',
                        'image_media_id' => null,
                        'featured' => true,
                    ],
                    [
                        'title' => 'Счёт, который сам себя объясняет',
                        'description' => 'Оплата по потреблению честная, но непонятная. Пересобрали счёт вокруг короткой сводки человеческим языком, а расшифровку убрали на один клик вглубь. Обращения в поддержку по биллингу упали на 38% за квартал.',
                        'technologies' => ['Продуктовый дизайн', 'Редактура интерфейса'],
                        'url' => 'https://example.com/work/billing',
                        'github_url' => '',
                        'year' => '2022',
                        'image_media_id' => null,
                        'featured' => false,
                    ],
                    [
                        'title' => 'Айдентика «Музея звука»',
                        'description' => 'Айдентика и сайт для небольшого звукового архива. Сдержанная система вокруг одной антиквы и большого количества тишины, чтобы страницу держали записи.',
                        'technologies' => ['Бренд', 'Веб'],
                        'url' => 'https://example.com/work/museu',
                        'github_url' => '',
                        'year' => '2019',
                        'image_media_id' => null,
                        'featured' => false,
                    ],
                ],
            ],
            'education' => [
                'heading' => 'Образование',
                'items' => [
                    [
                        'degree' => 'Магистр, графический дизайн',
                        'institution' => 'Высшая школа экономики',
                        'location' => 'Москва',
                        'start' => '2013',
                        'end' => '2015',
                        'description' => '',
                    ],
                    [
                        'degree' => 'Бакалавр, визуальные коммуникации',
                        'institution' => 'Академия имени Штиглица',
                        'location' => 'Санкт-Петербург',
                        'start' => '2009',
                        'end' => '2013',
                        'description' => '',
                    ],
                ],
            ],
            'contacts' => [
                'heading' => 'Поработаем вместе',
                'intro' => 'Беру один проект за раз и отвечаю на каждое письмо.',
                'email' => 'alisa@example.com',
                'phone' => '',
                'location' => 'Москва',
                'availability' => 'Беру проекты с апреля',
                'cta_text' => 'Начать разговор',
            ],
            'social_links' => [
                'items' => [
                    ['platform' => 'behance', 'url' => 'https://behance.net/example', 'label' => 'Behance'],
                    ['platform' => 'telegram', 'url' => 'https://t.me/example', 'label' => 'Telegram'],
                    ['platform' => 'linkedin', 'url' => 'https://linkedin.com/in/example', 'label' => 'LinkedIn'],
                ],
            ],
        ];
    }

    /** @return array<string, array<string, mixed>> */
    private function photographer(): array
    {
        return [
            'hero' => [
                'name' => 'Нина Северова',
                'title' => 'Фотограф',
                'intro' => 'Документальная и портретная съёмка, в основном по заказу. Снимаю людей там, где они на самом деле находятся, а не там, где лучше выглядят.',
                'cta_text' => 'Смотреть серию',
                'cta_url' => 'https://example.com/series',
                'secondary_cta_text' => '',
                'secondary_cta_url' => '',
                'alignment' => 'left',
                'show_social' => true,
                'photo_media_id' => null,
            ],
            'about' => [
                'heading' => 'О себе',
                'body' => "Восемь лет снимаю прибрежные посёлки — это научило терпению больше, чем технике. Заказы для журналов и культурных институций, между ними — личные проекты вдолгую.\n\nБеру редакционные, портретные и документальные съёмки по России и Европе.",
                'highlights' => [
                    ['label' => 'Серий завершено', 'value' => '12'],
                    ['label' => 'Выставок', 'value' => '7'],
                    ['label' => 'База', 'value' => 'Мурманск'],
                ],
                'photo_media_id' => null,
            ],
            'projects' => [
                'heading' => 'Серии',
                'intro' => 'Долгая работа: месяцы, а не дни.',
                'items' => [
                    [
                        'title' => 'Последний паром',
                        'description' => 'Три зимы на ночной переправе между островами — снимала тех, для кого это не путешествие, а дорога на работу.',
                        'technologies' => ['Документальная', 'Чёрно-белая'],
                        'url' => 'https://example.com/ferry',
                        'github_url' => '',
                        'year' => '2024',
                        'image_media_id' => null,
                        'featured' => true,
                    ],
                    [
                        'title' => 'Комнаты, которые топят',
                        'description' => 'Портреты в передних комнатах посёлка, потерявшего порт. Средний формат, только доступный свет.',
                        'technologies' => ['Портрет', 'Средний формат'],
                        'url' => 'https://example.com/rooms',
                        'github_url' => '',
                        'year' => '2022',
                        'image_media_id' => null,
                        'featured' => true,
                    ],
                    [
                        'title' => 'Полевые заметки, Териберка',
                        'description' => 'Заказ культурного ежеквартальника о том, как меняется сезон трескового промысла.',
                        'technologies' => ['Редакционная'],
                        'url' => 'https://example.com/lofoten',
                        'github_url' => '',
                        'year' => '2021',
                        'image_media_id' => null,
                        'featured' => false,
                    ],
                ],
            ],
            'services' => [
                'heading' => 'Съёмки',
                'items' => [
                    ['title' => 'Редакционная съёмка', 'description' => 'Репортаж и портреты для журналов и изданий, сдача в течение 48 часов после съёмки.', 'price' => 'От 60 000 ₽ за день'],
                    ['title' => 'Портретная сессия', 'description' => 'Неторопливые два часа на локации и пятнадцать отретушированных кадров.', 'price' => 'От 30 000 ₽'],
                    ['title' => 'Документальный проект', 'description' => 'Длинная работа для институций и НКО, объём обсуждается под задачу.', 'price' => 'По запросу'],
                ],
            ],
            'achievements' => [
                'heading' => 'Избранные выставки',
                'items' => [
                    ['title' => 'Последний паром', 'issuer' => 'Центр фотографии, Москва', 'date' => '2024', 'url' => '', 'description' => 'Персональная выставка, 34 отпечатка.'],
                    ['title' => 'Новая северная документалистика', 'issuer' => 'Фотобиеннале, Нижний Новгород', 'date' => '2023', 'url' => '', 'description' => 'Групповая выставка.'],
                    ['title' => 'Комнаты, которые топят', 'issuer' => 'Фестиваль «Формат», Екатеринбург', 'date' => '2022', 'url' => '', 'description' => ''],
                ],
            ],
            'contacts' => [
                'heading' => 'Съёмки и отпечатки',
                'intro' => 'По заказам, продаже отпечатков и запросам в архив.',
                'email' => 'nina@example.com',
                'phone' => '',
                'location' => 'Мурманск',
                'availability' => 'Запись с июня',
                'cta_text' => 'Написать',
            ],
            'social_links' => [
                'items' => [
                    ['platform' => 'telegram', 'url' => 'https://t.me/example', 'label' => 'Telegram'],
                    ['platform' => 'website', 'url' => 'https://example.com', 'label' => 'Архив'],
                ],
            ],
        ];
    }

    /** @return array<string, array<string, mixed>> */
    private function product(): array
    {
        $content = $this->designer();

        $content['hero']['name'] = 'Семён Окулов';
        $content['hero']['title'] = 'Продакт-менеджер';
        $content['hero']['intro'] = 'Довожу продукты от «в целом работает» до «за это платят». В основном B2B SaaS и та часть, где стратегии приходится выжить при встрече с роадмапом.';
        $content['about']['body'] = "Семь лет в продукте, три из них — первым продактом в команде. Лучше всего мне там, где уже что-то выпустили и надо решить, что перестать делать.\n\nПишу спеки, которые разработчики правда читают, и скорее срежу объём, чем углы.";
        $content['about']['highlights'] = [
            ['label' => 'Лет в продукте', 'value' => '7'],
            ['label' => 'Выручки под влиянием', 'value' => '1,2 млрд ₽'],
            ['label' => 'Команд вёл', 'value' => '3'],
        ];
        $content['skills']['heading'] = 'Как я работаю';
        $content['skills']['groups'] = [
            ['name' => 'Продукт', 'items' => ['Дискавери', 'Роадмап', 'Ценообразование', 'Позиционирование', 'Аналитика']],
            ['name' => 'Исследования', 'items' => ['Интервью с клиентами', 'Разбор сделок', 'Юзабилити-тесты']],
            ['name' => 'Инструменты', 'items' => ['Amplitude', 'Linear', 'Figma', 'SQL']],
        ];
        $content['contacts']['heading'] = 'Связаться';
        $content['contacts']['intro'] = 'Открыт к сильным продуктовым ролям и к эдвайзингу для ранних команд.';
        $content['contacts']['email'] = 'semyon@example.com';

        return $content;
    }

    /** @return array<string, array<string, mixed>> */
    private function generic(): array
    {
        return [
            'hero' => [
                'name' => 'Ваше имя',
                'title' => 'Чем вы занимаетесь',
                'intro' => 'Одно-два предложения о работе, которой хочется больше. Напишите так, как сказали бы вслух — люди, которые это читают, решают, писать вам или нет.',
                'cta_text' => 'Связаться',
                'cta_url' => 'https://example.com',
                'secondary_cta_text' => '',
                'secondary_cta_url' => '',
                'alignment' => 'left',
                'show_social' => true,
                'photo_media_id' => null,
            ],
            'about' => [
                'heading' => 'О себе',
                'body' => 'Короткий абзац о вашем опыте и о том, в чём вы сильны. Второй — о том, как вам нравится работать или что вы ищете дальше.',
                'highlights' => [
                    ['label' => 'Лет опыта', 'value' => '5'],
                    ['label' => 'Проектов сдано', 'value' => '20'],
                ],
                'photo_media_id' => null,
            ],
            'experience' => [
                'heading' => 'Опыт',
                'items' => [
                    [
                        'role' => 'Ваша должность',
                        'company' => 'Компания',
                        'location' => 'Город',
                        'start' => '2022',
                        'end' => '',
                        'current' => true,
                        'description' => 'За что вы отвечали и один конкретный результат.',
                        'tags' => [],
                    ],
                ],
            ],
            'skills' => [
                'heading' => 'Навыки',
                'groups' => [
                    ['name' => 'Основное', 'items' => ['Добавьте навык', 'И ещё один']],
                ],
            ],
            'projects' => [
                'heading' => 'Избранные работы',
                'intro' => '',
                'items' => [
                    [
                        'title' => 'Проект',
                        'description' => 'В чём была задача, что вы сделали и что в итоге изменилось.',
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
                'heading' => 'Связаться',
                'intro' => '',
                'email' => 'you@example.com',
                'phone' => '',
                'location' => '',
                'availability' => '',
                'cta_text' => 'Написать письмо',
            ],
            'social_links' => [
                'items' => [],
            ],
        ];
    }
}
