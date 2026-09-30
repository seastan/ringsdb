<?php

namespace AppBundle\PHPStan;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

/**
 * The application only uses the Doctrine ORM: the "doctrine" registry (Registry, getDoctrine())
 * always returns ORM entity managers and repositories and DBAL connections, not the generic
 * ObjectManager / ObjectRepository / object declared by doctrine/common.
 * getRepository('AppBundle:Card') returns an EntityRepository<Card> (see
 * stubs/EntityRepository.stub) and, on the entity manager, find('AppBundle:Card', $id) a Card|null.
 *
 * Registered for the registry, the entity manager, and the object manager of the fixtures.
 */
class DoctrineRegistryReturnTypeExtension implements DynamicMethodReturnTypeExtension {
    const RETURN_TYPES = [
        'getManager' => 'Doctrine\ORM\EntityManager',
        'getManagerForClass' => 'Doctrine\ORM\EntityManager',
        'resetManager' => 'Doctrine\ORM\EntityManager',
        'getRepository' => 'Doctrine\ORM\EntityRepository',
        'getConnection' => 'Doctrine\DBAL\Connection',
        'find' => 'object',
    ];

    /** @var class-string */
    private $className;

    /**

     * @param class-string $className

     */

    public function __construct(string $className) {
        $this->className = $className;
    }

    public function getClass(): string {
        return $this->className;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool {
        return isset(self::RETURN_TYPES[$methodReflection->getName()]);
    }

    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): Type {
        $name = $methodReflection->getName();
        $args = $methodCall->getArgs();
        $entity = isset($args[0]) ? $scope->getType($args[0]->value) : null;
        // "AppBundle:Card" or "AppBundle\Entity\Card"
        $strings = $entity !== null ? $entity->getConstantStrings() : [];
        $class = count($strings) === 1 ? preg_replace('/^AppBundle:/', 'AppBundle\\Entity\\', $strings[0]->getValue()) : null;
        if ($name === 'getRepository' && $class !== null) {
            return new GenericObjectType(self::RETURN_TYPES[$name], [new ObjectType($class)]);
        }
        if ($name === 'find') {
            return TypeCombinator::addNull($class !== null ? new ObjectType($class) : new ObjectWithoutClassType());
        }

        return new ObjectType(self::RETURN_TYPES[$name]);
    }
}
