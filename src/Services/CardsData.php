<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Card;
use App\Entity\Review;
use App\Entity\Sphere;
use App\Repository\CardRepository;
use App\Repository\CycleRepository;
use App\Repository\ReviewRepository;
use App\Repository\SphereRepository;
use App\Search\SearchKeys;
use App\Search\SearchTypes;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CardsData
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var UrlGeneratorInterface
     */
    private $router;

    /**
     * @var Packages
     */
    private $assets_packages;

    /**
     * @var string
     */
    private $publicDir;

    /**
     * @var CardRepository
     */
    private $cardRepository;

    /**
     * @var CycleRepository
     */
    private $cycleRepository;

    /**
     * @var ReviewRepository
     */
    private $reviewRepository;

    /**
     * @var SphereRepository
     */
    private $sphereRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        UrlGeneratorInterface $router,
        Packages $assets_packages,
        string $publicDir,
        CardRepository $cardRepository,
        CycleRepository $cycleRepository,
        ReviewRepository $reviewRepository,
        SphereRepository $sphereRepository
    ) {
        $this->entityManager = $entityManager;
        $this->router = $router;
        $this->assets_packages = $assets_packages;
        $this->publicDir = $publicDir;
        $this->cardRepository = $cardRepository;
        $this->cycleRepository = $cycleRepository;
        $this->reviewRepository = $reviewRepository;
        $this->sphereRepository = $sphereRepository;
    }

    /**
     * Searches for and replaces symbol tokens with markup in a given text.
     *
     * @param string $text
     */
    public function replaceSymbols($text): string
    {
        /** @var array<string, string> $displayTextReplacements */
        static $displayTextReplacements = [
            '[willpower]' => '<span class="icon-willpower"></span>',
            '[attack]' => '<span class="icon-attack"></span>',
            '[defense]' => '<span class="icon-defense"></span>',
            '[threat]' => '<span class="icon-threat"></span>',
            '[spirit]' => '<span class="icon-spirit"></span>',
            '[leadership]' => '<span class="icon-leadership"></span>',
            '[tactics]' => '<span class="icon-tactics"></span>',
            '[lore]' => '<span class="icon-lore"></span>',
            '[neutral]' => '<span class="icon-neutral"></span>',
            '[baggins]' => '<span class="icon-baggins"></span>',
            '[fellowship]' => '<span class="icon-fellowship"></span>',
            '[unique]' => '<span class="icon-unique"></span>',
        ];

        return str_replace(array_keys($displayTextReplacements), array_values($displayTextReplacements), $text);
    }

    public function splitInParagraphs(string $text): string
    {
        if (empty($text)) {
            return '';
        }

        return implode('', array_map(fn ($l): string => "<p>$l</p>", preg_split('/[\r?\n]+/', $text) ?: []));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function allSetsData(): array
    {
        $list_cycles = $this->cycleRepository->findBy([], ['position' => 'ASC']);
        $cycles = [];

        foreach ($list_cycles as $cycle) {
            $packs = [];
            $sreal = 0;
            $smax = 0;

            foreach ($cycle->getPacks() as $pack) {
                // count via printings: a repackaged pack's cards live as printings of
                // canonical cards in other packs, so getCards() would be empty for it.
                $real = count($pack->getPrintings());
                $sreal += $real;
                $max = $pack->getSize();
                $smax += $max;

                $packs[] = [
                    'label' => $pack->getName(),
                    'code' => $pack->getCode(),
                    'available' => $pack->getDateRelease() ? $pack->getDateRelease()->format('Y-m-d') : '',
                    'known' => intval($real),
                    'total' => $max,
                    'url' => $this->router->generate('cards_list', ['pack_code' => $pack->getCode()], UrlGeneratorInterface::ABSOLUTE_URL),
                    'search' => 'e:'.$pack->getCode(),
                    'packs' => '',
                ];
            }

            if (1 == count($packs) && $packs[0]['label'] == $cycle->getName()) {
                $cycles[] = $packs[0];
            } else {
                $cycles[] = [
                    'label' => $cycle->getName(),
                    'code' => $cycle->getCode(),
                    'known' => intval($sreal),
                    'total' => $smax,
                    'url' => $this->router->generate('cards_cycle', ['cycle_code' => $cycle->getCode()], UrlGeneratorInterface::ABSOLUTE_URL),
                    'search' => 'c:'.$cycle->getCode(),
                    'packs' => $packs,
                ];
            }
        }

        return $cycles;
    }

    /**
     * @return array<int, Sphere>
     */
    public function getPrimarySpheres(): array
    {
        $spheres = $this->sphereRepository->findBy(['is_primary' => true], ['code' => 'ASC']);

        return $spheres;
    }

    /**
     * @param array<array<string>> $conditions
     *
     * @return array<int, Card>
     */
    public function get_search_rows(array $conditions, string $sortorder, bool $forceempty = false): array
    {
        $i = 0;

        $qb = $this->cardRepository->createQueryBuilder('c');
        $qb->leftJoin('c.type', 't')->leftJoin('c.sphere', 's');
        $qb2 = null;
        $qb3 = null;

        foreach ($conditions as $condition) {
            $searchCode = array_shift($condition);
            $operator = array_shift($condition);

            $searchName = '';
            if (isset(SearchKeys::$searchKeys[$searchCode])) {
                $searchName = SearchKeys::$searchKeys[$searchCode];
            }

            $searchType = '';
            if (isset(SearchTypes::$searchTypes[$searchCode])) {
                $searchType = SearchTypes::$searchTypes[$searchCode];
            }

            switch ($searchType) {
                case 'boolean':
                    switch ($searchCode) {
                        default:
                            if ((':' == $operator && $condition[0]) || ('!' == $operator && !$condition[0])) {
                                $qb->andWhere("(c.$searchName = 1)");
                            } else {
                                $qb->andWhere("(c.$searchName = 0)");
                            }
                            ++$i;
                            break;
                    }
                    break;

                case 'integer':
                    switch ($searchCode) {
                        default:
                            $or = [];
                            foreach ($condition as $arg) {
                                switch ($operator) {
                                    case ':':
                                        $or[] = "(c.$searchName = ?$i)";
                                        break;
                                    case '!':
                                        $or[] = "(c.$searchName != ?$i)";
                                        break;
                                    case '<':
                                        $or[] = "(c.$searchName < ?$i)";
                                        break;
                                    case '>':
                                        $or[] = "(c.$searchName > ?$i)";
                                        break;
                                }
                                $qb->setParameter($i++, $arg);
                            }
                            $qb->andWhere(implode('!' == $operator ? ' and ' : ' or ', $or));
                            break;
                    }
                    break;

                case 'code':
                    switch ($searchCode) {
                        case 'c':
                            // cycle filter: card has a printing in a pack of this cycle (any printing)
                            $or = [];
                            foreach ($condition as $arg) {
                                $sub = "SELECT cpc{$i}.id FROM App:CardPrinting cpc{$i} JOIN cpc{$i}.pack ppc{$i} JOIN ppc{$i}.cycle yc{$i} WHERE cpc{$i}.card = c AND yc{$i}.code = ?$i";
                                switch ($operator) {
                                    case ':':
                                        $or[] = "EXISTS ($sub)";
                                        break;
                                    case '!':
                                        $or[] = "NOT EXISTS ($sub)";
                                        break;
                                }
                                $qb->setParameter($i++, $arg);
                            }
                            $qb->andWhere(implode('!' == $operator ? ' and ' : ' or ', $or));
                            break;

                        case 'e':
                            // pack filter: card has a printing in this pack (any printing)
                            $or = [];
                            foreach ($condition as $arg) {
                                $base = "SELECT cpe{$i}.id FROM App:CardPrinting cpe{$i} JOIN cpe{$i}.pack ppe{$i} WHERE cpe{$i}.card = c";
                                switch ($operator) {
                                    case ':':
                                        $or[] = "EXISTS ($base AND ppe{$i}.code = ?$i)";
                                        break;
                                    case '!':
                                        $or[] = "NOT EXISTS ($base AND ppe{$i}.code = ?$i)";
                                        break;
                                    case '<':
                                        $or[] = "EXISTS ($base AND ppe{$i}.dateRelease < (SELECT p2{$i}.dateRelease FROM App:Pack p2{$i} WHERE p2{$i}.code = ?$i))";
                                        break;
                                    case '>':
                                        $or[] = "EXISTS ($base AND ppe{$i}.dateRelease > (SELECT p3{$i}.dateRelease FROM App:Pack p3{$i} WHERE p3{$i}.code = ?$i))";
                                        break;
                                }
                                $qb->setParameter($i++, $arg);
                            }
                            $qb->andWhere(implode('!' == $operator ? ' and ' : ' or ', $or));
                            break;

                        default:
                            // type and sphere
                            $or = [];
                            foreach ($condition as $arg) {
                                switch ($operator) {
                                    case ':':
                                        $or[] = "($searchCode.code = ?$i)";
                                        break;
                                    case '!':
                                        $or[] = "($searchCode.code != ?$i)";
                                        break;
                                }
                                $qb->setParameter($i++, $arg);
                            }
                            $qb->andWhere(implode('!' == $operator ? ' and ' : ' or ', $or));
                            break;
                    }
                    break;

                case 'date':
                case 'string':
                    switch ($searchCode) {
                        case '':
                            // name or index
                            $or = [];
                            foreach ($condition as $arg) {
                                $code = preg_match('/^\d{5,8}$/u', $arg);
                                $acronym = preg_match('/^[A-Z]{2,}$/', $arg);
                                if ($code) {
                                    $or[] = "(c.code = ?$i)";
                                    $qb->setParameter($i++, $arg);
                                } else {
                                    if ($acronym) {
                                        $like = implode('% ', str_split($arg));
                                        $or[] = "(REPLACE(c.name, '-', ' ') like ?$i)";
                                        $qb->setParameter($i++, "$like%");
                                    } else {
                                        $or[] = "(c.name like ?$i)";
                                        $qb->setParameter($i++, "%$arg%");
                                    }
                                }
                            }
                            $qb->andWhere(implode(' or ', $or));
                            break;

                        case 'x':
                            // text
                            $or = [];
                            foreach ($condition as $arg) {
                                switch ($operator) {
                                    case ':':
                                        $or[] = "(c.text like ?$i)";
                                        break;
                                    case '!':
                                        $or[] = "(c.text not like ?$i)";
                                        break;
                                }
                                $qb->setParameter($i++, "%$arg%");
                            }
                            $qb->andWhere(implode('!' == $operator ? ' and ' : ' or ', $or));
                            break;

                        case 'f':
                            // flavor
                            $or = [];
                            foreach ($condition as $arg) {
                                switch ($operator) {
                                    case ':':
                                        $or[] = "(c.flavor like ?$i)";
                                        break;
                                    case '!':
                                        $or[] = "(c.flavor not like ?$i)";
                                        break;
                                }
                                $qb->setParameter($i++, "%$arg%");
                            }
                            $qb->andWhere(implode('!' == $operator ? ' and ' : ' or ', $or));
                            break;

                        case 'k':
                            // subtype (traits)
                            $or = [];
                            foreach ($condition as $arg) {
                                switch ($operator) {
                                    case ':':
                                        $or[] = "((c.traits = ?$i) or (c.traits like ?".($i + 1).') or (c.traits like ?'.($i + 2).') or (c.traits like ?'.($i + 3).'))';
                                        $qb->setParameter($i++, "$arg.");
                                        $qb->setParameter($i++, "$arg. %");
                                        $qb->setParameter($i++, "%. $arg.");
                                        $qb->setParameter($i++, "%. $arg. %");
                                        break;
                                    case '!':
                                        $or[] = "(c.traits is null or ((c.traits != ?$i) and (c.traits not like ?".($i + 1).') and (c.traits not like ?'.($i + 2).') and (c.traits not like ?'.($i + 3).')))';
                                        $qb->setParameter($i++, "$arg.");
                                        $qb->setParameter($i++, "$arg. %");
                                        $qb->setParameter($i++, "%. $arg.");
                                        $qb->setParameter($i++, "%. $arg. %");
                                        break;
                                }
                            }
                            $qb->andWhere(implode('!' == $operator ? ' and ' : ' or ', $or));
                            break;

                        case 'i':
                            // illustrator — search via CardPrinting (field moved off Card in Phase 9)
                            $or = [];
                            foreach ($condition as $arg) {
                                $sub = "SELECT cpi{$i}.id FROM App:CardPrinting cpi{$i} WHERE cpi{$i}.card = c AND cpi{$i}.illustrator = ?$i";
                                switch ($operator) {
                                    case ':':
                                        $or[] = "EXISTS ($sub)";
                                        break;
                                    case '!':
                                        $or[] = "NOT EXISTS ($sub)";
                                        break;
                                }
                                $qb->setParameter($i++, $arg);
                            }
                            $qb->andWhere(implode('!' == $operator ? ' and ' : ' or ', $or));
                            break;

                        case 'r':
                            // release: card has a printing released by / after this date (any printing)
                            $or = [];
                            foreach ($condition as $arg) {
                                $base = "SELECT cpr{$i}.id FROM App:CardPrinting cpr{$i} JOIN cpr{$i}.pack ppr{$i} WHERE cpr{$i}.card = c";
                                switch ($operator) {
                                    case '<':
                                        $or[] = "EXISTS ($base AND ppr{$i}.dateRelease <= ?$i)";
                                        break;
                                    case '>':
                                        $or[] = "EXISTS ($base AND (ppr{$i}.dateRelease > ?$i OR ppr{$i}.dateRelease IS NULL))";
                                        break;
                                }

                                if ('now' == $arg) {
                                    $qb->setParameter($i++, new \DateTime());
                                } else {
                                    $qb->setParameter($i++, new \DateTime($arg));
                                }
                            }
                            $qb->andWhere(implode(' or ', $or));
                            break;
                    }
                    break;
            }
        }

        if (!$i && !$forceempty) {
            return [];
        }

        switch ($sortorder) {
            case 'set':
                $qb->orderBy('c.code');
                break;
            case 'sphere':
                $qb->orderBy('c.sphere')->addOrderBy('c.type');
                break;
            case 'type':
                $qb->orderBy('c.type')->addOrderBy('c.sphere');
                break;
            case 'cost':
                $qb->orderBy('c.cost')->addOrderBy('c.threat');
                break;
            case 'attack':
                $qb->orderBy('c.attack', 'DESC');
                break;
            case 'willpower':
                $qb->orderBy('c.willpower', 'DESC');
                break;
            case 'defense':
                $qb->orderBy('c.defense', 'DESC');
                break;
            case 'health':
                $qb->orderBy('c.health', 'DESC');
                break;
        }
        $qb->addOrderBy('c.name');
        $qb->addOrderBy('c.code');
        $query = $qb->getQuery();
        $rows = $query->getResult();

        return $rows;
    }

    /**
     * @param Card $card
     * @param bool $api
     *
     * @return array<string, mixed>
     */
    public function getCardInfo($card, $api = false): array
    {
        $cardinfo = [];

        $metadata = $this->entityManager->getClassMetadata(Card::class);
        $fieldNames = $metadata->getFieldNames();
        $associationMappings = $metadata->getAssociationMappings();

        foreach ($associationMappings as $fieldName => $associationMapping) {
            if ($associationMapping['isOwningSide']) {
                $getter = str_replace(' ', '', ucwords(str_replace('_', ' ', "get_$fieldName")));
                $associationEntity = $card->$getter();
                if (!$associationEntity) {
                    continue;
                }

                $cardinfo[$fieldName.'_code'] = $associationEntity->getCode();
                $cardinfo[$fieldName.'_name'] = $associationEntity->getName();
            }
        }

        foreach ($fieldNames as $fieldName) {
            $getter = str_replace(' ', '', ucwords(str_replace('_', ' ', "get_$fieldName")));
            $value = $card->$getter();
            switch ($metadata->getTypeOfField($fieldName)) {
                case 'datetime':
                case 'date':
                    continue 2;
                case 'boolean':
                    $value = (bool) $value;
                    break;
            }
            $fieldName = ltrim(strtolower((string) preg_replace('/[A-Z]/', '_$0', $fieldName)), '_');
            $cardinfo[$fieldName] = $value;
        }

        // Fields removed from Card ORM in Phase 9 — supply from the primary printing.
        $primaryPrinting = $card->getPrimaryPrinting();
        $primaryPack = $primaryPrinting ? $primaryPrinting->getPack() : null;
        $cardinfo['pack_code'] = $primaryPack ? $primaryPack->getCode() : null;
        $cardinfo['pack_name'] = $primaryPack ? $primaryPack->getName() : null;
        $cardinfo['illustrator'] = $primaryPrinting ? $primaryPrinting->getIllustrator() : null;
        $cardinfo['octgnid'] = $primaryPrinting ? $primaryPrinting->getOctgnid() : null;
        $cardinfo['quantity'] = $primaryPrinting ? intval($primaryPrinting->getQuantity()) : null;

        $cardinfo['url'] = $this->router->generate('cards_zoom', ['card_code' => $card->getCode()], UrlGeneratorInterface::ABSOLUTE_URL);
        $imageurl = $this->assets_packages->getUrl('bundles/cards/'.$card->getCode().'.png');
        $imagepath = $this->publicDir.preg_replace('/\?.*/', '', $imageurl);

        if (file_exists($imagepath)) {
            $cardinfo['imagesrc'] = $imageurl;
        } else {
            $cardinfo['imagesrc'] = null;
        }

        // All printings of this card (one per pack it appears in). pack_code/pack_name
        // above stay as the canonical/primary printing for backward compatibility.
        $cardinfo['packs'] = [];
        foreach ($card->getPrintings() as $printing) {
            $pack = $printing->getPack();
            if (!$pack) {
                continue;
            }

            $prImageUrl = $this->assets_packages->getUrl('bundles/cards/'.$printing->getImageCode().'.png');
            $prImagePath = $this->publicDir.preg_replace('/\?.*/', '', $prImageUrl);
            $dateRelease = $pack->getDateRelease();

            $cardinfo['packs'][] = [
                'pack_code' => $pack->getCode(),
                'pack_name' => $pack->getName(),
                'position' => $printing->getPosition(),
                'quantity' => intval($printing->getQuantity()),
                'image_code' => $printing->getImageCode(),
                'illustrator' => $printing->getIllustrator(),
                'octgnid' => $printing->getOctgnid(),
                'imagesrc' => file_exists($prImagePath) ? $prImageUrl : null,
                'date_release' => $dateRelease ? $dateRelease->format('Y-m-d') : null,
            ];
        }
        usort($cardinfo['packs'], function ($a, $b) {
            if (null === $a['date_release'] && null === $b['date_release']) {
                return 0;
            }
            if (null === $a['date_release']) {
                return 1;
            }
            if (null === $b['date_release']) {
                return -1;
            }

            return strcmp((string) $a['date_release'], (string) $b['date_release']);
        });

        if ($api) {
            unset($cardinfo['id']);
            $cardinfo = array_filter($cardinfo, fn ($var): bool => isset($var));
        } else {
            $cardinfo['text'] = $this->replaceSymbols($cardinfo['text']);
            $cardinfo['text'] = $this->splitInParagraphs($cardinfo['text']);
            $cardinfo['flavor'] = $this->replaceSymbols($cardinfo['flavor']);
        }

        return $cardinfo;
    }

    /**
     * @return list<array<int, string>>
     */
    public function syntax(string $query): array
    {
        // renvoie une liste de conditions (array)
        // chaque condition est un tableau à n>1 éléments
        // le premier est le type de condition (0 ou 1 caractère)
        // les suivants sont les arguments, en OR

        $query = preg_replace('/\s+/u', ' ', trim($query));

        $list = [];
        // an empty condition, never kept
        $cond = ['', ':'];
        // l'automate a 3 états :
        // 1:recherche de type
        // 2:recherche d'argument principal
        // 3:recherche d'argument supplémentaire
        // 4:erreur de parsing, on recherche la prochaine condition
        // s'il tombe sur un argument alors qu'il est en recherche de type, alors le type est vide
        $etat = 1;
        while ('' != $query) {
            if (1 == $etat) {
                if (count($cond) > 2) {
                    $list[] = $cond;
                }
                // on commence par rechercher un type de condition
                $match = [];
                if (preg_match('/^(\p{L})([:<>!])(.*)/u', $query, $match)) { // jeton "condition:"
                    $cond = [mb_strtolower($match[1]), $match[2]];
                    $query = $match[3];
                } else {
                    $cond = ['', ':'];
                }
                $etat = 2;
            } else {
                if (preg_match('/^"([^"]*)"(.*)/u', $query, $match) // jeton "texte libre entre guillements"
                    || preg_match('/^([\p{L}\p{N}\-\&]+)(.*)/u', $query, $match) // jeton "texte autorisé sans guillements"
                ) {
                    if ((2 == $etat && 2 == count($cond)) || 3 == $etat) {
                        $cond[] = $match[1];
                        $query = $match[2];
                        $etat = 2;
                    } else {
                        // erreur
                        $query = $match[2];
                        $etat = 4;
                    }
                } else {
                    if (preg_match('/^\|(.*)/u', $query, $match)) { // jeton "|"
                        if ((':' == $cond[1] || '!' == $cond[1]) && ((2 == $etat && count($cond) > 2) || 3 == $etat)) {
                            $query = $match[1];
                            $etat = 3;
                        } else {
                            // erreur
                            $query = $match[1];
                            $etat = 4;
                        }
                    } else {
                        if (preg_match('/^ (.*)/u', $query, $match)) { // jeton " "
                            $query = $match[1];
                            $etat = 1;
                        } else {
                            // erreur
                            $query = substr($query, 1);
                            $etat = 4;
                        }
                    }
                }
            }
        }
        if (4 != $etat && count($cond) > 2) {
            $list[] = $cond;
        }

        return $list;
    }

    /**
     * @param list<array<int, string>> $conditions
     *
     * @return array<int, mixed>
     */
    public function validateConditions(array $conditions): array
    {
        // suppression des conditions invalides
        $numeric = ['<', '>'];

        foreach ($conditions as $i => $l) {
            $searchCode = $l[0];
            $searchOp = $l[1];

            if (in_array($searchOp, $numeric) && 'integer' !== SearchTypes::$searchTypes[$searchCode] && 'date' !== SearchTypes::$searchTypes[$searchCode]) {
                // operator is numeric but searched property is not
                unset($conditions[$i]);
            }
        }

        return array_values($conditions);
    }

    /**
     * @param array<array<string>> $conditions
     */
    public function buildQueryFromConditions(array $conditions): string
    {
        return implode(
            ' ',
            array_map(
                fn ($l): string => ($l[0] ? $l[0].$l[1] : '')
                    .implode(
                        '|',
                        array_map(
                            fn ($s) => preg_match("/^[\p{L}\p{N}\-\&]+$/u", $s)
                                ? $s
                                : "\"$s\"",
                            array_slice($l, 2)
                        )
                    ),
                $conditions
            )
        );
    }

    /**
     * @return array<int, Review>
     */
    public function getReviews(Card $card): array
    {
        return $this->reviewRepository->findBy(['card' => $card], ['nbVotes' => 'DESC', 'id' => 'ASC']);
    }

    /**
     * @return array<string, int>
     */
    public function getDistinctTraits(): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->from('App:Card', 'c');
        $qb->select('c.traits');
        $qb->distinct();
        $result = $qb->getQuery()->getResult();

        $traits = [];
        foreach ($result as $card) {
            $subs = explode('.', (string) $card['traits']);
            foreach ($subs as $sub) {
                $traits[trim($sub)] = 1;
            }
        }

        return $traits;
    }
}
