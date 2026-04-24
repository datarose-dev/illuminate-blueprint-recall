<?php

namespace Datarose\BlueprintRecall\Enums;

use Datarose\BlueprintRecall\Exceptions\SqlTypeConversionException;

enum BlueprintType: string
{
    // String types
    case STRING = 'string';
    case TEXT = 'text';

    // Integer types
    case TINY_INTEGER = 'tinyInteger';
    case SMALL_INTEGER = 'smallInteger';
    case MEDIUM_INTEGER = 'mediumInteger';
    case INTEGER = 'integer';
    case BIG_INTEGER = 'bigInteger';

    // Floating point and decimal types
    case FLOAT = 'float';
    case DOUBLE = 'double';
    case DECIMAL = 'decimal';

    // Boolean types
    case BOOLEAN = 'boolean';

    // Date and time types
    case DATE = 'date';
    case TIME = 'time';
    case DATETIME = 'dateTime';
    case TIMESTAMP = 'timestamp';
    case YEAR = 'year';

    // Enum types
    case ENUM = 'enum';

    // JSON types
    case JSON = 'json';

    // Binary types
    case BINARY = 'binary';

    // UUID types
    case UUID = 'uuid';

    public static function fromSqlType(string $sqlType): self
    {
        return match ($sqlType) {
            // String types
            'varchar', 'char' => self::STRING,
            'text', 'smalltext', 'mediumtext', 'longtext' => self::TEXT,

            // Integer types
            'tinyint' => self::TINY_INTEGER,
            'smallint' => self::SMALL_INTEGER,
            'mediumint' => self::MEDIUM_INTEGER,
            'int', 'integer' => self::INTEGER,
            'bigint' => self::BIG_INTEGER,

            // Floating point and decimal types
            'float' => self::FLOAT,
            'double' => self::DOUBLE,
            'decimal', 'numeric' => self::DECIMAL,

            // Boolean types
            'bit', 'bool', 'boolean' => self::BOOLEAN,

            // Date and time types
            'date' => self::DATE,
            'time' => self::TIME,
            'datetime' => self::DATETIME,
            'timestamp' => self::TIMESTAMP,
            'year' => self::YEAR,

            // Enum types
            'enum' => self::ENUM,

            // JSON types
            'json' => self::JSON,

            // Binary types
            'binary', 'varbinary', 'blob' => self::BINARY,

            // UUID types
            // varchar(36), char(36), binary(16) esetében nem ez érvényesül
            'uuid' => self::UUID,

            // Default case for invalid SQL type
            default => throw new SqlTypeConversionException($sqlType),
        };
    }
}
