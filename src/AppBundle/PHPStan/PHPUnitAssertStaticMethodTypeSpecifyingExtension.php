<?php

namespace AppBundle\PHPStan;

use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\StaticMethodTypeSpecifyingExtension;

/** self::assert*(): see PHPUnitAssertTypeSpecifyingExtension. */
class PHPUnitAssertStaticMethodTypeSpecifyingExtension extends PHPUnitAssertTypeSpecifyingExtension implements StaticMethodTypeSpecifyingExtension {
    public function isStaticMethodSupported(MethodReflection $staticMethodReflection, StaticCall $node, TypeSpecifierContext $context): bool {
        return $this->isSupported($staticMethodReflection, $node);
    }

    public function specifyTypes(MethodReflection $staticMethodReflection, StaticCall $node, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes {
        return $this->specify($staticMethodReflection, $node, $scope);
    }
}
