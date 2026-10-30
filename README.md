<p align="center"><a href="https://voyager.devdojo.com" target="_blank"><img width="400" src="https://s3.amazonaws.com/thecontrolgroup/voyager.png"></a></p>

> [!Note]
> **Voyager продолжает развиваться!** Форк проекта активно поддерживается и обновляется для совместимости с новыми версиями Laravel.

### 🚀 Новые возможности и обновления

**Поддержка Laravel 13:**
- Полная совместимость с Laravel 13.x (проверено на Laravel 13.39.0)
- Поддержка PHP 8.2, 8.3 и 8.4
- Совместимость с Intervention Image 3.7+ и 4.x, включая image-расширение Laravel 13
- Работа со схемой и интроспекцией БД без Doctrine DBAL, включая SQLite
- Обновлены Artisan-команды, Compass, MakeModel и тесты под Laravel 13 / PHPUnit 12
- Сохранена совместимость с Laravel 10, 11 и 12

**Встроенные расширения (начиная с 1.7.45):**
- `kupidonkhv/voyager-extension` включён прямо в пакет: расширенные BREAD-шаблоны, клонирование записей, поля `adv_image`, `adv_media_files`, `adv_inline_set`, генераторы путей Media Library и расширенный конструктор меню
- `kupidonkhv/captcha` включён прямо в пакет: изображения капчи, API-капча и правила валидации `captcha` / `captcha_api`
- Отдельные пакеты `monstrex/voyager-extension`, `kupidonkhv/captcha` и `mews/captcha` больше не нужны и несовместимы с этой версией
- Для работы расширенных медиа-полей добавлена зависимость `spatie/laravel-medialibrary` и `ext-gd`

**Основные изменения:**
- Удалены устаревшие методы Doctrine из миграций и работы со схемой
- Обновлены версии зависимостей в composer.json
- Исправлены неявно nullable-параметры для PHP 8.4
- Добавлен слой совместимости Intervention Image 3/4
- Форматирование дат в расширенных BREAD-шаблонах переведено на `Voyager::formatDate` (Carbon 3)
- После регистрации маршрутов обновляются name-lookups Laravel, поэтому BREAD-маршруты доступны и при повторной загрузке `routes/web.php`

**Установка для Laravel 13:**
```bash
composer require kupidonkhv/voyager-fork:^1.7.46
```

Если в проекте уже установлены старые отдельные пакеты, сначала удалите их:
```bash
composer remove monstrex/voyager-extension kupidonkhv/captcha mews/captcha
composer require kupidonkhv/voyager-fork:^1.7.46
```

Публикация конфигурации и ресурсов (необязательно):
```bash
php artisan vendor:publish --tag=voyager-extension-config
php artisan vendor:publish --tag=captcha-config
php artisan vendor:publish --tag=voyager-extension-assets
```

Расширение включено по умолчанию. Отключить его можно в `.env`:
```dotenv
VOYAGER_EXTENSION_ENABLED=false
```

Капча доступна по адресу `/captcha/{config}` (по умолчанию `/captcha/default`), через helper `captcha()`, `captcha_img()`, `captcha_src()` и правила валидации `captcha` / `captcha_api`.

> Если Packagist ещё не проиндексировал новый релиз, подождите несколько минут или установите ветку разработки:
> `composer require kupidonkhv/voyager-fork:dev-1.7`

**Проверка перед релизом:**
```bash
composer validate
vendor/bin/phpunit
```
Текущий набор: 135 тестов, 902 утверждения (Laravel 13.39.0, PHP 8.4).

Voyager остается отличным выбором для быстрого создания административных панелей с поддержкой BREAD системы!

# **V**oyager - The Missing Laravel Admin
Made with ❤️ by [The Control Group](https://www.thecontrolgroup.com)

![Voyager Screenshot](https://s3.amazonaws.com/thecontrolgroup/voyager-screenshot.png)

Website & Documentation: https://voyager.devdojo.com/

Video Tutorial Here: https://voyager.devdojo.com/academy/

Join our Slack chat: https://voyager-slack-invitation.herokuapp.com/

View the Voyager Cheat Sheet: https://voyager-cheatsheet.ulties.com/

<hr>

Laravel Admin & BREAD System (Browse, Read, Edit, Add, & Delete), supporting Laravel 10, 11, 12, and 13!

> Looking for older Laravel versions?
> - Laravel 8-9: Use Voyager 1.6.x
> - Laravel 6-7: Use [Voyager 1.5](https://github.com/the-control-group/voyager/tree/1.5)

## Installation Steps

### 1. Require the Package

After creating your new Laravel application you can include the Voyager package with the following command:

```bash
composer require kupidonkhv/voyager-fork
```

> **Поддержка Laravel 10, 11, 12, 13:**
> Текущая версия Voyager полностью совместима с Laravel 10, 11, 12 и 13. Для установки используйте:

```bash
composer require kupidonkhv/voyager-fork:^1.7.46
```

### 2. Add the DB Credentials & APP_URL

Next make sure to create a new database and add your database credentials to your .env file:

```
DB_HOST=localhost
DB_DATABASE=homestead
DB_USERNAME=homestead
DB_PASSWORD=secret
```

You will also want to update your website URL inside of the `APP_URL` variable inside the .env file:

```
APP_URL=http://localhost:8000
```

### 3. Run The Installer

Lastly, we can install voyager. You can do this either with or without dummy data.
The dummy data will include 1 admin account (if no users already exists), 1 demo page, 4 demo posts, 2 categories and 7 settings.

To install Voyager without dummy simply run

```bash
php artisan voyager:install
```

If you prefer installing it with dummy run

```bash
php artisan voyager:install --with-dummy
```

And we're all good to go!

Start up a local development server with `php artisan serve` And, visit [http://localhost:8000/admin](http://localhost:8000/admin).

## Creating an Admin User

If you did go ahead with the dummy data, a user should have been created for you with the following login credentials:

>**email:** `admin@admin.com`   
>**password:** `password`

NOTE: Please note that a dummy user is **only** created if there are no current users in your database.

If you did not go with the dummy user, you may wish to assign admin privileges to an existing user.
This can easily be done by running this command:

```bash
php artisan voyager:admin your@email.com
```

If you did not install the dummy data and you wish to create a new admin user, you can pass the `--create` flag, like so:

```bash
php artisan voyager:admin your@email.com --create
```

And you will be prompted for the user's name and password.

## Sponsors

Voyager is proudly supported by our amazing sponsors. A big thank you to:

[![DigitalOcean Referral Badge](https://web-platforms.sfo2.cdn.digitaloceanspaces.com/WWW/Badge%203.svg)](https://www.digitalocean.com/?refcode=dc19b9819d06&utm_campaign=Referral_Invite&utm_medium=Referral_Program&utm_source=badge)

