<?php

namespace AppBundle\PHPStan;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

/**
 * The application only uses the Doctrine ORM: the "doctrine" registry (Registry, getDoctrine())
 * always returns ORM entity managers and DBAL connections, not the generic ObjectManager / object
 * declared by doctrine/persistence. phpstan-doctrine types the repositories (getRepository() with
 * a class name), not the registry.
 */
class DoctrineRegistryReturnTypeExtension implements DynamicMethodReturnTypeExtension {
    const RETURN_TYPES = [
        'getManager' => 'Doctrine\ORM\EntityManager',
        'getManagerForClass' => 'Doctrine\ORM\EntityManager',
        'resetManager' => 'Doctrine\ORM\EntityManager',
        'getConnection' => 'Doctrine\DBAL\Connection',
    ];

    public function getClass(): string {
        return 'Doctrine\Persistence\ManagerRegistry';
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool {
        return isset(self::RETURN_TYPES[$methodReflection->getName()]);
    }

    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): Type {
        return new ObjectType(self::RETURN_TYPES[$methodReflection->getName()]);
    }
}
