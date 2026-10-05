<?php

namespace App\Controller\CardSearch;

use App\Entity\Cycle;
use App\Entity\Pack;
use App\Repository\CycleRepository;
use App\Repository\PackRepository;
use App\Repository\SphereRepository;
use App\Repository\TypeRepository;
use App\Services\CardsData;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SearchFormController extends AbstractController
{
    private int $cacheExpiration;

    private PackRepository $packRepository;

    private CycleRepository $cycleRepository;

    private SphereRepository $sphereRepository;

    private CardsData $cardsData;

    private Connection $connection;

    public function __construct(
        int $cacheExpiration,
        PackRepository $packRepository,
        CycleRepository $cycleRepository,
        SphereRepository $sphereRepository,
        CardsData $cardsData,
        Connection $connection
    ) {
        $this->cacheExpiration = $cacheExpiration;
        $this->packRepository = $packRepository;
        $this->cycleRepository = $cycleRepository;
        $this->sphereRepository = $sphereRepository;
        $this->cardsData = $cardsData;
        $this->connection = $connection;
    }

    /**
     * @Route("/search", name="cards_search")
     */
    public function formAction(TypeRepository $typeRepository): Response
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->cacheExpiration);

        $list_packs = $this->packRepository->findBy([], ['dateRelease' => 'ASC', 'position' => 'ASC']);
        $packs = [];
        foreach ($list_packs as $pack) {
            /* @var $pack Pack */
            $packs[] = ['name' => $pack->getName(), 'code' => $pack->getCode()];
        }

        $list_cycles = $this->cycleRepository->findBy([], ['position' => 'ASC']);
        $cycles = [];
        foreach ($list_cycles as $cycle) {
            /* @var $cycle Cycle */
            $cycles[] = ['name' => $cycle->getName(), 'code' => $cycle->getCode()];
        }

        $types = $typeRepository->findBy([], ['name' => 'ASC']);
        $spheres = $this->sphereRepository->findBy([], ['id' => 'ASC']);
        $traits = $this->cardsData->getDistinctTraits();
        $traits = array_filter(array_keys($traits));
        sort($traits);
        $list_illustrators = $this->connection->executeQuery("SELECT DISTINCT illustrator FROM card_printing WHERE illustrator IS NOT NULL AND illustrator != '' ORDER BY illustrator")->fetchAll();
        $illustrators = array_map(fn ($card) => $card['illustrator'], $list_illustrators);

        return $this->render('Search/searchform.html.twig', ['pagetitle' => 'Card Search', 'pagedescription' => 'Find all the cards of the game, easily searchable.', 'packs' => $packs, 'cycles' => $cycles, 'types' => $types, 'spheres' => $spheres, 'traits' => $traits, 'illustrators' => $illustrators, 'allsets' => $this->renderView('Default/allsets.html.twig', ['data' => $this->cardsData->allSetsData()])], $response);
    }
}
