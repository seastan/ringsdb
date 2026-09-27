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
 * always returns ORM entity managers and repositories, not the generic ObjectManager /
 * ObjectRepository declared by doctrine/common.
 */
class DoctrineRegistryReturnTypeExtension implements DynamicMethodReturnTypeExtension {
    const RETURN_TYPES = [
        'getManager' => 'Doctrine\ORM\EntityManager',
        'getManagerForClass' => 'Doctrine\ORM\EntityManager',
        'resetManager' => 'Doctrine\ORM\EntityManager',
        'getRepository' => 'Doctrine\ORM\EntityRepository',
    ];

    public function getClass(): string {
        return 'Doctrine\Common\Persistence\ManagerRegistry';
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool {
        return isset(self::RETURN_TYPES[$methodReflection->getName()]);
    }

    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): Type {
        return new ObjectType(self::RETURN_TYPES[$methodReflection->getName()]);
    }
}
