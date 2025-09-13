# Changelog

All notable changes to this project will be documented in this file.

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
