<?php

namespace AppBundle\PHPStan;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

/**
 * $container->get('service_id') and $this->get('service_id') in controllers return the class of the
 * service, read from the container dumped by Symfony in debug mode (app/cache/test/*.xml: run the
 * tests or "app/console cache:warmup --env=test" first).
 */
class SymfonyContainerReturnTypeExtension implements DynamicMethodReturnTypeExtension {
    /** @var class-string */
    private $className;

    /** @var string */
    private $containerXml;

    /** @var array<string, string>|null service id => class */
    private $services;

    /**

     * @param class-string $className

     */

    public function __construct(string $className, string $containerXml) {
        $this->className = $className;
        $this->containerXml = $containerXml;
    }

    public function getClass(): string {
        return $this->className;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool {
        return $methodReflection->getName() === 'get';
    }

    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): Type {
        $default = ParametersAcceptorSelector::selectFromArgs($scope, $methodCall->getArgs(), $methodReflection->getVariants())->getReturnType();
        $args = $methodCall->getArgs();
        if (!isset($args[0])) {
            return $default;
        }
        $ids = $scope->getType($args[0]->value)->getConstantStrings();
        if (count($ids) !== 1) {
            return $default;
        }
        $services = $this->services();
        $class = $services[strtolower($ids[0]->getValue())] ?? null;

        return $class !== null ? new ObjectType($class) : $default;
    }

    /**
     * @return array<string, string>
     */
    private function services(): array {
        if ($this->services !== null) {
            return $this->services;
        }
        $this->services = [];
        if (!is_file($this->containerXml)) {
            return $this->services;
        }
        $xml = simplexml_load_file($this->containerXml);
        if ($xml === false) {
            return $this->services;
        }
        // synthetic services, without a class in the dump
        $classes = [
            'kernel' => 'Symfony\Component\HttpKernel\KernelInterface',
            'service_container' => 'Symfony\Component\DependencyInjection\ContainerInterface',
        ];
        $aliases = [];
        foreach ($xml->services->service as $service) {
            $id = strtolower((string) $service['id']);
            if (isset($service['alias'])) {
                $aliases[$id] = strtolower((string) $service['alias']);
            } elseif (isset($service['class']) && strpos((string) $service['class'], '%') === false) {
                $classes[$id] = ltrim((string) $service['class'], '\\');
            }
        }
        foreach ($aliases as $id => $target) {
            for ($i = 0; isset($aliases[$target]) && $i < 10; $i++) {
                $target = $aliases[$target];
            }
            if (isset($classes[$target])) {
                $classes[$id] = $classes[$target];
            }
        }

        return $this->services = $classes;
    }
}
