<?php

declare(strict_types=1);

namespace App\Model;

use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * The query string of the card search form: a free query, plus one field per search key (see
 * SearchKeys) with its operator (the key followed by "o"); the spheres are a list.
 */
final readonly class ProcessSearchDto
{
    /**
     * @param array<mixed> $sphere
     */
    public function __construct(
        public ?string $q = null,
        public ?string $view = null,
        public ?string $sort = null,
        #[SerializedName('s')]
        public array $sphere = [],
        #[SerializedName('a')]
        public ?string $attack = null,
        #[SerializedName('ao')]
        public ?string $attackOperator = null,
        #[SerializedName('b')]
        public ?string $threat = null,
        #[SerializedName('bo')]
        public ?string $threatOperator = null,
        #[SerializedName('c')]
        public ?string $cycle = null,
        #[SerializedName('co')]
        public ?string $cycleOperator = null,
        #[SerializedName('d')]
        public ?string $defense = null,
        #[SerializedName('do')]
        public ?string $defenseOperator = null,
        #[SerializedName('e')]
        public ?string $pack = null,
        #[SerializedName('eo')]
        public ?string $packOperator = null,
        #[SerializedName('f')]
        public ?string $flavor = null,
        #[SerializedName('fo')]
        public ?string $flavorOperator = null,
        #[SerializedName('h')]
        public ?string $health = null,
        #[SerializedName('ho')]
        public ?string $healthOperator = null,
        #[SerializedName('i')]
        public ?string $illustrator = null,
        #[SerializedName('io')]
        public ?string $illustratorOperator = null,
        #[SerializedName('k')]
        public ?string $traits = null,
        #[SerializedName('ko')]
        public ?string $traitsOperator = null,
        #[SerializedName('o')]
        public ?string $cost = null,
        #[SerializedName('oo')]
        public ?string $costOperator = null,
        #[SerializedName('t')]
        public ?string $type = null,
        #[SerializedName('to')]
        public ?string $typeOperator = null,
        #[SerializedName('u')]
        public ?string $isUnique = null,
        #[SerializedName('uo')]
        public ?string $isUniqueOperator = null,
        #[SerializedName('w')]
        public ?string $willpower = null,
        #[SerializedName('wo')]
        public ?string $willpowerOperator = null,
        #[SerializedName('x')]
        public ?string $text = null,
        #[SerializedName('xo')]
        public ?string $textOperator = null,
        #[SerializedName('y')]
        public ?string $quantity = null,
        #[SerializedName('yo')]
        public ?string $quantityOperator = null,
        #[SerializedName('z')]
        public ?string $hasErrata = null,
        #[SerializedName('zo')]
        public ?string $hasErrataOperator = null,
    ) {
    }

    /**
     * The value of a search key, null when it is absent (always for the empty key of the code: a
     * query parameter without a name is dropped).
     */
    public function value(string $key): ?string
    {
        return match ($key) {
            'a' => $this->attack,
            'b' => $this->threat,
            'c' => $this->cycle,
            'd' => $this->defense,
            'e' => $this->pack,
            'f' => $this->flavor,
            'h' => $this->health,
            'i' => $this->illustrator,
            'k' => $this->traits,
            'o' => $this->cost,
            't' => $this->type,
            'u' => $this->isUnique,
            'w' => $this->willpower,
            'x' => $this->text,
            'y' => $this->quantity,
            'z' => $this->hasErrata,
            default => null,
        };
    }

    /**
     * The operator of a search key, null when it is absent.
     */
    public function operator(string $key): ?string
    {
        return match ($key) {
            'a' => $this->attackOperator,
            'b' => $this->threatOperator,
            'c' => $this->cycleOperator,
            'd' => $this->defenseOperator,
            'e' => $this->packOperator,
            'f' => $this->flavorOperator,
            'h' => $this->healthOperator,
            'i' => $this->illustratorOperator,
            'k' => $this->traitsOperator,
            'o' => $this->costOperator,
            't' => $this->typeOperator,
            'u' => $this->isUniqueOperator,
            'w' => $this->willpowerOperator,
            'x' => $this->textOperator,
            'y' => $this->quantityOperator,
            'z' => $this->hasErrataOperator,
            default => null,
        };
    }
}
