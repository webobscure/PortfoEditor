<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Сообщения валидации
|--------------------------------------------------------------------------
|
| Формулировки безличные («Поле … должно …»), потому что имена полей
| подставляются из массива attributes ниже и склонять их по роду
| в общем случае негде.
|
*/

return [

    'accepted' => 'Поле :attribute должно быть принято.',
    'accepted_if' => 'Поле :attribute должно быть принято, когда :other равно :value.',
    'active_url' => 'Поле :attribute должно быть корректной ссылкой.',
    'after' => 'Поле :attribute должно содержать дату после :date.',
    'after_or_equal' => 'Поле :attribute должно содержать дату не раньше :date.',
    'alpha' => 'Поле :attribute должно содержать только буквы.',
    'alpha_dash' => 'Поле :attribute должно содержать только буквы, цифры, дефис и подчёркивание.',
    'alpha_num' => 'Поле :attribute должно содержать только буквы и цифры.',
    'any_of' => 'Поле :attribute заполнено неверно.',
    'array' => 'Поле :attribute должно быть массивом.',
    'ascii' => 'Поле :attribute должно содержать только однобайтовые символы.',
    'before' => 'Поле :attribute должно содержать дату до :date.',
    'before_or_equal' => 'Поле :attribute должно содержать дату не позже :date.',
    'between' => [
        'array' => 'Поле :attribute должно содержать от :min до :max элементов.',
        'file' => 'Размер файла в поле :attribute должен быть от :min до :max КБ.',
        'numeric' => 'Значение поля :attribute должно быть от :min до :max.',
        'string' => 'Поле :attribute должно содержать от :min до :max символов.',
    ],
    'boolean' => 'Поле :attribute должно быть «да» или «нет».',
    'can' => 'Поле :attribute содержит недопустимое значение.',
    'confirmed' => 'Поле :attribute не совпадает с подтверждением.',
    'contains' => 'В поле :attribute не хватает обязательного значения.',
    'current_password' => 'Неверный пароль.',
    'date' => 'Поле :attribute должно содержать корректную дату.',
    'date_equals' => 'Поле :attribute должно содержать дату :date.',
    'date_format' => 'Поле :attribute должно соответствовать формату :format.',
    'decimal' => 'Поле :attribute должно содержать :decimal знаков после запятой.',
    'declined' => 'Поле :attribute должно быть отклонено.',
    'declined_if' => 'Поле :attribute должно быть отклонено, когда :other равно :value.',
    'different' => 'Поля :attribute и :other должны различаться.',
    'digits' => 'Поле :attribute должно содержать :digits цифр.',
    'digits_between' => 'Поле :attribute должно содержать от :min до :max цифр.',
    'dimensions' => 'Недопустимые размеры изображения в поле :attribute.',
    'distinct' => 'Поле :attribute содержит повторяющееся значение.',
    'doesnt_contain' => 'Поле :attribute не должно содержать ни одно из значений: :values.',
    'doesnt_end_with' => 'Поле :attribute не должно заканчиваться на: :values.',
    'doesnt_start_with' => 'Поле :attribute не должно начинаться с: :values.',
    'email' => 'Поле :attribute должно содержать корректный адрес электронной почты.',
    'encoding' => 'Поле :attribute должно быть в кодировке :encoding.',
    'ends_with' => 'Поле :attribute должно заканчиваться на одно из: :values.',
    'enum' => 'Выбрано недопустимое значение поля :attribute.',
    'exists' => 'Выбрано недопустимое значение поля :attribute.',
    'extensions' => 'Файл в поле :attribute должен иметь одно из расширений: :values.',
    'file' => 'Поле :attribute должно содержать файл.',
    'filled' => 'Поле :attribute не должно быть пустым.',
    'gt' => [
        'array' => 'Поле :attribute должно содержать больше :value элементов.',
        'file' => 'Размер файла в поле :attribute должен быть больше :value КБ.',
        'numeric' => 'Значение поля :attribute должно быть больше :value.',
        'string' => 'Поле :attribute должно содержать больше :value символов.',
    ],
    'gte' => [
        'array' => 'Поле :attribute должно содержать не менее :value элементов.',
        'file' => 'Размер файла в поле :attribute должен быть не меньше :value КБ.',
        'numeric' => 'Значение поля :attribute должно быть не меньше :value.',
        'string' => 'Поле :attribute должно содержать не менее :value символов.',
    ],
    'hex_color' => 'Поле :attribute должно содержать корректный цвет в формате HEX.',
    'image' => 'Поле :attribute должно содержать изображение.',
    'in' => 'Выбрано недопустимое значение поля :attribute.',
    'in_array' => 'Значение поля :attribute должно присутствовать в :other.',
    'in_array_keys' => 'Поле :attribute должно содержать хотя бы один из ключей: :values.',
    'integer' => 'Поле :attribute должно быть целым числом.',
    'ip' => 'Поле :attribute должно содержать корректный IP-адрес.',
    'ipv4' => 'Поле :attribute должно содержать корректный адрес IPv4.',
    'ipv6' => 'Поле :attribute должно содержать корректный адрес IPv6.',
    'json' => 'Поле :attribute должно содержать корректную строку JSON.',
    'list' => 'Поле :attribute должно быть списком.',
    'lowercase' => 'Поле :attribute должно быть в нижнем регистре.',
    'lt' => [
        'array' => 'Поле :attribute должно содержать меньше :value элементов.',
        'file' => 'Размер файла в поле :attribute должен быть меньше :value КБ.',
        'numeric' => 'Значение поля :attribute должно быть меньше :value.',
        'string' => 'Поле :attribute должно содержать меньше :value символов.',
    ],
    'lte' => [
        'array' => 'Поле :attribute должно содержать не более :value элементов.',
        'file' => 'Размер файла в поле :attribute должен быть не больше :value КБ.',
        'numeric' => 'Значение поля :attribute должно быть не больше :value.',
        'string' => 'Поле :attribute должно содержать не более :value символов.',
    ],
    'mac_address' => 'Поле :attribute должно содержать корректный MAC-адрес.',
    'max' => [
        'array' => 'Поле :attribute должно содержать не более :max элементов.',
        'file' => 'Размер файла в поле :attribute не должен превышать :max КБ.',
        'numeric' => 'Значение поля :attribute не должно превышать :max.',
        'string' => 'Поле :attribute не должно быть длиннее :max символов.',
    ],
    'max_digits' => 'Поле :attribute должно содержать не более :max цифр.',
    'mimes' => 'Поле :attribute должно содержать файл типа: :values.',
    'mimetypes' => 'Поле :attribute должно содержать файл типа: :values.',
    'min' => [
        'array' => 'Поле :attribute должно содержать не менее :min элементов.',
        'file' => 'Размер файла в поле :attribute должен быть не меньше :min КБ.',
        'numeric' => 'Значение поля :attribute должно быть не меньше :min.',
        'string' => 'Поле :attribute должно содержать не менее :min символов.',
    ],
    'min_digits' => 'Поле :attribute должно содержать не менее :min цифр.',
    'missing' => 'Поле :attribute должно отсутствовать.',
    'missing_if' => 'Поле :attribute должно отсутствовать, когда :other равно :value.',
    'missing_unless' => 'Поле :attribute должно отсутствовать, если :other не равно :value.',
    'missing_with' => 'Поле :attribute должно отсутствовать, когда указано :values.',
    'missing_with_all' => 'Поле :attribute должно отсутствовать, когда указаны :values.',
    'multiple_of' => 'Значение поля :attribute должно быть кратно :value.',
    'not_in' => 'Выбрано недопустимое значение поля :attribute.',
    'not_regex' => 'Неверный формат поля :attribute.',
    'numeric' => 'Поле :attribute должно быть числом.',
    'password' => [
        'letters' => 'Поле :attribute должно содержать хотя бы одну букву.',
        'mixed' => 'Поле :attribute должно содержать буквы в верхнем и нижнем регистре.',
        'numbers' => 'Поле :attribute должно содержать хотя бы одну цифру.',
        'symbols' => 'Поле :attribute должно содержать хотя бы один специальный символ.',
        'uncompromised' => 'Это значение поля :attribute встречалось в утечках данных. Выберите другое.',
    ],
    'present' => 'Поле :attribute должно присутствовать.',
    'present_if' => 'Поле :attribute должно присутствовать, когда :other равно :value.',
    'present_unless' => 'Поле :attribute должно присутствовать, если :other не равно :value.',
    'present_with' => 'Поле :attribute должно присутствовать, когда указано :values.',
    'present_with_all' => 'Поле :attribute должно присутствовать, когда указаны :values.',
    'prohibited' => 'Поле :attribute запрещено.',
    'prohibited_if' => 'Поле :attribute запрещено, когда :other равно :value.',
    'prohibited_if_accepted' => 'Поле :attribute запрещено, когда :other принято.',
    'prohibited_if_declined' => 'Поле :attribute запрещено, когда :other отклонено.',
    'prohibited_unless' => 'Поле :attribute запрещено, если :other не входит в :values.',
    'prohibits' => 'Поле :attribute запрещает присутствие :other.',
    'regex' => 'Неверный формат поля :attribute.',
    'required' => 'Поле :attribute обязательно для заполнения.',
    'required_array_keys' => 'Поле :attribute должно содержать записи для: :values.',
    'required_if' => 'Поле :attribute обязательно, когда :other равно :value.',
    'required_if_accepted' => 'Поле :attribute обязательно, когда :other принято.',
    'required_if_declined' => 'Поле :attribute обязательно, когда :other отклонено.',
    'required_unless' => 'Поле :attribute обязательно, если :other не входит в :values.',
    'required_with' => 'Поле :attribute обязательно, когда указано :values.',
    'required_with_all' => 'Поле :attribute обязательно, когда указаны :values.',
    'required_without' => 'Поле :attribute обязательно, когда не указано :values.',
    'required_without_all' => 'Поле :attribute обязательно, когда не указано ни одно из :values.',
    'same' => 'Поле :attribute должно совпадать с :other.',
    'size' => [
        'array' => 'Поле :attribute должно содержать :size элементов.',
        'file' => 'Размер файла в поле :attribute должен быть :size КБ.',
        'numeric' => 'Значение поля :attribute должно быть равно :size.',
        'string' => 'Поле :attribute должно содержать :size символов.',
    ],
    'starts_with' => 'Поле :attribute должно начинаться с одного из: :values.',
    'string' => 'Поле :attribute должно быть строкой.',
    'timezone' => 'Поле :attribute должно содержать корректный часовой пояс.',
    'unique' => 'Такое значение поля :attribute уже занято.',
    'uploaded' => 'Не удалось загрузить файл из поля :attribute.',
    'uppercase' => 'Поле :attribute должно быть в верхнем регистре.',
    'url' => 'Поле :attribute должно содержать корректную ссылку.',
    'ulid' => 'Поле :attribute должно содержать корректный ULID.',
    'uuid' => 'Поле :attribute должно содержать корректный UUID.',

    /*
    |--------------------------------------------------------------------------
    | Точечные сообщения
    |--------------------------------------------------------------------------
    */

    'custom' => [
        'email' => [
            'unique' => 'Этот адрес уже зарегистрирован.',
        ],
        'password' => [
            'confirmed' => 'Пароли не совпадают.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Названия полей
    |--------------------------------------------------------------------------
    |
    | Подставляются вместо :attribute. Заданы в именительном падеже, потому
    | что все формулировки выше построены как «Поле <название> …».
    |
    */

    'attributes' => [
        'name' => 'имя',
        'email' => 'электронная почта',
        'password' => 'пароль',
        'password_confirmation' => 'подтверждение пароля',
        'file' => 'файл',
        'slug' => 'адрес',
        'template_key' => 'шаблон',
        'status' => 'статус',
        'settings' => 'настройки',
        'meta' => 'мета-данные',
        'type' => 'тип секции',
        'position' => 'позиция',
        'enabled' => 'видимость',
        'content' => 'содержимое',
        'sections' => 'секции',
        'portfolio_id' => 'портфолио',
    ],

];
