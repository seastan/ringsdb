<?php

namespace AppBundle\PHPStan;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\NotIdentical;
use PhpParser\Node\Expr\BooleanNot;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Instanceof_;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierAwareExtension;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\Reflection\MethodReflection;

/**
 * The PHPUnit assertions narrow the types of their arguments after them: assertNotFalse($row),
 * assertNotNull(), assertInstanceOf(Foo::class, $x), assertInternalType('string', $x),
 * assertTrue(<condition>), assertFalse(<condition>).
 *
 * Base of the extensions for $this->assert*() (PHPUnitAssertMethodTypeSpecifyingExtension) and
 * self::assert*() (PHPUnitAssertStaticMethodTypeSpecifyingExtension).
 */
abstract class PHPUnitAssertTypeSpecifyingExtension implements TypeSpecifierAwareExtension {
    const INTERNAL_TYPES = [
        'array' => 'is_array', 'bool' => 'is_bool', 'boolean' => 'is_bool', 'float' => 'is_float',
        'int' => 'is_int', 'integer' => 'is_int', 'numeric' => 'is_numeric', 'object' => 'is_object',
        'string' => 'is_string',
    ];

    /** @var TypeSpecifier */
    private $typeSpecifier;

    public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void {
        $this->typeSpecifier = $typeSpecifier;
    }

    public function getClass(): string {
        return 'PHPUnit\Framework\Assert';
    }

    /**
     * @param MethodCall|StaticCall $node
     */
    protected function isSupported(MethodReflection $methodReflection, Expr $node): bool {
        return self::condition($methodReflection->getName(), $node->getArgs()) !== null;
    }

    /**
     * @param MethodCall|StaticCall $node
     */
    protected function specify(MethodReflection $methodReflection, Expr $node, Scope $scope): SpecifiedTypes {
        $condition = self::condition($methodReflection->getName(), $node->getArgs());
        if ($condition === null) {
            return new SpecifiedTypes();
        }

        return $this->typeSpecifier->specifyTypesInCondition($scope, $condition, TypeSpecifierContext::createTruthy());
    }

    /**
     * The condition that holds after the assertion, or null.
     *
     * @param Arg[] $args
     */
    private static function condition(string $method, array $args): ?Expr {
        switch (strtolower($method)) {
            case 'assertnotfalse':
                return isset($args[0]) ? new NotIdentical($args[0]->value, new ConstFetch(new Name('false'))) : null;
            case 'assertnotnull':
                return isset($args[0]) ? new NotIdentical($args[0]->value, new ConstFetch(new Name('null'))) : null;
            case 'asserttrue':
                return isset($args[0]) ? $args[0]->value : null;
            case 'assertfalse':
                return isset($args[0]) ? new BooleanNot($args[0]->value) : null;
            case 'assertinstanceof':
                if (isset($args[0], $args[1]) && $args[0]->value instanceof ClassConstFetch && $args[0]->value->class instanceof Name) {
                    return new Instanceof_($args[1]->value, $args[0]->value->class);
                }

                return null;
            case 'assertinternaltype':
                if (isset($args[0], $args[1]) && $args[0]->value instanceof String_ && isset(self::INTERNAL_TYPES[$args[0]->value->value])) {
                    return new FuncCall(new Name(self::INTERNAL_TYPES[$args[0]->value->value]), [$args[1]]);
                }

                return null;
        }

        return null;
    }
}
