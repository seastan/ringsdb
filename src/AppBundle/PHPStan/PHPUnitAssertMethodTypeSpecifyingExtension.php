<?php

namespace AppBundle\PHPStan;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\MethodTypeSpecifyingExtension;

/** $this->assert*(): see PHPUnitAssertTypeSpecifyingExtension. */
class PHPUnitAssertMethodTypeSpecifyingExtension extends PHPUnitAssertTypeSpecifyingExtension implements MethodTypeSpecifyingExtension {
    public function isMethodSupported(MethodReflection $methodReflection, MethodCall $node, TypeSpecifierContext $context): bool {
        return $this->isSupported($methodReflection, $node);
    }

    public function specifyTypes(MethodReflection $methodReflection, MethodCall $node, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes {
        return $this->specify($methodReflection, $node, $scope);
    }
}
