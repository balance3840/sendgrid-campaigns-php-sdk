<?php

namespace SendgridCampaign\DTO;

use ReflectionClass;
use ReflectionProperty;

class BaseDTO
{
    public ?array $_metadata = null;

    public static function fromArray(array $data): static
    {
        $dto = new static();
        $reflection = new ReflectionClass($dto);

        foreach ($data as $key => $value) {
            if (!property_exists($dto, $key)) {
                continue;
            }

            $property = $reflection->getProperty($key);
            $value = self::castValue($property, $value);
            $dto->$key = $value;
        }

        return $dto;
    }

    /**
     * @return static[]
     */
    public static function collect(array $items): array
    {
        return array_map(
            fn(array $item) => static::fromArray($item),
            $items
        );
    }

    private static function castValue(ReflectionProperty $property, mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        $type = $property->getType();

        if (!$type || $type->isBuiltin()) {
            // Check for array of DTOs via docblock
            if ($type?->getName() === 'array' && is_array($value)) {
                return self::castArrayFromDocblock($property, $value);
            }
            return $value;
        }

        $className = $type->getName();

        // Handle backed enums
        if (is_string($value) || is_int($value)) {
            if (enum_exists($className) && is_subclass_of($className, \BackedEnum::class)) {
                return $className::tryFrom($value);
            }
        }

        // Handle nested DTOs
        if (is_array($value) && is_subclass_of($className, self::class)) {
            return $className::fromArray($value);
        }

        return $value;
    }

    private static function castArrayFromDocblock(ReflectionProperty $property, array $value): array
    {
        $docComment = $property->getDocComment();

        if ($docComment && preg_match('/@var\s+([\\\\a-zA-Z0-9_]+)\[\]/', $docComment, $matches)) {
            $className = $matches[1];

            if (!class_exists($className)) {
                $namespace = $property->getDeclaringClass()->getNamespaceName();
                $className = $namespace . '\\' . $className;
            }

            if (class_exists($className) && is_subclass_of($className, self::class)) {
                return $className::collect($value);
            }
        }

        return $value;
    }

    public function toArray(bool $excludeNullValues = false): array
    {
        $result = [];

        foreach (get_object_vars($this) as $key => $value) {
            if ($value instanceof self) {
                $value = $value->toArray($excludeNullValues);
            } elseif ($value instanceof \BackedEnum) {
                $value = $value->value;
            } elseif (is_array($value)) {
                $value = array_map(
                    fn($item) => match (true) {
                        $item instanceof self => $item->toArray($excludeNullValues),
                        $item instanceof \BackedEnum => $item->value,
                        default => $item,
                    },
                    $value
                );
            }

            if ($excludeNullValues && $value === null) {
                continue;
            }

            $result[$key] = $value;
        }

        return $result;
    }
    private function isEmpty(mixed $value): bool
    {
        return $value === null
            || $value === ''
            || $value === []
            || (is_array($value) && count(array_filter($value)) === 0);
    }
}