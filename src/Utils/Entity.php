<?php

namespace Pushword\Core\Utils;

class Entity
{
    /**
     * @return list<string>
     */
    public static function getProperties(object $object): array
    {
        $reflClass = new \ReflectionClass(\get_class($object));
        $properties = array_filter($reflClass->getProperties(), function (\ReflectionProperty $property) {
            if (false !== strpos((string) $property->getDocComment(), '@ORM\Column')) {
                return true;
            }
        });
        $propertyNames = [];
        foreach ($properties as $property) {
            if ('id' === $property->getName()) {
                continue;
            }
            $propertyNames[] = $property->getName();
        }

        return $propertyNames;
    }
}
