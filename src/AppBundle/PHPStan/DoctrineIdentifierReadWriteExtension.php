<?php

namespace AppBundle\PHPStan;

use PHPStan\Reflection\ExtendedPropertyReflection;
use PHPStan\Rules\Properties\ReadWritePropertiesExtension;

/**
 * The identifier ($id) of the entities is written by Doctrine when they are persisted or loaded.
 */
class DoctrineIdentifierReadWriteExtension implements ReadWritePropertiesExtension {
    public function isAlwaysRead(ExtendedPropertyReflection $property, string $propertyName): bool {
        return false;
    }

    public function isAlwaysWritten(ExtendedPropertyReflection $property, string $propertyName): bool {
        return $this->isEntityIdentifier($property, $propertyName);
    }

    public function isInitialized(ExtendedPropertyReflection $property, string $propertyName): bool {
        return $this->isEntityIdentifier($property, $propertyName);
    }

    private function isEntityIdentifier(ExtendedPropertyReflection $property, string $propertyName): bool {
        return $propertyName === 'id'
            && strpos($property->getDeclaringClass()->getName(), 'AppBundle\\Entity\\') === 0;
    }
}
