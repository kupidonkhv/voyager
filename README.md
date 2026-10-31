# Voyager — недостающая админка Laravel

[![Laravel](https://img.shields.io/badge/Laravel-10%20%7C%2011%20%7C%2012%20%7C%2013-FF2D20?logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2%20%7C%208.3%20%7C%208.4-777BB4?logo=php&logoColor=white)](https://www.php.net)
[![Version](https://img.shields.io/badge/version-1.7.47-blue)](https://github.com/kupidonkhv/voyager/releases)
[![License](https://img.shields.io/badge/license-MIT-green)](license)

Voyager — это админ-панель и система BREAD (Browse, Read, Edit, Add, Delete) для Laravel. Форк `kupidonkhv/voyager-fork` поддерживает актуальные версии Laravel и PHP, а также включает готовые Галереи, Капчу и набор расширенных полей BREAD.

- Репозиторий форка: <https://github.com/kupidonkhv/voyager>
- Оригинальная документация: <https://voyager-docs.devdojo.com/>
- Демонстрация: <https://voyager.devdojo.com/>
- Пакет: `kupidonkhv/voyager-fork`

> Этот README полностью переведён на русский язык и адаптирован под форк 1.7.x. Код, имена классов, ключи конфигурации и JSON-примеры оставлены без изменений. Все страницы оригинальной документации переработаны с учётом поддержки Laravel 10–13, PHP 8.2–8.4, Галерей, Капчи и встроенных расширений.

---

## Содержание

- [Что нового в форке](#что-нового-в-форке)
- [Введение](#введение)
- [Что такое Voyager](#что-такое-voyager)
- [Требования](#требования)
- [Установка](#установка)
- [Обновление](#обновление)
- [Конфигурация](#конфигурация)
- [Галереи](#галереи)
- [Капча](#капча)
- [Встроенное расширение](#встроенное-расширение)
- [BREAD](#bread)
- [Связи](#связи)
- [Formfields](#formfields)
- [Checkbox, множественные чекбоксы и радиокнопки](#checkbox-множественные-чекбоксы-и-радиокнопки)
- [Поле Coordinates](#поле-coordinates)
- [Дата и время](#дата-и-время)
- [Dropdown](#dropdown)
- [Изображения](#изображения)
- [Media Picker](#media-picker)
- [Number](#number)
- [TinyMCE](#tinymce)
- [Маршрутизация](#маршрутизация)
- [Медиа-менеджер](#медиа-менеджер)
- [Меню и конструктор меню](#меню-и-конструктор-меню)
- [Менеджер базы данных](#менеджер-базы-данных)
- [Настройки](#настройки)
- [Compass](#compass)
- [Роли и разрешения](#роли-и-разрешения)
- [Helper-методы](#helper-методы)
- [Мультиязычность](#мультиязычность)
- [Переопределение файлов](#переопределение-файлов)
- [Переопределение маршрутов](#переопределение-маршрутов)
- [Дополнительные CSS и JS](#дополнительные-css-и-js)
- [Мягкое удаление](#мягкое-удаление)
- [Дополнительные атрибуты связей](#дополнительные-атрибуты-связей)
- [Свои formfields](#свои-formfields)
- [Координаты в модели](#координаты-в-модели)
- [BREAD-аксессоры](#bread-аксессоры)
- [Собственный guard](#собственный-guard)
- [Кнопки действий](#кнопки-действий)
- [Решение проблем](#решение-проблем)
- [Разработка и тестирование](#разработка-и-тестирование)

---

## Что нового в форке

### Поддержка Laravel и PHP

- Laravel 10.x, 11.x, 12.x и 13.x. Версия 1.7.47 проверена на Laravel 13.39.0.
- PHP 8.2, 8.3 и 8.4. Исправлены неявно nullable-параметры, несовместимые с PHP 8.4.
- Intervention Image 3.7+ и 4.x, включая нативный image-менеджер Laravel 13.
- Работа со схемой и интроспекцией базы данных без Doctrine DBAL, включая SQLite.
- Форматирование дат через `Voyager::formatDate` совместимо с Carbon 3.
- После регистрации маршрутов обновляются name-lookups Laravel, поэтому BREAD-маршруты доступны и при повторной загрузке `routes/web.php`.
- `voyager:make:model` сохраняет сигнатуру Voyager на Laravel 13.
- Кеширование меню учитывает allow-list классов кеша Laravel.
- Обновлены Artisan-команды, Compass, MakeModel и тесты под Laravel 13 / PHPUnit 12.
- Версия 1.7.46 исправляет упаковку релиза: представления, ресурсы и переводы расширения входят в архив, а правило `.gitignore` больше не скрывает `resources/views/vendor`, `publishable/assets/vendor` и `publishable/lang/vendor`.

> Для Laravel 8–9 используйте Voyager 1.6.x. Для Laravel 6–7 используйте [Voyager 1.5](https://github.com/the-control-group/voyager/tree/1.5).

### Кратко о галереях

Начиная с 1.7.47 в Voyager встроен BREAD-раздел «Галереи»:

- загрузка фотографий, подписи, сортировка перетаскиванием, выбор размера миниатюр;
- автоматическое создание пункта меню админки с учётом `APP_LOCALE` (готовые переводы для `en` и `ru`, для остальных языков — английский fallback);
- вставка галереи в контент через `%gallery="Название галереи"%`;
- готовые шаблоны вывода `default`, `photo-carousel` и `rounded`;
- отдельная установка и повторная установка командой `php artisan voyager:gallery:install`;
- хранение изображений через `spatie/laravel-medialibrary`.

Подробности — в разделе [Галереи](#галереи).

### Кратко о капче

Начиная с 1.7.45 в пакет встроена Капча:

- изображения по адресу `/captcha/{config}`;
- API-капча по адресу `/captcha/api/{config}`;
- helper-функции `captcha()`, `captcha_src()`, `captcha_img()`, `captcha_check()`, `captcha_api_check()`;
- правила валидации `captcha` и `captcha_api`;
- конфигурации `default`, `math`, `flat`, `mini`, `inverse`.

Подробности — в разделе [Капча](#капча).

### Кратко о расширении

Начиная с 1.7.45 в пакет встроен `kupidonkhv/voyager-extension`:

- расширенные BREAD-шаблоны и липкая панель действий;
- клонирование записей;
- поля `adv_image`, `adv_media_files`, `adv_inline_set`, `adv_select_dropdown_tree`, `adv_fields_group`, `adv_json`, `adv_related`, `adv_page_layout`;
- генераторы путей и URL для Media Library;
- расширенный конструктор меню.

Отдельные пакеты `monstrex/voyager-extension`, `kupidonkhv/captcha` и `mews/captcha` больше не нужны и несовместимы с этой версией.

Подробности — в разделе [Встроенное расширение](#встроенное-расширение).

### Проверка релиза

```bash
composer validate
vendor/bin/phpunit
```

Текущий набор: 140 тестов, 937 утверждений (PHP 8.4). Дополнительно проверена установка в чистом приложении Laravel 13.39.0.

---

## Введение

Добро пожаловать в документацию Voyager для версии 1.7.x. Здесь описано, как установить, настроить и использовать Voyager, чтобы быстрее создавать административные панели и управлять данными приложения.

![Voyager Screenshot](https://s3.amazonaws.com/thecontrolgroup/voyager-screenshot.png)

Перед установкой стоит понять, чем Voyager является, а чем — нет. Об этом следующий раздел.

---

## Что такое Voyager

Прежде чем устанавливать Voyager, важно понять, _что это_ и _чем это не является_.

### Что это

- Интерфейс администратора для вашего Laravel-приложения.
- Простой способ добавлять, редактировать и удалять данные приложения.
- Конструктор меню: меню создаются в Voyager и выводятся в приложении.
- Медиа-менеджер для файлов.
- Генератор CRUD/BREAD. Подробнее: [BREAD](#bread).

Voyager — это просто админка для Laravel-приложения. Что делать на фронтенде — решаете вы. Voyager помогает добавлять данные, редактировать пользователей, создавать меню и выполнять другие административные задачи.

### Чем это не является

- Системой управления контентом (CMS).
- Платформой для блогов.
- WordPress.

Voyager — не CMS и не блог-платформа. На нём можно построить CMS или блог, но «из коробки» он ни тем, ни другим не является. Вы полностью контролируете поведение и функциональность приложения.

С Laravel и Voyager можно создать любое приложение. Это инструменты, которые упрощают разработку.

---

## Требования

Перед установкой убедитесь, что выполнены следующие требования:

- Laravel 10, 11, 12 или 13.
- PHP 8.2, 8.3 или 8.4.
- Расширения PHP `gd` и `json`.
- Composer 2.
- Поддерживаемая Laravel база данных: MySQL, MariaDB, PostgreSQL, SQLite или SQL Server. Работа со схемой без Doctrine DBAL проверена на SQLite.
- Права на запись в `storage` и `bootstrap/cache`.
- Возможность создать символическую ссылку `public/storage`.

Для старых версий Laravel:

- Laravel 8–9 — Voyager 1.6.x.
- Laravel 6–7 — Voyager 1.5.x.

---

## Установка

Установка Voyager состоит из нескольких шагов.

### 1. Подключите пакет

Создайте новое Laravel-приложение и добавьте пакет:

```bash
composer require kupidonkhv/voyager-fork:^1.7.47
```

Если в проекте уже установлены старые отдельные пакеты, сначала удалите их:

```bash
composer remove monstrex/voyager-extension kupidonkhv/captcha mews/captcha
composer require kupidonkhv/voyager-fork:^1.7.47
```

> Если Packagist ещё не проиндексировал новый релиз, подождите несколько минут или установите ветку разработки:
> `composer require kupidonkhv/voyager-fork:dev-1.7`

### 2. Настройте базу данных и APP_URL

Создайте базу данных и добавьте учётные данные в `.env`:

```dotenv
DB_HOST=localhost
DB_DATABASE=homestead
DB_USERNAME=homestead
DB_PASSWORD=secret
```

Также задайте адрес приложения:

```dotenv
APP_URL=http://localhost:8000
```

### 3. Запустите установщик

Voyager можно установить с демо-данными или без них. Демо-данные включают одного администратора (если в базе ещё нет пользователей), одну демонстрационную страницу, четыре демонстрационных поста, две категории и семь настроек.

Установка без демо-данных:

```bash
php artisan voyager:install
```

Установка с демо-данными:

```bash
php artisan voyager:install --with-dummy
```

Установщик публикует ресурсы, выполняет миграции, добавляет маршруты, создаёт сиды, устанавливает BREAD «Галереи» и создаёт символическую ссылку `storage`.

Если вы видите ошибку **Specified key was too long**, скорее всего, используется устаревшая версия MySQL. Решение описано в [Laravel News](https://laravel-news.com/laravel-5-4-key-too-long-error).

Запустите локальный сервер:

```bash
php artisan serve
```

И откройте <http://localhost:8000/admin>.

При установке с демо-данными создаётся пользователь:

> **email:** `admin@admin.com`
> **password:** `password`

> Демо-пользователь создаётся **только** если в базе ещё нет пользователей.

### 4. Назначьте администратора

Если демо-данные не устанавливались, можно выдать права администратора существующему пользователю:

```bash
php artisan voyager:admin your@email.com
```

Чтобы создать нового администратора, передайте флаг `--create`:

```bash
php artisan voyager:admin your@email.com --create
```

Команда запросит имя и пароль пользователя.

### Публикация конфигурации и ресурсов

Публикация выполняется по тегам:

```bash
php artisan vendor:publish --tag=voyager-extension-config
php artisan vendor:publish --tag=captcha-config
php artisan vendor:publish --tag=voyager-extension-assets
```

Дополнительно можно опубликовать представления и переводы расширения:

```bash
php artisan vendor:publish --tag=voyager-extension-views
php artisan vendor:publish --tag=voyager-extension-lang
```

Расширение включено по умолчанию. Отключить его можно в `.env`:

```dotenv
VOYAGER_EXTENSION_ENABLED=false
```

### Ручная установка в существующее приложение

Этот раздел для случаев, когда Voyager ставится в уже существующее Laravel-приложение или нужна ручная установка.

Опубликуйте ресурсы пакета:

```bash
php artisan vendor:publish --provider="TCG\Voyager\VoyagerServiceProvider"
```

Выполните миграции:

```bash
php artisan migrate
```

> Если нужно изменить миграции, например использовать другую таблицу пользователей, не запускайте миграции сразу. Скопируйте миграции Voyager в `database/migrations`, внесите изменения, отключите опцию `database.autoload_migrations` и только затем выполните миграции.

Укажите, что модель `User` наследует модель Voyager. Обычно это `app/User.php` или `app/Models/User.php`:

```php
<?php

class User extends \TCG\Voyager\Models\User
{
    // ...
}
```

Добавьте маршруты Voyager в `routes/web.php`:

```php
<?php

Route::group(['prefix' => 'admin'], function () {
    Voyager::routes();
});
```

Заполните базу необходимыми данными:

```bash
php artisan db:seed --class=VoyagerDatabaseSeeder
```

Создайте символическую ссылку хранилища:

```bash
php artisan storage:link
```

Установите BREAD «Галереи»:

```bash
php artisan voyager:gallery:install
```

Завершите установку:

```bash
composer dump-autoload
```

---

## Обновление

### Обновление до 1.7.47

Обновите версию в `composer.json`:

```json
"kupidonkhv/voyager-fork": "^1.7.47"
```

Затем выполните:

```bash
composer update
```

Если ранее использовались отдельные расширения и капча, удалите их:

```bash
composer remove monstrex/voyager-extension kupidonkhv/captcha mews/captcha
```

Опубликуйте обновлённые конфигурации:

```bash
php artisan vendor:publish --tag=voyager-extension-config --force
php artisan vendor:publish --tag=captcha-config --force
php artisan vendor:publish --tag=voyager-extension-assets --force
```

Выполните миграции и установите Галереи:

```bash
php artisan migrate
php artisan voyager:gallery:install
```

Очистите кеши:

```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

Если вы переопределяли представления Voyager, сравните их с новой версией: в 1.7.x изменены BREAD-шаблоны, форматирование дат и подключение ресурсов расширения.

### Обновление с 1.5 на 1.6

При обновлении старых проектов обновите версию в `composer.json`:

```json
"tcg/voyager": "1.6.*"
```

Затем выполните:

```bash
composer update
```

TinyMCE обновлён до версии 6, и многие параметры конфигурации изменились. Если в консоли появляются ошибки и вы настраивали TinyMCE, сверьте параметры с актуальной документацией TinyMCE.

Если возникают проблемы, задайте вопрос в Slack-сообществе Voyager.

---

## Конфигурация

После установки создаётся файл `config/voyager.php`. В нём собраны настройки Voyager.

> Если вы кешируете конфигурацию, после изменений выполняйте `php artisan config:clear`.

Ниже разобраны основные параметры.

### Пользователи

```php
'user' => [
    'add_default_role_on_register' => true,
    'default_role'                 => 'user',
    'default_avatar'               => 'users/default.png',
    'redirect'                     => '/admin',
],
```

- **add_default_role_on_register** — добавлять ли новому пользователю роль по умолчанию.
- **default_role** — роль, которая назначается новым пользователям.
- **default_avatar** — путь к аватару по умолчанию.
- **redirect** — куда перенаправлять пользователя после входа.

Доступ к админ-панели проверяется по разрешению `browse_admin`. Оно создаётся при установке и не настраивается отдельным ключом.

### Контроллеры

```php
'controllers' => [
    'namespace' => 'TCG\\Voyager\\Http\\Controllers',
],
```

Здесь задаётся namespace контроллеров Voyager. Если нужно переопределить поведение, скопируйте контроллеры и укажите расположение своих контроллеров.

> **Переопределение одного контроллера**
> Если нужно заменить только один контроллер, добавьте в метод `register` класса `AppServiceProvider`:
> `$this->app->bind(VoyagerBreadController::class, MyBreadController::class);`

### Модели

```php
'models' => [
    //'namespace' => 'App\\Models\\',
],
```

Namespace моделей, которые создаются из раздела базы данных Voyager. Если параметр не задан, используется namespace приложения по умолчанию. Не забудьте завершающие обратные слеши.

### Хранилище

```php
'storage' => [
    'disk' => 'public',
],
```

По умолчанию Voyager использует локальный диск `public`. Можно указать любой диск из `config/filesystems.php`: S3, Google Cloud Storage или другое хранилище.

### Скрытые файлы медиа-менеджера

```php
'hidden_files' => false,
```

Показывать ли в медиа-менеджере скрытые файлы, например `.gitignore`.

### Параметры галерей

```php
'gallery' => [
    'enabled'                 => env('VOYAGER_GALLERY_ENABLED', true),
    'menu_title'              => env('VOYAGER_GALLERY_MENU_TITLE'),
    'thumb_dimensions'        => env('VOYAGER_GALLERY_THUMB_DIMENSIONS', '400x300'),
    'max_upload_size'         => env('VOYAGER_GALLERY_MAX_UPLOAD_SIZE', 10240),
    'accepted_mime_types'     => [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
    ],
],
```

- **enabled** — включает раздел «Галереи». При `false` команда установки пропускает создание BREAD и пункта меню.
- **menu_title** — собственный заголовок пункта меню. Если не задан, используется перевод для текущей локали.
- **thumb_dimensions** — размер миниатюр по умолчанию в формате `ШИРИНАxВЫСОТА`.
- **max_upload_size** — максимальный размер файла в килобайтах. По умолчанию `10240` КБ (10 МБ).
- **accepted_mime_types** — разрешённые MIME-типы изображений.

Подробности — в разделе [Галереи](#галереи).

### База данных

```php
'database' => [
    'tables' => [
        'hidden' => ['migrations', 'data_rows', 'data_types', 'menu_items', 'password_resets', 'permission_role', 'personal_access_tokens', 'settings'],
    ],
    'autoload_migrations' => true,
],
```

В `tables.hidden` перечислены таблицы, скрытые в разделе базы данных Voyager. Параметр `autoload_migrations` позволяет исключить миграции Voyager при выполнении `php artisan migrate`.

### Параметры мультиязычности

```php
'multilingual' => [
    'enabled' => false,
    'default' => 'en',
    'locales' => [
        'en',
        //'pt',
    ],
],
```

- **enabled** — включает поддержку нескольких языков.
- **default** — язык по умолчанию.
- **locales** — поддерживаемые локали.

Подробнее: [Мультиязычность](#мультиязычность).

### Панель управления

```php
'dashboard' => [
    'navbar_items' => [
        'voyager::generic.profile' => [
            'route'      => 'voyager.profile',
            'classes'    => 'class-full-of-rum',
            'icon_class' => 'voyager-person',
        ],
        'voyager::generic.home' => [
            'route'        => '/',
            'icon_class'   => 'voyager-home',
            'target_blank' => true,
        ],
        'voyager::generic.logout' => [
            'route'      => 'voyager.logout',
            'icon_class' => 'voyager-power',
        ],
    ],
    'widgets' => [

    ],
],
```

- **navbar_items** — пункты выпадающего меню пользователя. Поддерживаются `route`, `icon_class`, `target_blank` и `classes`.
- **widgets** — классы виджетов на панели управления. Примеры находятся в `src/Widgets`.

### Автоматические действия BREAD

```php
'bread' => [
    'add_menu_item' => true,
    'default_menu'  => 'admin',
    'add_permission' => true,
    'default_role'   => 'admin',
],
```

- **add_menu_item** — создавать пункт меню при добавлении BREAD.
- **default_menu** — меню, в которое добавляется пункт.
- **add_permission** — создавать разрешения для нового BREAD.
- **default_role** — роль, которой выдаются эти разрешения.

### Основной цвет

```php
'primary_color' => '#22A7F0',
```

Основной цвет админ-панели. По умолчанию — светло-синий.

### Подсказки разработчику

```php
'show_dev_tips' => true,
```

Показывает подсказки о том, как обращаться к значениям Voyager. Отключите параметром `false`.

### Подключение CSS и JS

```php
'additional_css' => [
    //'css/custom.css',
],
'additional_js' => [
    //'js/custom.js',
],
```

Файлы подключаются в админ-панели после ресурсов Voyager, поэтому могут переопределять стили и поведение. Путь передаётся в функцию Laravel `asset()`.

Подробнее: [Дополнительные CSS и JS](#дополнительные-css-и-js).

### Google Maps

```php
'googlemaps' => [
    'key'    => env('GOOGLE_MAPS_KEY', ''),
    'center' => [
        'lat' => env('GOOGLE_MAPS_DEFAULT_CENTER_LAT', '32.715738'),
        'lng' => env('GOOGLE_MAPS_DEFAULT_CENTER_LNG', '-117.161084'),
    ],
    'zoom' => env('GOOGLE_MAPS_DEFAULT_ZOOM', 11),
],
```

Настройки для поля **Coordinates**. Здесь задаются ключ Google Maps, центр карты и масштаб. Значения можно хранить в `.env`.

### Кеш настроек

```php
'settings' => [
    'cache' => false,
],
```

Включает кеширование настроек между запросами средствами Laravel.

### Compass в production

```php
'compass_in_production' => false,
```

Включает Compass в окружении, отличном от `local`, например в production.

### Параметры медиа-менеджера

```php
'media' => [
    'allowed_mimetypes' => [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/bmp',
        'video/mp4',
    ],
    'path'                => '/',
    'show_folders'        => true,
    'allow_upload'        => true,
    'allow_move'          => true,
    'allow_delete'        => true,
    'allow_create_folder' => true,
    'allow_rename'        => true,
],
```

- **allowed_mimetypes** — разрешённые типы файлов. Чтобы разрешить все типы, используйте `'*'`.
- **path** — стартовый путь относительно файловой системы.
- **show_folders** — показывать вложенные папки.
- **allow_upload**, **allow_move**, **allow_delete**, **allow_create_folder**, **allow_rename** — разрешения на соответствующие операции.

В этом же разделе можно задать `watermark` и `thumbnails`. Их формат описан в разделе [Media Picker](#media-picker).

---

## Галереи

Раздел «Галереи» появился в версии 1.7.47. Он позволяет вести фотогалереи в админке и выводить их на сайте. Изображения хранятся через `spatie/laravel-medialibrary`, поэтому пакет добавляет зависимость `spatie/laravel-medialibrary` и требует расширение `ext-gd`.

### Установка и повторная установка

При выполнении `php artisan voyager:install` Галереи устанавливаются автоматически. Отдельная команда нужна для повторной установки, обновления или включения раздела в существующем проекте:

```bash
php artisan voyager:gallery:install
```

Команда:

- при необходимости выполняет миграции Media Library;
- создаёт BREAD `galleries`, если его ещё нет;
- создаёт поля BREAD и разрешения `browse_galleries`, `read_galleries`, `edit_galleries`, `add_galleries`, `delete_galleries`;
- выдаёт эти разрешения роли `admin`;
- создаёт пункт меню в меню `admin`, если оно существует.

> Если меню `admin` ещё не создано, команда предупредит об этом. Создайте меню в конструкторе меню и запустите команду снова.

Если раздел отключён в конфигурации, команда завершится с сообщением, что Галереи отключены:

```php
'gallery' => [
    'enabled' => false,
],
```

### Поля галереи

В BREAD «Галереи» доступны поля:

- **title** — название галереи. Обязательное поле. Именно по нему галерея находится при вставке в контент.
- **dimensions** — размер миниатюр. Список готовых вариантов: `400x300`, `600x450`, `800x600`, `1024x768`, `300x400`, `450x600`, `600x800`, `768x1024`, `300x300`, `500x500`, `800x800`, `600x400`, `400x600`, `900x600`, `600x900`.
- **description** — описание галереи.
- **sort_order** — порядок сортировки самих галерей.

Размер миниатюр можно задать отдельно для каждой галереи. Если поле не заполнено, используется `voyager.gallery.thumb_dimensions`.

### Работа с фотографиями

Откройте галерею на редактирование. На странице есть область загрузки:

- перетащите изображения в область или нажмите на неё, чтобы выбрать файлы;
- после загрузки можно добавить подпись к каждому фото;
- фотографии можно перетаскивать для изменения порядка;
- фотографию можно удалить.

> Сначала сохраните галерею, затем загружайте фотографии на странице редактирования. Для новой записи область загрузки показывает подсказку сохранить галерею.

Ограничения загрузки:

- разрешённые MIME-типы задаются в `voyager.gallery.accepted_mime_types`;
- максимальный размер файла задаётся в `voyager.gallery.max_upload_size` в килобайтах;
- подпись к фотографии ограничена 500 символами.

### Конфигурация

```php
'gallery' => [
    'enabled'                 => env('VOYAGER_GALLERY_ENABLED', true),
    'menu_title'              => env('VOYAGER_GALLERY_MENU_TITLE'),
    'thumb_dimensions'        => env('VOYAGER_GALLERY_THUMB_DIMENSIONS', '400x300'),
    'max_upload_size'         => env('VOYAGER_GALLERY_MAX_UPLOAD_SIZE', 10240),
    'accepted_mime_types'     => [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
    ],
],
```

Переменные окружения:

```dotenv
VOYAGER_GALLERY_ENABLED=true
VOYAGER_GALLERY_MENU_TITLE="Галереи"
VOYAGER_GALLERY_THUMB_DIMENSIONS=400x300
VOYAGER_GALLERY_MAX_UPLOAD_SIZE=10240
```

- **enabled** — включает или отключает раздел.
- **menu_title** — заголовок пункта меню. Если не задан, используется перевод.
- **thumb_dimensions** — размер миниатюр по умолчанию.
- **max_upload_size** — максимальный размер файла в килобайтах.
- **accepted_mime_types** — список разрешённых MIME-типов.

### Язык пункта меню

Пункт меню создаётся на языке, который выбран в `APP_LOCALE`. Для `en` и `ru` в пакете есть готовые переводы. Для остальных локалей используется английский текст. Заголовок можно переопределить через `gallery.menu_title` или `VOYAGER_GALLERY_MENU_TITLE`.

### Вывод галереи в контенте

Вставьте в контент токен с названием галереи:

```text
%gallery="Название галереи"%
```

Чтобы выбрать шаблон вывода, укажите `form`:

```text
%gallery="Название галереи" form="rounded"%
```

Доступные шаблоны:

- `default` — сетка миниатюр с подписями;
- `photo-carousel` — горизонтальная карусель;
- `rounded` — сетка с круглыми миниатюрами.

Токен обрабатывается макросом `Str::parseGalleries()`. Вызовите его там, где выводится контент:

```php
{!! \Illuminate\Support\Str::parseGalleries($page->body) !!}
```

Если галерея не найдена или в ней нет фотографий, в HTML выводится комментарий вида `<!-- Gallery "..." not found or empty -->`. Это безопасно и не ломает страницу.

### Переопределение шаблонов

Шаблоны лежат в `resources/views/gallery`. Их можно переопределить в приложении:

- `resources/views/gallery/default.blade.php`
- `resources/views/gallery/photo-carousel.blade.php`
- `resources/views/gallery/rounded.blade.php`

Или в каталоге переопределений Voyager:

- `resources/views/vendor/voyager/gallery/default.blade.php`
- `resources/views/vendor/voyager/gallery/photo-carousel.blade.php`
- `resources/views/vendor/voyager/gallery/rounded.blade.php`

В шаблон передаётся переменная `$gallery` — модель `TCG\Voyager\Models\Gallery`.

### Работа с моделью

```php
use TCG\Voyager\Models\Gallery;

$gallery = Gallery::where('title', 'Название галереи')->first();

foreach ($gallery->images as $media) {
    echo $media->getUrl();          // оригинал
    echo $media->getUrl('thumb');   // миниатюра
    echo $media->getCustomProperty('description', '');
}
```

Связь `images()` возвращает медиафайлы коллекции `images`, отсортированные по `order_column`.

---

## Капча

Начиная с 1.7.45 в пакет встроена Капча на основе Mews Captcha. Она умеет создавать изображения, работать через API и проверять значения правилами валидации Laravel.

### Публикация конфигурации

```bash
php artisan vendor:publish --tag=captcha-config
```

После публикации появится `config/captcha.php`.

### Маршруты

- `GET /captcha/{config}` — изображение капчи. По умолчанию `/captcha/default`.
- `GET /captcha/api/{config}` — JSON с ключом и изображением в data URI.

Маршруты регистрируются с middleware `web`, если приложение использует веб-группу. При отключённой капче маршруты не создаются.

### Конфигурации

Встроенные конфигурации: `default`, `math`, `flat`, `mini`, `inverse`.

```php
'default' => [
    'length' => 9,
    'width' => 120,
    'height' => 36,
    'quality' => 90,
    'math' => false,
    'expire' => 60,
    'encrypt' => false,
],

'math' => [
    'length' => 9,
    'width' => 120,
    'height' => 36,
    'quality' => 90,
    'math' => true,
],
```

Основные параметры:

- **length** — количество символов;
- **width**, **height** — размеры изображения;
- **quality** — качество JPEG;
- **math** — математическая капча;
- **expire** — время жизни кода в секундах;
- **encrypt** — шифрование ключа;
- **sensitive** — учитывать регистр;
- **lines**, **bgImage**, **bgColor**, **fontColors**, **contrast**, **angle**, **sharpen**, **blur**, **invert** — визуальные настройки.

Отключить капчу можно в `.env`:

```dotenv
CAPTCHA_DISABLE=true
```

При отключении правила валидации всегда считаются пройденными, а маршруты не регистрируются.

### Helper-функции

```php
captcha();                 // изображение (Response)
captcha('flat');           // изображение с конфигурацией flat
captcha_src();             // URL изображения
captcha_src('mini');       // URL для конфигурации mini
captcha_img();             // HTML-тег img
captcha_img('flat', ['class' => 'form-control', 'alt' => 'Капча']);
captcha_check($value);     // проверка значения из сессии
captcha_api_check($value, $key, 'default');
```

В Blade:

```blade
{!! captcha_img() !!}
```

### Проверка формы

```php
$request->validate([
    'captcha' => 'required|captcha',
]);
```

Если нужно вывести сообщение об ошибке, используйте стандартные средства Laravel:

```blade
@error('captcha')
    <span class="text-danger">{{ $message }}</span>
@enderror
```

### API-капча

Запросите `/captcha/api/default` и получите JSON:

```json
{
    "sensitive": false,
    "key": "...",
    "img": "data:image/jpeg;base64,..."
}
```

Покажите `img` пользователю, сохраните `key` и проверьте ответ правилом `captcha_api`:

```php
$request->validate([
    'captcha' => 'required|captcha_api:'.$key.',default',
]);
```

Или через helper:

```php
if (!captcha_api_check($request->input('captcha'), $key, 'default')) {
    // неверная капча
}
```

> `captcha_api` принимает первым параметром ключ, вторым — имя конфигурации. Если конфигурация не указана, используется `default`.

### Ограничения

- Код хранится в кеше и живёт `expire` секунд (по умолчанию 60).
- После успешной проверки значение удаляется из кеша и сессии.
- Для загрузки изображений и шрифтов нужны права на чтение файлов пакета.

---

## Встроенное расширение

Начиная с 1.7.45 расширение `kupidonkhv/voyager-extension` входит в пакет. Оно добавляет в BREAD расширенные шаблоны, клонирование записей, дополнительные поля и генераторы Media Library.

### Конфигурация

Опубликуйте конфигурацию:

```bash
php artisan vendor:publish --tag=voyager-extension-config
```

Основные параметры `config/voyager-extension.php`:

```php
'enabled' => env('VOYAGER_EXTENSION_ENABLED', true),

'legacy_browse_bread'   => false,
'legacy_edit_add_bread' => false,
'legacy_bread_list'     => false,

'sticky_action_panel' => [
    'enabled'  => true,
    'autohide' => false,
],

'clone_record' => [
    'enabled'       => true,
    'reset_types'   => ['image', 'multiple_images', 'file'],
    'suffix_fields' => ['title', 'name', 'slug', 'key'],
],

'use_media_path_generator' => true,
'use_media_url_generator'  => true,
'slug_filenames'           => true,
'assets_path_prefix'       => 'admin',
```

- **enabled** — включает расширение. Отключается через `VOYAGER_EXTENSION_ENABLED=false`.
- **legacy_browse_bread**, **legacy_edit_add_bread**, **legacy_bread_list** — возвращают оригинальные представления Voyager вместо расширенных.
- **sticky_action_panel.enabled** — включает липкую панель действий. **autohide** скрывает её, пока курсор не наведён.
- **clone_record.enabled** — включает кнопку клонирования.
- **clone_record.reset_types** — типы полей, значения которых очищаются при клонировании.
- **clone_record.suffix_fields** — поля, к значению которых добавляется суффикс `(clone)`.
- **use_media_path_generator**, **use_media_url_generator** — включают генераторы путей и URL Media Library.
- **slug_filenames** — преобразует имена файлов в slug перед сохранением.
- **assets_path_prefix** — префикс пути к ресурсам расширения.

### Поля BREAD

| Код поля | Название | Назначение |
| --- | --- | --- |
| `adv_image` | VE Image | Одно изображение через Media Library с атрибутами `title` и `alt` |
| `adv_media_files` | VE Media Files | Несколько медиафайлов с выбором, удалением и сортировкой |
| `adv_inline_set` | VE Inline Fields Set | Набор вложенных полей, редактируемый прямо в форме |
| `adv_select_dropdown_tree` | VE Select Dropdown Tree | Выпадающий список с древовидной иерархией |
| `adv_fields_group` | VE Fields Group | Группа полей |
| `adv_json` | VE JSON Fields | Редактирование JSON-структуры |
| `adv_related` | VE Related Model | Связанная модель |
| `adv_page_layout` | VE Page Layout | Конструктор секций страницы. Требует пакет `monstrex/voyager-site` |

Выберите нужный код в поле **Form Type** при настройке BREAD. Для расширенных полей можно использовать те же JSON-опции, что и для стандартных formfields.

#### Изображения и медиафайлы

`adv_image` сохраняет изображение в Media Library. В модели получите файл по имени поля:

```php
$image = $post->getFirstMedia('image');

$imageUrl   = $image->getFullUrl();
$imageTitle = $image->getCustomProperty('title');
$imageAlt   = $image->getCustomProperty('alt');
```

Для `adv_media_files` используйте коллекцию Media Library и перебирайте файлы:

```php
foreach ($post->getMedia('files') as $media) {
    echo $media->getUrl();
}
```

> Имена коллекций зависят от настроек поля. Проверьте их в BREAD и в форме редактирования.

### Клонирование записей

При включённом `clone_record.enabled` в списке записей появляется кнопка «Клонировать». Клон создаётся через маршрут `POST {slug}/{id}/clone`. Поведение настраивается:

```php
'clone_record' => [
    'enabled'       => true,
    'reset_types'   => ['image', 'multiple_images', 'file'],
    'suffix_fields' => ['title', 'name', 'slug', 'key'],
],
```

- поля из `reset_types` очищаются;
- к полям из `suffix_fields` добавляется `(clone)`;
- остальные значения копируются.

Для клонирования нужно разрешение `add` на соответствующий BREAD.

### Генераторы Media Library

Расширение подменяет генераторы путей и URL Media Library, если включены соответствующие параметры. Это позволяет хранить файлы в предсказуемой структуре и формировать URL с префиксом ресурсов админки.

### Конструктор меню

Расширенный конструктор меню добавляет обновление пунктов без перезагрузки страницы, работу с цветом, иконками и параметрами маршрутов. Используются маршруты:

- `voyager.menu-items.ext-record-update` — обновление записи пункта меню;
- `voyager.ext-records-get` — получение списка записей.

### Helper-функции расширения

```php
str_trans('{{en}}English{{ru}}Русский'); // строка на текущей локали
voyager_extension_asset('css/app.css');   // URL ресурса расширения
flat_to_tree($items);                     // плоский массив в дерево
json_response_with_success($status, $message, $data);
json_response_with_error($status, $error);
```

`str_trans()` поддерживает синтаксис `{{locale}}текст` и `[[locale]]текст`. Если перевод для текущей локали не найден, возвращается первый вариант.

---

## BREAD

BREAD расшифровывается как **Browse, Read, Edit, Add, Delete**. Это система управления записями таблицы базы данных.

### Введение

При добавлении или редактировании BREAD для таблицы сначала отображается блок сведений:

- отображаемые имена в единственном и множественном числе;
- slug;
- иконка;
- `Model` и namespace контроллера;
- имя Policy;
- создание разрешений для BREAD.

Ниже находится список полей таблицы. Для каждого поля можно выбрать, где оно отображается:

- **BROWSE** — поле показывается в списке записей;
- **READ** — поле показывается на странице просмотра записи;
- **EDIT** — поле доступно для редактирования;
- **ADD** — поле показывается при создании записи;
- **DELETE** — не влияет на удаление, флаг можно включать или выключать.

Для каждого поля выбирается тип формы: текстовое поле, текстовая область, чекбокс, изображение и другие. Дополнительные параметры задаются в JSON в блоке **Optional Details**.

### Валидация

В **Optional Details** можно указать правила валидации. Пример: обязательное поле с максимальной длиной 12 символов.

```php
{
    "validation": {
        "rule": "required|max:12"
    }
}
```

Свои сообщения об ошибках:

```php
{
    "validation": {
        "rule": "required|max:12",
        "messages": {
            "required": "Поле :attribute обязательно.",
            "max": "Максимальная длина поля :attribute — :max."
        }
    }
}
```

Несколько правил:

```php
{
    "validation": {
        "rule": [
            "required",
            "max:12"
        ]
    }
}
```

Отдельные правила для редактирования и добавления:

```text
{
    "validation": {
        "rule": "min:3",
        "edit": {
            "rule": "nullable"
        },
        "add": {
            "rule": "required"
        }
    }
}
```

Полный список правил — в [документации Laravel](https://laravel.com/docs/validation#available-validation-rules).

### Тегирование

Тегирование позволяет добавлять новые элементы в связь Belongs-To-Many прямо при редактировании или создании записи.

Включите **Tagging** в настройках связи. Затем введите свободный текст в поле выбора и нажмите Enter, чтобы сохранить новую связь.

> Сохраняется только `display-column`. Убедитесь, что остальные поля таблицы допускают `NULL` или имеют значение по умолчанию.

### Сортировка записей BREAD

Можно задать порядок по умолчанию для списка записей и менять порядок перетаскиванием. Для этого измените настройки BREAD:

- **Order column** — поле таблицы, в котором хранится целочисленный порядок;
- **Order display column** — поле, которое показывается в списке перетаскивания;
- **Order direction** — направление сортировки.

После этого на странице списка появится кнопка **Order**. Она открывает страницу изменения порядка записей.

### Ограничение выборки через Scope

Чтобы фильтровать список записей BREAD, создайте [локальный scope](https://laravel.com/docs/eloquent#local-scopes) в модели. Например, чтобы показывать только посты текущего пользователя:

```php
<?php
public function scopeCurrentUser($query)
{
    return $query->where('author_id', Auth::user()->id);
}
```

Затем откройте настройки BREAD для `posts`, найдите поле **Scope** и выберите `currentUser`. После сохранения вы увидите только свои записи.

---

## Связи

### Создание связи

Связи между таблицами создаются в конструкторе BREAD. Внизу страницы есть кнопка **Create Relationship**.

> Если BREAD для таблицы ещё не создан, сначала создайте его, затем вернитесь к добавлению связи. Иначе появится уведомление о том, что BREAD отсутствует.

После нажатия **Create Relationship** откроется окно, в котором нужно:

1. выбрать тип связи;
2. выбрать таблицу, на которую ссылается связь;
3. указать namespace этой таблицы;
4. выбрать поле, которое связывает таблицы;
5. при необходимости выбрать колонки для выпадающего списка или мультиселекта.

Voyager поддерживает связи `belongsTo`, `belongsToMany`, `hasOne` и `hasMany`.

### Дополнительные параметры

Для `belongsToMany` после сохранения связи можно указать параметры в поле details:

```php
{
    "foreign_pivot_key": "user_id",
    "related_pivot_key": "role_id",
    "parent_key": "id"
}
```

### Сортировка связей

Результаты связи сортируются через объект `sort`:

```php
{
    "sort": {
        "field": "my_field",
        "direction": "asc"
    }
}
```

Или по убыванию:

```php
{
    "sort": {
        "field": "my_field",
        "direction": "desc"
    }
}
```

### Ограничение вариантов связи

Показываемые варианты можно отфильтровать локальным scope в связанной модели. Например, чтобы показывать только активные записи:

```php
public function scopeActive($query)
{
    return $query->where('active', 1);
}
```

Добавьте в параметры связи:

```php
{
    "scope": "active",
}
```

Значение — имя метода без префикса `scope`. Для `scopeActive()` это `active`, для `scopeSomeUsers()` — `someUsers`.

> В связи BelongsTo поле внешнего ключа определяет, можно ли сохранить значение при добавлении или редактировании, и к нему применяются правила валидации. Для остальных действий, например отображения в списке и редактирования, используется поле связи.

---

## Formfields

Formfields — основа BREAD-системы Voyager. Каждый formfield представляет поле таблицы базы данных и один элемент ввода или вывода в BREAD. Поведение настраивается JSON-опциями.

Все formfields поддерживают несколько общих параметров.

### Описание

Любой тип может содержать описание, которое помогает понять назначение поля. Оно задаётся в **Optional Details**:

```php
{
    "description": "Подсказка для этого поля."
}
```

### Параметры отображения

Ширина поля и пользовательский ID задаются через ключ `display`:

```php
{
    "display": {
        "width": "3",
        "id": "custom_id"
    }
}
```

Ширина считается по сетке из 12 колонок. Значение `3` занимает 25% ширины.

Ключ **id** задаёт обёртку вокруг элемента:

```markup
<div id="custom_id">
    <!-- Your field element -->
</div>
```

### Значение по умолчанию

Большинство formfields поддерживают значение по умолчанию:

```php
{
    "default" : "Текст по умолчанию"
}
```

### Значения NULL

Иногда пустую строку нужно сохранить в базу как `null`. Укажите:

```php
{
    "null": ""
}
```

Тогда пустая строка будет записана как `null`. Если нужно сохранять и пустую строку, и `null`, выберите строку-заменитель:

```php
{
    "null": "Nothing"
}
```

Теперь ввод `Nothing` сохранится как `null`, а пустая строка останется пустой строкой.

### Генерация slug

Чтобы автоматически создавать slug из другого поля, например из `title`:

```php
{
    "slugify": {
        "origin": "title",
        "forceUpdate": true
    }
}
```

Slug создаётся из значения поля `title`. Если slug уже существует, он обновится только при `forceUpdate: true`. По умолчанию обновление отключено.

### Пользовательское представление

Для formfield можно указать собственный Blade-шаблон:

```text
{
    "view": "my_view"
}
```

Шаблон `my_view` загружается из `resources/views` вместо стандартного представления formfield.

В шаблон передаются:

- `$view` — одно из значений `browse`, `read`, `edit`, `add`, `order`;
- `$content` — содержимое поля;
- `$dataType` — модель DataType;
- `$dataTypeContent` — экземпляр модели записи;
- `$row` — DataRow;
- `$options` — детали DataRow.

Можно задать отдельные представления для действий:

```text
{
    "view_browse": "my_browse_view",
    "view_read": "my_read_view",
    "view_add": "my_add_view",
    "view_edit": "my_edit_view",
    "view_order": "my_order_view"
}
```

В них передаются те же переменные.

> **Разработка собственного formfield?**
> Если вы создаёте свой formfield и хотите переопределить представления, объедините `view` с `$options` в методе `createContent()`.

---

## Checkbox, множественные чекбоксы и радиокнопки

### Checkbox

```php
{
    "on" : "Включено",
    "off" : "Выключено",
    "checked" : true
}
```

Checkbox отображается как переключатель. Ключ `on` содержит значение во включённом состоянии, `off` — в выключенном. Если `checked` равно `true`, переключатель включён по умолчанию.

### Multiple Checkbox

```php
{
    "checked" : true,
    "options": {
        "checkbox1": "Текст чекбокса 1",
        "checkbox2": "Текст чекбокса 2"
    }
}
```

Количество чекбоксов не ограничено.

### Radio Button

```php
{
    "default" : "radio1",
    "options" : {
        "radio1": "Текст радиокнопки 1",
        "radio2": "Текст радиокнопки 2"
    }
}
```

Радиокнопки работают так же, как dropdown. В `default` задаётся значение по умолчанию, в `options` слева указывается значение, справа — отображаемый текст.

---

## Поле Coordinates

Поле Coordinates позволяет выбрать координаты на карте Google Maps.

### Optional Details

#### showLatLngInput / showAutocompleteInput

Установите `false`, чтобы скрыть соответствующее поле ввода. По умолчанию оба включены.

```json
{
  "showAutocompleteInput": false,
  "showLatLngInput": false
}
```

#### onChange

```json
{
  "onChange": "myFunction"
}
```

Задаёт callback, который вызывается после изменения любого поля ввода. Callback можно использовать, например, чтобы заполнить другое поле адресом из Autocomplete.

Callback вызывается с задержкой 300 мс. Первый параметр — `eventType` (`mapDragged`, `latLngChanged` или `placeChanged`). Второй — объект `data` с полями `lat`, `lng` и `place`.

```javascript
function myFunction(eventType, data) {
  console.log('eventType', eventType);
  console.log('data.lat', data.lat);
  console.log('data.lng', data.lng);
  console.log('data.place', data.place);
}
```

---

## Дата и время

```php
{
    "format" : "%Y-%m-%d"
}
```

Поле даты и времени позволяет ввести дату. В `format` задаётся формат вывода даты в списке и на странице просмотра. В форке форматирование совместимо с Carbon 3 и выполняется через `Voyager::formatDate`.

---

## Dropdown

```php
{
    "default" : "option1",
    "options" : {
        "option1": "Текст варианта 1",
        "option2": "Текст варианта 2"
    }
}
```

В `default` задаётся значение по умолчанию, если у записи нет значения. В `options` слева указывается значение, справа — отображаемый текст.

---

## Изображения

```php
{
    "resize": {
        "width": "1000",
        "height": null
    },
    "quality" : "70%",
    "upsize" : true,
    "thumbnails": [
        {
            "name": "medium",
            "scale": "50%"
        },
        {
            "name": "small",
            "scale": "25%"
        },
        {
            "name": "cropped",
            "crop": {
                "width": "300",
                "height": "250"
            }
        }
    ]
}
```

У поля изображения много параметров. Если ничего не указывать, изображение всё равно загрузится. Дополнительные параметры позволяют изменить размер, качество и создать миниатюры.

- **resize** — размеры изображения. Если `height` или `width` равно `null`, пропорции сохраняются по заданной стороне. В примере ширина `1000` пикселей, высота рассчитывается автоматически.
- **quality** — качество сжатия в процентах. Обычно значения от 70 до 100% почти не ухудшают вид, но заметно уменьшают размер файла.
- **upsize** — работает только вместе с `resize`. Если изображение меньше заданного размера, по умолчанию оно не увеличивается. При `upsize: true` изображение увеличивается до указанных размеров.
- **thumbnails** — массив объектов миниатюр. Каждый объект содержит `name` и `scale`. `name` добавляется к имени файла: для `ABC.jpg` миниатюра `medium` будет называться `ABC-medium.jpg`. `scale` — процент от размеров `resize`, если они заданы. Вместо `scale` можно использовать `crop`.

---

## Number

```php
{
    "step" : 0.1,
    "min" : 0,
    "max" : 10,
    "default": 1,
}
```

Стандартные атрибуты числового поля. Если `step` не задан, используется значение `any`.

---

## TinyMCE

Чтобы настроить TinyMCE в Voyager, подключите [дополнительный JS-файл](#дополнительные-css-и-js) в конфигурации.

В нём определите функцию:

```javascript
function tinymce_init_callback(editor)
{
    //...
}
```

Если нужно изменить редактор **до** инициализации:

```javascript
function tinymce_setup_callback(editor)
{
    //...
}
```

Дополнительные параметры инициализации TinyMCE можно объединить через JSON в деталях BREAD:

```php
{
    "tinymceOptions" : {
        "name": "value"
    }
}
```

Чтобы использовать TinyMCE вне стандартного шаблона `rich_text_box`, инициализируйте его так:

```javascript
tinymce.init(window.voyagerTinyMCE.getConfig());
```

Все переменные, функции и параметры конфигурации описаны в [документации TinyMCE](https://www.tinymce.com/docs/api/tinymce/tinymce.editor/).


---

## Media Picker

Media Picker позволяет загружать, удалять и выбирать файлы прямо из медиа-менеджера. Поведение настраивается опциями:

```php
{
    "max": 10,
    "min": 0,
    "expanded": true,
    "show_folders": true,
    "show_toolbar": true,
    "allow_upload": true,
    "allow_move": true,
    "allow_delete": true,
    "allow_create_folder": true,
    "allow_rename": true,
    "allow_crop": true,
    "allowed": [],
    "hide_thumbnails": false,
    "quality": 90,
    "watermark": {
        "source": "...",
        "position": "top-left",
        "x": 0,
        "y": 0
    }
}
```

| Параметр | Описание | Тип | По умолчанию |
| --- | --- | --- | --- |
| `base_path` | Стартовый путь относительно файловой системы | String | `/bread-slug/` |
| `rename` | Переименовать загруженные файлы по строке или выражению | String | Исходное имя |
| `delete_files` | Удалять файлы при удалении записи BREAD. Используйте осторожно | bool | `false` |
| `show_as_images` | Показывать сохранённые данные как изображения в списке | bool | `false` |
| `min` | Минимальное количество выбираемых файлов | int | `0` |
| `max` | Максимальное количество выбираемых файлов. `0` — без ограничений | int | `0` |
| `expanded` | Разворачивать медиа-пикер по умолчанию | bool | `true` |
| `show_folders` | Показывать вложенные папки | bool | `true` |
| `show_toolbar` | Показывать всю панель инструментов | bool | `false` |
| `allow_upload` | Разрешить загрузку файлов | bool | `true` |
| `allow_move` | Разрешить перемещение файлов и папок | bool | `true` |
| `allow_delete` | Разрешить удаление файлов | bool | `true` |
| `allow_create_folder` | Разрешить создание папок | bool | `true` |
| `allow_rename` | Разрешить переименование файлов. Используйте осторожно | bool | `true` |
| `allow_crop` | Разрешить обрезку изображений | bool | `true` |
| `hide_thumbnails` | Скрывать известные миниатюры и показывать их дочерними элементами исходного изображения | bool | `true` |
| `quality` | Качество загружаемых изображений и миниатюр | int | `90` |
| `allowed` | Разрешённые типы файлов. Пустой объект означает все типы. Файлы других типов не отображаются | Object | `[]` |

### Разрешённые типы

Чтобы разрешить только определённые типы, передайте MIME-типы в `allowed`:

```text
["image", "audio", "video"]
```

или

```text
["image/jpeg", "image/png", "image/bmp"]
```

### Выражения

В `base_path` и `rename` можно использовать подстановки:

- `{pk}` — первичный ключ записи (только для `base_path`);
- `{uid}` — ID текущего авторизованного пользователя;
- `{date:format}` — текущая дата в формате `format`, например `{date:d.m.Y}`;
- `{random:10}` — случайная строка из N символов.

Пример `base_path`:

```text
{
    "base_path": "/my-bread/{pk}/{date:Y}/{date:m}/"
}
```

### Водяной знак

Водяной знак добавляется к загруженным изображениям. Источник задаётся относительно диска хранения Voyager:

```php
{
    "watermark": {
        "source": "watermark.png",
        "position": "bottom-left",
        "x": 0,
        "y": 0,
        "size": 15
    }
}
```

- **position** — положение водяного знака: `top-left` (по умолчанию), `top`, `top-right`, `left`, `center`, `right`, `bottom-left`, `bottom`, `bottom-right`;
- **x** — смещение по оси X, по умолчанию `0`;
- **y** — смещение по оси Y, по умолчанию `0`;
- **size** — размер в процентах от изображения, по умолчанию `15`.

### Миниатюры

Для каждого загруженного изображения можно создавать миниатюры. Доступны три типа.

#### Fit

Fit совмещает обрезку и изменение размера, чтобы получить миниатюру нужных размеров. Обязателен `width`, необязательны `height` и `position`.

```text
{
    "thumbnails": [
        {
            "type": "fit",
            "name": "fit-500",
            "width": 500,
            "height": 500,
            "position": "center"
        }
    ]
}
```

#### Crop

Crop обрезает изображение по заданным размерам и позиции. Обязательны `width` и `height`, необязательны `x` и `y`.

```text
{
    "thumbnails": [
        {
            "type": "crop",
            "name": "crop-500-500",
            "width": 500,
            "height": 500,
            "x": 50,
            "y": 50,
        }
    ]
}
```

#### Resize

Resize изменяет размер изображения. Нужно передать `width` и/или `height`.

```text
{
    "thumbnails": [
        {
            "type": "resize",
            "name": "resize-500",
            "width": 500,
            "upsize": true,
        },
        {
            "type": "resize",
            "name": "resize-500-500",
            "width": 500,
            "height": 500
        },
        {
            "type": "resize",
            "name": "resize-500",
            "width": null,
            "height": 500
        }
    ]
}
```

Водяной знак можно добавить и к миниатюрам. Задайте параметры `watermark` у родительского элемента и установите `watermark: true` для нужных миниатюр.

---

## Маршрутизация

После установки Voyager в `routes/web.php` появляются маршруты:

```php
Route::group(['prefix' => 'admin'], function () {
    Voyager::routes();
});
```

Здесь рендерятся маршруты Voyager. Префикс `admin` можно изменить или задать другие параметры группы, например `middleware` или `domain`.

При создании BREAD и указании slug запись доступна по адресу:

```text
URL/admin/slug-name
```

Пример: для таблицы `products` со slug `products` адрес будет:

```text
URL/admin/products
```

> Если включён параметр `bread.add_menu_item` (по умолчанию `true`), пункт меню для нового BREAD создаётся автоматически. Если ссылки нет, добавьте её вручную через [конструктор меню](#меню-и-конструктор-меню).

---

## Медиа-менеджер

Voyager включает полноценный медиа-менеджер: загрузка, переименование и удаление файлов, создание папок, перемещение файлов и папок. В нём доступны все основные операции файлового менеджера.

Файлы можно перетаскивать на кнопку **Upload**, чтобы загрузить несколько файлов сразу.

Медиа-менеджер умеет создавать миниатюры и добавлять водяные знаки через конфигурацию. Формат параметров описан в разделе [Media Picker](#media-picker).

> **Размер загружаемых файлов**
> Если при загрузке больших файлов возникает ошибка, проверьте настройки PHP `upload_max_filesize` и `post_max_size`.

---

## Меню и конструктор меню

С Voyager можно создавать меню для приложения. Сама админ-панель использует конструктор меню для навигации слева.

Текущие меню доступны в **Tools → Menu Builder**. Меню можно добавлять, редактировать и удалять. Можно создать отдельные меню для шапки, сайдбара и подвала сайта.

Чтобы добавить пункты, нажмите кнопку конструктора у нужного меню. Откроется страница добавления, редактирования и удаления пунктов.

После настройки меню его можно вывести в приложении. Например, для меню `main`:

```php
menu('main');
```

Будет выведен нестилизованный список `<ul>`. Если используется Bootstrap, передайте второй аргумент:

```php
menu('main', 'bootstrap');
```

Можно указать собственный Blade-шаблон. Например, файл `resources/views/my_menu.blade.php`:

```markup
<ul>
    @foreach($items as $menu_item)
        <li><a href="{{ $menu_item->link() }}">{{ $menu_item->title }}</a></li>
    @endforeach
</ul>
```

Вызов:

```php
menu('main', 'my_menu');
```

### Меню как JSON

Если меню нужно не вывести, а получить массив, передайте `_json` вторым параметром:

```php
menu('main', '_json')
```

Метод вернёт коллекцию пунктов меню.

---

## Менеджер базы данных

Voyager предоставляет инструменты для просмотра, создания, редактирования и удаления таблиц базы данных. Для любой таблицы можно добавить BREAD-функциональность.

В админ-панели откройте **Tools → Database**. Вы увидите все таблицы базы данных. Кнопка **Create a New Table** создаёт новую таблицу. Новые таблицы используют charset, заданный в [соединении с базой данных по умолчанию](https://laravel.com/docs/database#configuration).

Если нажать на имя таблицы, откроется текущая схема. Кнопки **View**, **Edit** и **Delete** выполняют соответствующие действия с таблицей.

Для любой таблицы можно добавить BREAD. Если BREAD уже создан, его можно изменить или удалить.

> В форке интроспекция и изменение схемы работают без Doctrine DBAL, включая SQLite.

---

## Настройки

Раздел **Settings** позволяет хранить настройки сайта. Например, изображение логотипа или текстовый заголовок главной страницы.

Настройки группируются. Если создать настройку в группе `site` с ключом `title`, её можно использовать в любом месте приложения:

```php
<?php
echo setting('site.title');
```

Или в Blade:

```text
{{ setting('site.title') }}
```

Так можно добавлять любые настройки в Voyager и использовать их в приложении.

---

## Compass

Compass — быстрый раздел со ссылками, шрифтами, запуском команд и просмотром логов.

- На первой вкладке собраны ссылки на ресурсы и все шрифты, доступные в Voyager.
- На вкладке **Command** можно выполнять Artisan-команды прямо из Voyager.
- На вкладке **Logs** отображаются логи приложения.

---

## Роли и разрешения

Voyager включает роли и разрешения. У каждого пользователя есть роль, а у роли — набор разрешений.

В админ-панели роли можно добавлять, редактировать и удалять. При редактировании роли задаются разрешения BREAD.

Начиная с версии 1.0 авторизация Voyager следует [подходу Laravel](https://laravel.com/docs/authorization#authorizing-actions-using-policies). Проверка прав выполняется так:

```php
// через объект пользователя
$canViewPost = $user->can('read', $post);
$canViewPost = Auth::user()->can('read', $post);

// через контроллер
$canViewPost = $this->authorize('read', $post);
```

Стандартные разрешения:

- `browse_admin` — доступ к админ-панели Voyager;
- `browse_database` — доступ к разделу базы данных;
- `browse_bread` — доступ к разделу BREAD;
- `browse_media` — доступ к разделу медиа;
- `browse_menu` — доступ к разделу меню;
- `browse_settings` — доступ к разделу настроек;
- `read_settings` — просмотр настройки;
- `edit_settings` — редактирование настройки;
- `add_settings` — добавление настройки;
- `delete_settings` — удаление настройки.

Для каждого созданного BREAD можно включить **Generate permissions**. Тогда будут созданы разрешения `browse`, `read`, `edit`, `add` и `delete`.

Пример: для BREAD таблицы `products` создаются ключи `browse_products`, `read_products`, `edit_products`, `add_products` и `delete_products`.

> Если пункт меню связан с BREAD, проверяется разрешение `browse`. Например, для пункта `Posts` проверяется `browse_posts`. Если у пользователя нет разрешения, пункт меню скрывается.

### Разрешения для собственной страницы

Если нужно ограничить доступ к собственной странице, используйте разрешения. Это работает, когда slug идёт сразу после `/admin/`. Для страницы вида `/admin/sub/foo` пункт меню не будет скрыт.

#### Создание разрешения

Создайте разрешение в таблице `permissions` (например, через BREAD; модель — `TCG\Voyager\Models\Permission`). Колонка `table_name` должна быть `null`. Колонка `key` должна иметь вид `browse_slug`, где `slug` — slug страницы. Например, для страницы `/admin/create_bill` создайте разрешение `browse_create_bill`.

#### Назначение роли

Отметьте разрешение для нужных ролей на странице `admin/roles`. В примере появится чекбокс «Browse Create Bill». Если у пользователя нет разрешения, пункт меню со страницей будет скрыт.

#### Настройка контроллера

Создайте собственный [gate](https://laravel.com/docs/authorization#gates):

```php
Gate::define('browse_create_bill', function ($user) {
    return $user->hasPermission('browse_create_bill');
});
```

Используйте `authorize` в контроллере:

```php
public function index()
{
  $this->authorize('browse_create_bill');
  //..
```

Если используете собственный guard, добавьте его в контроллер:

```php
  /**
   * Get the guard to be used during authentication.
   *
   * @return \Illuminate\Contracts\Auth\StatefulGuard
   */
  protected function guard()
  {
      return Auth::guard(app('VoyagerGuard'));
  }
```

### Разрешения в Blade

Проверка разрешений в шаблонах:

```php
@can('browse', $post)
    I can browse posts
@endcan
```

С условием `else`:

```php
@can('browse', $post)
    I can browse posts
@else
    I cannot browse posts
@endcan
```

---

## Helper-методы

Voyager предоставляет helper-функции, которые упрощают разработку.

### URL миниатюр

Voyager создаёт миниатюры для поля изображения, если заданы [дополнительные параметры поля](#изображения).

Чтобы вывести миниатюру или получить её URL, подключите трейт `Resizable` в модели:

```php
use TCG\Voyager\Traits\Resizable;

class Post extends Model
{
    use Resizable;
}
```

#### Одно изображение

```php
@foreach($posts as $post)
    <img src="{{Voyager::image($post->thumbnail('small'))}}" />
@endforeach
```

Или с именем поля изображения (по умолчанию `image`):

```php
@foreach($posts as $post)
    <img src="{{Voyager::image($post->thumbnail('small', 'photo'))}}" />
@endforeach
```

#### Несколько изображений

```php
@foreach($posts as $post)
    $images = json_decode($post->images);
    @foreach($images as $image)
        <img src="{{ Voyager::image($post->getThumbnail($image, 'small')) }}" />
    @endforeach
@endforeach
```

---

## Мультиязычность

Voyager поддерживает несколько языков для моделей. Сначала выполните настройку.

### Настройка

Укажите `locales` и включите мультиязычность в `config/voyager.php`:

```php
'multilingual' => [
        'enabled' => true,
        'default' => 'en',
        'locales' => [
            'en',
            'da',
        ],
    ],
```

Подключите трейт `Translatable` в модели и перечислите переводимые атрибуты:

```php
use TCG\Voyager\Traits\Translatable;
class Post extends Model
{
    use Translatable;
    protected $translatable = ['title', 'body'];
}
```

После этого в BREAD Pages появится выбор языка.

### Использование

#### Eager-load переводов

```php
// Загружает все переводы
$posts = Post::with('translations')->get();

// Загружает все переводы
$posts = Post::all();
$posts->load('translations');

// Загружает переводы для текущей локали и fallback-локали
$posts = Post::withTranslations()->get();

// Загружает переводы для указанных локалей и fallback-локали
$posts = Post::withTranslations(['en', 'da'])->get();

// Загружает перевод для локали da и fallback-локали
$posts = Post::withTranslation('da')->get();

// Загружает перевод для текущей локали и fallback-локали
$posts = Post::withTranslation()->get();
```

#### Значение языка по умолчанию

```php
echo $post->title;
```

#### Переведённое значение

```php
echo $post->getTranslatedAttribute('title', 'locale', 'fallbackLocale');
```

Если локаль не указана, используется текущая локаль приложения. Можно передать свою локаль строкой. Если `fallbackLocale` не указана, используется fallback-локаль приложения. Можно передать свою строку. Чтобы отключить fallback, передайте `false`. Если значение не найдено ни для локали, ни для fallback, атрибут становится `null`.

#### Перевод всей модели

```php
$post = $post->translate('locale', 'fallbackLocale');
echo $post->title;
echo $post->body;

// Метод translate можно вызвать на коллекции Eloquent,
// чтобы перевести все модели в коллекции.
$posts = $posts->translate('locale', 'fallbackLocale');
echo $posts[0]->title;
```

Правила для локали и fallback такие же, как выше. Если значение не найдено, атрибут становится `null`.

#### Проверка переводимости модели

```php
// со строкой
if (Voyager::translatable(Post::class)) {
    // модель переводима
}

// с объектом модели или коллекции
if (Voyager::translatable($post)) {
    // модель переводима
}
```

#### Установка переводов атрибутов

```php
$post = $post->translate('da');
$post->title = 'foobar';
$post->save();
```

Будет обновлён или создан перевод `title` для локали `da`. Если изменяемый атрибут не переводимый, изменения попадут напрямую в модель, то есть перезапишут значение языка по умолчанию.

#### Поиск по переводам

Для поиска по переведённому значению используйте `whereTranslation`. Например, для поиска по slug страницы:

```php
$page = Page::whereTranslation('slug', 'my-translated-slug');
// То же самое
$page = Page::whereTranslation('slug', '=', 'my-translated-slug');
// Искать только в локалях en, de и в локали по умолчанию
$page = Page::whereTranslation('slug', '=', 'my-translated-slug', ['en', 'de']);
// Искать только в локалях en и de
$page = Page::whereTranslation('slug', '=', 'my-translated-slug', ['en', 'de'], false);
```

Параметры `whereTranslation`:

- `field` — поле поиска;
- `operator` — оператор, по умолчанию `=`. Может быть значением, как в [where](https://laravel.com/docs/queries#where-clauses);
- `value` — искомое значение;
- `locales` — массив локалей. `null` означает поиск по всем локалям;
- `default` — искать ли также в значении по умолчанию. По умолчанию `true`.

---

## Переопределение файлов

### Переопределение представлений BREAD

Представления BREAD можно переопределить для **одного** BREAD, создав папку `resources/views/vendor/voyager/slug-name`, где `slug-name` — slug таблицы. Доступны четыре файла:

- `browse.blade.php`
- `edit-add.blade.php`
- `read.blade.php`
- `order.blade.php`

Или переопределите представления для **всех** BREAD, создав эти файлы в `resources/views/vendor/voyager/bread`.

### Переопределение кнопки отправки

Кнопку отправки можно изменить без переопределения всего `edit-add.blade.php`, расширив секцию `submit-buttons`:

```blade
@extends('voyager::bread.edit-add')
@section('submit-buttons')
    @parent
    <button type="submit" class="btn btn-primary save">Save And Publish</button>
@endsection
```

### Собственные контроллеры

Контроллер для одного BREAD можно переопределить, создав класс, который наследует контроллер Voyager:

```php
<?php

namespace App\Http\Controllers;

class VoyagerCategoriesController extends \TCG\Voyager\Http\Controllers\VoyagerBaseController
{
    //...
}
```

Затем в настройках BREAD укажите полное имя класса в поле **Controller Name**. После этого можно переопределять любые методы `VoyagerBaseController`.

### Переопределение контроллеров Voyager

> **Используйте только при необходимости**
> Мы не рекомендуем переопределять все контроллеры: изменения в будущих версиях не попадут в вашу копию.

Измените namespace в `config/voyager.php`:

```php
/*
|--------------------------------------------------------------------------
| Controllers config
|--------------------------------------------------------------------------
|
| Here you can specify voyager controller settings
|
*/

'controllers' => [
    'namespace' => 'App\\Http\\Controllers\\Voyager',
],
```

Затем выполните:

```bash
php artisan voyager:controllers
```

Voyager будет использовать дочерние контроллеры, созданные в `App/Http/Controllers/Voyager`.

### Переопределение моделей Voyager

Модели Voyager можно заменить. Добавьте в метод `register` класса `AppServiceProvider`:

```php
Voyager::useModel($name, $object);
```

Где **name** — имя класса модели, а **object** — полное имя вашей модели. Например:

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Events\Dispatcher;
use TCG\Voyager\Facades\Voyager;

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Voyager::useModel('DataRow', \App\DataRow::class);
    }
    // ...
}
```

Создайте модель, наследующую оригинал. Для `DataRow`:

```php
<?php

namespace App;

class DataRow extends \TCG\Voyager\Models\DataRow
{
    // ...
}
```

Если у заменяемой модели есть BREAD, откройте настройки BREAD и замените **Model Name** на полное имя вашего класса.

---

## Переопределение маршрутов

Любые маршруты Voyager можно переопределить, описав свои маршруты после `Voyager::routes()`. Например, чтобы заменить `LoginController`:

```php
Route::group(['prefix' => 'admin'], function () {
   Voyager::routes();

   // Ваши переопределения
   Route::post('login', ['uses' => 'MyAuthController@postLogin', 'as' => 'postlogin']);
});
```

---

## Дополнительные CSS и JS

В последних версиях дополнительные CSS и JS подключаются к master-шаблону Voyager без копирования и изменения самого шаблона. Файлы добавляются _после_ ресурсов Voyager, поэтому могут переопределять стили и поведение.

Настройка выполняется в `config/voyager.php`:

```php
// Здесь можно указать дополнительные ресурсы для master.blade
'additional_css' => [
    //'css/custom.css',
],
'additional_js' => [
   //'js/custom.js',
],
```

> Путь передаётся в функцию Laravel [`asset()`](https://laravel.com/docs/helpers#method-asset).

---

## Мягкое удаление

Этот раздел помогает включить мягкое удаление моделей в Voyager. Подробности — в [документации Laravel](https://laravel.com/docs/eloquent#soft-deleting).

### Настройка таблицы

При создании таблицы в менеджере базы данных выберите **Add Soft Deletes**, а при добавлении BREAD укажите имя модели. После этого достаточно изменить файл модели.

### Изменение модели

Обычная модель выглядит так:

```php
<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


class YourModelName extends Model
{

}
```

Измените её:

```php
<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class YourModelName extends Model
{
    use SoftDeletes;

    protected $casts = [
        'deleted_at' => 'datetime',
    ];
}
```

Теперь при удалении записи она не удаляется физически: в колонку `deleted_at` записывается текущая дата и время.

---

## Дополнительные атрибуты связей

Начиная с Voyager 1.1 в связях можно показывать дополнительные атрибуты.

Например, `Post` связан с `Author`, и нужно вывести полное имя пользователя. Сначала определите [аксессор](https://laravel.com/docs/eloquent-mutators#defining-an-accessor):

```php
public function getFullNameAttribute()
{
    return "{$this->first_name} {$this->last_name}";
}
```

Затем укажите аксессор в модели:

```php
public $additional_attributes = ['full_name'];
```

Теперь `full_name` можно выбрать в настройках связи.

---

## Свои formfields

Добавить новый formfield в Voyager просто. В примере ниже создаётся числовое поле (оно уже есть в Voyager по умолчанию).

Создайте класс `NumberFormField` в проекте:

```php
<?php

namespace App\FormFields;

use TCG\Voyager\FormFields\AbstractHandler;

class NumberFormField extends AbstractHandler
{
    protected $codename = 'number';

    public function createContent($row, $dataType, $dataTypeContent, $options)
    {
        return view('formfields.number', [
            'row' => $row,
            'options' => $options,
            'dataType' => $dataType,
            'dataTypeContent' => $dataTypeContent
        ]);
    }
}
```

Переменная `codename` используется в выпадающем списке конструктора BREAD. Метод `createContent` возвращает представление для отображения поля.

Создайте представление:

```markup
<input type="number"
       class="form-control"
       name="{{ $row->field }}"
       data-name="{{ $row->display_name }}"
       @if($row->required == 1) required @endif
             step="any"
       placeholder="{{ isset($options->placeholder)? old($row->field, $options->placeholder): $row->display_name }}"
       value="@if(isset($dataTypeContent->{$row->field})){{ old($row->field, $dataTypeContent->{$row->field}) }}@else{{old($row->field)}}@endif">
```

В представлении можно добавлять любую логику.

Зарегистрируйте formfield в service provider, например в `AppServiceProvider`:

```php
<?php

namespace App\Providers;

use TCG\Voyager\Facades\Voyager;
use App\FormFields\NumberFormField;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    //..

    public function register()
    {
        Voyager::addFormField(NumberFormField::class);
    }
}
```

---

## Координаты в модели

Voyager умеет хранить координаты и выбирать их на карте. Колонка таблицы должна иметь тип `GEOMETRY` или `POINT`.

Подключите трейт `Spatial` в модели и укажите колонку:

```php
<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use TCG\Voyager\Traits\Spatial;

class Category extends Model
{
    use Spatial;

    protected $spatial = ['your_column'];
}
```

Затем в настройках BREAD установите для поля тип `Coordinates`. Появится карта для выбора координат.

> Укажите Google Maps API-ключ в [конфигурации](#google-maps). Там же задаётся положение карты по умолчанию.

### Получение координат

```php
$model->getCoordinates();
```

Метод возвращает массив с `lat` (широта) и `lng` (долгота).

---

## BREAD-аксессоры

Иногда атрибут нужно форматировать только для одного или нескольких действий BREAD. Например, есть поле `name`, и на странице списка нужно показать текст, если поле пустое:

```php
<?php

public function getNameBrowseAttribute()
{
    return $this->name ?? 'Empty';
}
```

Если поле пустое, будет показано `Empty`, иначе — значение.

Аналогично для других действий:

```php
<?php

public function getNameReadAttribute()
{
    //
}

public function getNameEditAttribute()
{
    //
}

public function getNameAddAttribute()
{
    //
}
```

---

## Собственный guard

Начиная с Voyager 1.2 можно определить собственный guard, который используется во всём Voyager. Привяжите имя guard к `VoyagerGuard`.

Сначала определите guard по [документации Laravel](https://laravel.com/docs/authentication#adding-custom-guards). Затем в методе `register` класса `AuthServiceProvider` добавьте:

```php
$this->app->singleton('VoyagerGuard', function () {
    return 'your-custom-guard-name';
});
```

Теперь вместо стандартного guard используется указанный.

### Пример: отдельная модель и таблица для администраторов

Создайте таблицу `admins`:

```php
<?php
Schema::create('admins', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->bigInteger('role_id')->unsigned()->nullable();
    $table->string('name');
    $table->string('email')->unique();
    $table->string('avatar')->nullable()->default('users/default.png');
    $table->string('password')->nullable();
    $table->string('remember_token')->nullable();
    $table->text('settings')->nullable()->default(null);
    $table->timestamps();
    $table->foreign('role_id')->references('id')->on('roles');
});
```

Создайте модель, наследующую модель пользователя Voyager:

```php
<?php

namespace App;

class Admin extends \TCG\Voyager\Models\User
{

}
```

Создайте guard `admin` в `config/auth.php`:

```php
'guards' => [
    'admin' => [
        'driver' => 'session',
        'provider' => 'admins',
    ],

    // ...
],
```

И провайдер пользователей `admins`:

```php
'providers' => [
    'admins' => [
        'driver' => 'eloquent',
        'model' => App\Admin::class,
    ],

    // ...
],
```

Укажите новый guard в `AppServiceProvider.php`:

```php
public function register()
{
    $this->app->singleton('VoyagerGuard', function () {
        return 'admin';
    });
}
```

> BREAD пользователей по-прежнему редактирует `users`, а не `admins`. Если нужно управлять администраторами, создайте BREAD для таблицы `admins`.

---

## Кнопки действий

Кнопки действий показываются при просмотре BREAD рядом с каждой строкой.

Создайте класс действия, наследующий `AbstractAction`, например `app/Actions/MyAction.php`:

```php
<?php

namespace App\Actions;

use TCG\Voyager\Actions\AbstractAction;

class MyAction extends AbstractAction
{
    public function getTitle()
    {
        return 'My Action';
    }

    public function getIcon()
    {
        return 'voyager-eye';
    }

    public function getPolicy()
    {
        return 'read';
    }

    public function getAttributes()
    {
        return [
            'class' => 'btn btn-sm btn-primary pull-right',
        ];
    }

    public function getDefaultRoute()
    {
        return route('my.route');
    }
}
```

Зарегистрируйте действие в методе `boot()` класса `app/Providers/AppServiceProvider.php`:

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Events\Dispatcher;
use TCG\Voyager\Facades\Voyager;

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Voyager::addAction(\App\Actions\MyAction::class);
    }
}
```

После этого кнопка появится при просмотре любого BREAD.

### Показ и скрытие действий

Чтобы показывать действие только для определённых типов данных, реализуйте `shouldActionDisplayOnDataType()`:

```php
<?php

public function shouldActionDisplayOnDataType()
{
    return $this->dataType->slug == 'posts';
}
```

Чтобы управлять показом для каждой строки, реализуйте `shouldActionDisplayOnRow($row)`:

```php
<?php

public function shouldActionDisplayOnRow($row)
{
    return $row->id > 10;
}
```

### Массовые действия

Массовые действия вызываются для нескольких записей модели. Чтобы действие стало массовым, реализуйте метод:

```php
<?php

public function massAction($ids, $comingFrom)
{
    // Do something with the IDs
    return redirect($comingFrom);
}
```

---

## Решение проблем

### HTTPS и изображения

**Симптом:** при открытии сайта по HTTPS изображения Voyager не загружаются.

**Причина:** URL изображений создаются так:

```php
Storage::disk(config('voyager.storage.disk'))->url($file);
```

Если `voyager.storage.disk` равен `public`, а диск `public` используется по умолчанию, в конфигурации диска задан абсолютный URL:

```php
'url' => env('APP_URL').'/storage',
```

Он использует `APP_URL` без HTTPS и строит абсолютный URL.

**Решение:** уберите `env('APP_URL')` из конфигурации диска `public`. Тогда будет формироваться относительный URL, использующий текущий домен и протокол.

Если нужен абсолютный URL, оберните вызов `Voyager::image('...')` в `asset()`:

```php
asset(Voyager::image('...'))
```

Такой вариант вернёт текущий протокол, домен и правильный путь к изображению.

### Missing required parameter

**Симптом:** появляется страница ошибки:

```text
Missing required parameters for [Route...]
```

**Причина:** возможны две причины:

1. В таблице нет первичного ключа.
2. Первичный ключ есть, но он называется **не** `id`.

**Решение:**

1. Создайте поле `id` в таблице.
2. Укажите первичный ключ в модели:

```php
protected $primaryKey = 'your_primary_key';
```

Соблюдайте [соглашения моделей Eloquent](https://laravel.com/docs/eloquent#eloquent-model-conventions).

---

## Разработка и тестирование

Проверка пакета перед релизом:

```bash
composer validate
vendor/bin/phpunit
```

Актуальный набор тестов: 140 тестов, 937 утверждений на PHP 8.4. Отдельно проверена установка в чистом приложении Laravel 13.39.0:

```bash
php artisan voyager:install --with-dummy
```

Проверены вход, панель управления, BREAD CRUD, редактор BREAD, менеджер базы данных, конструктор меню, ресурсы расширения, клонирование, переводы, изображения и проверка капчи.

### История изменений

Подробная история — в [CHANGELOG.md](CHANGELOG.md).

### Версионирование

Проект следует [semver](http://semver.org/). Изменения без обратной совместимости выпускаются в новой минорной или мажорной версии. Исправления ошибок — в патч-релизах.

### Лицензия

Voyager распространяется по лицензии MIT. Подробности — в файле [license](license).

### Благодарности

Voyager proudly supported by our amazing sponsors:

[![DigitalOcean Referral Badge](https://web-platforms.sfo2.cdn.digitaloceanspaces.com/WWW/Badge%203.svg)](https://www.digitalocean.com/?refcode=dc19b9819d06&utm_campaign=Referral_Invite&utm_medium=Referral_Program&utm_source=badge)
