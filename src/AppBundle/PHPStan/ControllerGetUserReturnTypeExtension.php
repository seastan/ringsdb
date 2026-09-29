<?php

namespace AppBundle\PHPStan;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

/**
 * Controller::getUser() returns the logged in user, an AppBundle\Entity\User (the only user
 * class, FOSUserBundle's user provider), or null for an anonymous visitor.
 */
class ControllerGetUserReturnTypeExtension implements DynamicMethodReturnTypeExtension {
    public function getClass(): string {
        return 'Symfony\Bundle\FrameworkBundle\Controller\Controller';
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool {
        return $methodReflection->getName() === 'getUser';
    }

    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): Type {
        return TypeCombinator::union(new ObjectType('AppBundle\Entity\User'), new NullType());
    }
}
