<?php

namespace AppBundle\PHPStan;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\NullType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

/**
 * HeaderBag::get($key, $default = null, $first = true) returns the first value of the header (a
 * string) or the default: only get(..., ..., false) returns all the values.
 */
class HeaderBagGetReturnTypeExtension implements DynamicMethodReturnTypeExtension {
    public function getClass(): string {
        return 'Symfony\Component\HttpFoundation\HeaderBag';
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool {
        return $methodReflection->getName() === 'get';
    }

    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): Type {
        $args = $methodCall->getArgs();
        if (isset($args[2])) {
            $first = $scope->getType($args[2]->value);
            if (!$first instanceof ConstantBooleanType || !$first->getValue()) {
                return ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();
            }
        }
        $default = isset($args[1]) ? $scope->getType($args[1]->value) : new NullType();

        return TypeCombinator::union(new StringType(), $default);
    }
}
