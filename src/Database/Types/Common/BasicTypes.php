<?php

namespace TCG\Voyager\Database\Types\Common;

use TCG\Voyager\Database\Types\Type;

class BasicTypes
{
    public static function register()
    {
        // Register basic types
        Type::addType("integer", IntegerType::class);
        Type::addType("string", StringType::class);
        Type::addType("varchar", VarCharType::class);
        Type::addType("text", TextType::class);
        Type::addType("boolean", BooleanType::class);
        Type::addType("datetime", DateTimeType::class);
        Type::addType("date", DateType::class);
        Type::addType("time", TimeType::class);
        Type::addType("float", FloatType::class);
        Type::addType("double", DoubleType::class);
        Type::addType("decimal", DecimalType::class);
        Type::addType("numeric", NumericType::class);
        Type::addType("json", JsonType::class);
        Type::addType("char", CharType::class);
    }
}
