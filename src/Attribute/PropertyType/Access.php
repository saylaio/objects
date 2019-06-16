<?php

namespace Sayla\Objects\Attribute\PropertyType;

use Sayla\Objects\Contract\PropertyTypes\AttributePropertyType;
use Sayla\Objects\Contract\PropertyTypes\NormalizesPropertyValue;
use Sayla\Util\Mixin\Mixin;

class Access implements AttributePropertyType, NormalizesPropertyValue
{
    const IDENTITY_PROPERTIES = ['visible', 'readable', 'writable'];
    const NAME = 'access';

    public static function getProviders(): array
    {
        return [
            self::PROVIDER_DESCRIPTOR_MIXIN => function (string $dataType, array $properties): Mixin {
                return new AccessDescriptorMixin($properties);
            }
        ];
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function getPropertyValue(string $attributeName, array $value, string $attributeType): array
    {
        $visible = $value['visible'] == true;
        return [
            'visible' => $visible,
            'hidden' => !$visible,
            'readable' => $value['readable'] == true,
            'writable' => $value['writable'] == true,
        ];
    }

    public function normalizePropertyValue(array $descriptorData, string $objectClass, ?string $classFile): ?array
    {
        return array_merge(
            ['visible' => true, 'readable' => true, 'writable' => true],
            array_only($descriptorData, self::IDENTITY_PROPERTIES)
        );
    }
}