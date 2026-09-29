<?php

namespace AppBundle\PHPStan;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

/**
 * Methods documented as nullable (or returning false) that never do in this application (see
 * phpstan.neon): the response, request and container of the test client once a request is made,
 * the container of a booted kernel, the content of a response (false for streamed responses).
 */
class NonNullReturnTypeExtension implements DynamicMethodReturnTypeExtension {
    /** @var string */
    private $className;

    /** @var string[] */
    private $methods;

    /**
     * @param string[] $methods
     */
    public function __construct(string $className, array $methods) {
        $this->className = $className;
        $this->methods = $methods;
    }

    public function getClass(): string {
        return $this->className;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool {
        return in_array($methodReflection->getName(), $this->methods, true);
    }

    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): Type {
        $type = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();

        return TypeCombinator::remove(TypeCombinator::removeNull($type), new ConstantBooleanType(false));
    }
}
