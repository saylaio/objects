<?php

namespace Sayla\Objects\Attribute;

use ArrayIterator;
use Sayla\Helper\Data\BaseHashMap;
use Sayla\Helper\Data\Contract\FreezableTrait;
use Sayla\Objects\Contract\Attributes\Property;

class PropertySet extends BaseHashMap implements Property
{
    use FreezableTrait;
    /** @var string */
    private $name;

    /**
     * @param mixed $value
     * @param string $name
     */
    public function __construct(string $name, array $value)
    {
        $this->name = $name;
        $this->fill(array_replace_key($value, function (Property $property) {
            return $property->getName();
        }));
        $this->freeze();
    }

    public function __debugInfo()
    {
        return [
            'name' => $this->name,
            'value' => $this->getValue(),
        ];
    }

    public function count()
    {
        return count($this->toArray());
    }

    /**
     * @return \ArrayIterator|Property[]
     */
    public function getIterator()
    {
        return new ArrayIterator($this->items);
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return Property[]
     */
    public function getValue()
    {
        return $this->toArray();
    }

    protected function put(string $key, Property $item)
    {
        $this->items[$key] = $item;
    }
}