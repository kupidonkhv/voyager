# Changelog

All notable changes to this project will be documented in this file.

## [1.7.48] - 2026-10-31

### 🔐 Admin Activity Log

- Added a built-in activity log that records who, when, what action, on which object and with what result
- Covered BREAD record changes, deletions, restores and clones
- Covered file uploads, replacements, moves, renames and deletions
- Covered settings changes, role and permission assignments
- Covered login, logout, failed login attempts and access denials
- Added the `/admin/activity` section with filters and event details
- Added `browse_activity_logs` and `browse_activity_log_context` permissions
- Added `php artisan voyager:activity:install` and `php artisan voyager:activity:prune`
- Added `config/voyager-activity.php`, English and Russian translations

### 🛡️ Security

- Record edits store affected field names only; old and new values are never stored or exposed
- Deletions store only the subject type and identifier
- Passwords, tokens, file contents and request bodies are excluded
- Event metadata is validated against a per-event allow-list
- Absolute paths, unsafe strings and forbidden keys are rejected
- Activity log failures do not roll back the primary admin action

### 📚 Documentation

- Rewrote the README in Russian across the full Voyager documentation set
- Documented Galleries, Captcha, the embedded extension and the activity log
- Documented PHP 8.2–8.4 and Laravel 10–13 support

### ✅ Verification

- 148 tests, 978 assertions on PHP 8.4 / Laravel 13.39.0
- `composer validate` passes

## [1.7.47] - 2026-10-30

### 🖼️ Galleries

- Added a Gallery BREAD stored through `spatie/laravel-medialibrary`
- Added image uploads, captions, drag sorting and thumbnail size selection
- Added localized admin menu installation for `en` and `ru`
- Added `%gallery="Gallery name"%` content embedding
- Added `default`, `photo-carousel` and `rounded` frontend templates
- Added `php artisan voyager:gallery:install`

### ✅ Verification

- 140 tests, 937 assertions on PHP 8.4 / Laravel 13.39.0
- Fresh Laravel 13 installation verified

## [1.7.46] - 2026-10-30

### 🐛 Packaging Fix

- Include extension views, assets and translations in the released archive
- Anchor the root `vendor` ignore rule so `resources/views/vendor`, `publishable/assets/vendor` and `publishable/lang/vendor` are tracked
- Verified installation from the Packagist release archive in a clean Laravel 13 application

## [1.7.45] - 2026-10-30

### 📦 Embedded Extensions

- Embedded `kupidonkhv/voyager-extension` into `src/Extension` with its views, assets, routes, form fields, generators and clone action
- Embedded `kupidonkhv/captcha` into `src/Captcha`, including helpers, routes, validation rules and the `Captcha` facade alias
- Added `spatie/laravel-medialibrary` and `ext-gd` dependencies for extended media fields
- Added publish tags for extension config/assets/views/lang and captcha config
- Added `voyager-extension.enabled` switch (`VOYAGER_EXTENSION_ENABLED`)
- Declared conflicts with `mews/captcha` and `monstrex/voyager-extension`

### 🐛 Laravel 13 Fixes

- Refreshed Laravel route name lookups after Voyager route registration
- Replaced Carbon's removed `formatLocalized()` in extension BREAD views with `Voyager::formatDate`

### ✅ Verification

- 135 tests, 902 assertions on Laravel 13.39.0 / PHP 8.4
- Fresh Laravel 13 install with `voyager:install --with-dummy`
- Verified login, dashboard, BREAD CRUD, BREAD editor, database manager, menu builder, extension assets/clone/translations and captcha image/validation

## [1.7.44] - 2026-10-30

### 🚀 Laravel 13 Compatibility

**Major Changes:**
- ✅ **Laravel 13.x support** (verified with 13.39.0) while keeping Laravel 10, 11 and 12 support
- ✅ **PHP 8.4 support** - implicit nullable parameter deprecations fixed
- ✅ **Intervention Image 3.7+ and 4.x** compatibility layer, including Laravel 13's native image manager
- ✅ **Database schema builder restored without Doctrine DBAL** - create, update, rename and drop tables
- ✅ **Native schema introspection** via Laravel Schema (SQLite verified)
- ✅ **Carbon 3 compatible date formatting** for BREAD views via `Voyager::formatDate`
- ✅ **`voyager:make:model`** keeps its Voyager signature on Laravel 13
- ✅ **Menu caching** respects Laravel's cache class allow-list
- ✅ Removed debug logging from the BREAD form and fixed test teardown

**Testing & Validation:**
- ✅ 135 tests, 902 assertions on Laravel 11 and Laravel 13
- ✅ Fresh Laravel 13 app: `voyager:install --with-dummy` completed successfully
- ✅ Verified login, dashboard, BREAD CRUD, image upload/resize/thumbnails, database table create/drop, media manager, menus and Compass


## [1.7.7] - 2025-09-13

### 🚀 Laravel 12 Compatibility

**Major Changes:**
- ✅ **Complete Doctrine DBAL removal** - No external Doctrine dependency
- ✅ **Native Laravel schema inspection** implemented for all database drivers
- ✅ **BasicTypes system** with 14 native Laravel type mappings

**New Type System:**
- BooleanType - Boolean values
- IntegerType - Integer numbers  
- FloatType - Floating point numbers
- DecimalType - Decimal numbers
- StringType - String values
- TextType - Text content
- CharType - Fixed-length characters
- VarCharType - Variable-length characters
- DateType - Date values
- DateTimeType - DateTime values
- TimeType - Time values
- JsonType - JSON data
- NumericType - Numeric values
- DoubleType - Double precision numbers

**Technical Improvements:**
- Updated DatabaseUpdater to use Laravel schema methods
- Enhanced Table class with proper diff functionality
- Fixed Type registration to exclude BasicTypes from platform types
- Improved null safety in blade templates

**Testing & Validation:**
- ✅ SQLite database integration tested
- ✅ All BREAD operations (CRUD) working
- ✅ Field validations and type mappings verified
- ✅ Rich text editor functionality confirmed
- ✅ Bulk delete operations tested

## [1.7.6] - 2025-09-13

### 🐛 Bug Fixes
- Fixed Doctrine replacement with Laravel Schema for Laravel 12 compatibility in BREAD panel

## [1.7.5] - 2025-09-13

### 🐛 Bug Fixes  
- Replaced deprecated getDoctrineSchemaManager with createSchemaManager for Laravel 12 compatibility

## [1.7.4] - 2025-09-13

### 📦 Dependencies
- Updated package version

## [1.7.3] - 2025-09-13

### 📦 Dependencies
- Updated package version

## [1.7.2] - 2025-09-13

### 📖 Documentation
- Updated README with Laravel 12 compatibility information

## [1.7.1] - 2025-09-13

### 🚀 Initial Fork
- Forked from thedevdojo/voyager
- Initial setup for Laravel 12 compatibility
- Updated dependencies for modern PHP versions
