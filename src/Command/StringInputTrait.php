<?php

namespace App\Command;

use Symfony\Component\Console\Input\InputInterface;

trait StringInputTrait {
    /**
     * The value of an argument that is not an array (IS_ARRAY): '' when it is not given.
     */
    private static function stringArgument(InputInterface $input, string $name): string {
        $value = $input->getArgument($name);
        if (is_array($value)) {
            throw new \InvalidArgumentException("The argument $name must be a single value.");
        }

        return (string) $value;
    }
}
