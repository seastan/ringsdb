<?php

namespace App\PHPStan;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

/**
 * Controller::getUser() returns the logged in user, an App\Entity\User (the only user
 * class, FOSUserBundle's user provider), or null for an anonymous visitor.
 */
class ControllerGetUserReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return 'Symfony\Bundle\FrameworkBundle\Controller\AbstractController';
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return 'getUser' === $methodReflection->getName();
    }

    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): Type
    {
        return TypeCombinator::union(new ObjectType('App\Entity\User'), new NullType());
    }
}
