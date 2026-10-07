<?php

declare(strict_types=1);

namespace App\Controller\Decklist;

use App\Entity\Cycle;
use App\Repository\CycleRepository;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SearchDecklistController extends AbstractController
{
    public function __construct(
        private readonly int $cacheExpiration,
        private readonly Connection $connection,
        private readonly CycleRepository $cycleRepository
    ) {
    }

    /**
     * @Route("/decklists/search", name="decklists_searchform", methods={"GET"}, priority="2")
     */
    public function __invoke(Request $request): Response
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->cacheExpiration);

        $spheres = $this->connection->executeQuery('SELECT s.name, s.code FROM sphere s ORDER BY s.name ASC')->fetchAllAssociative();
        $owned_packs = '';
        if ($this->getUser() instanceof \Symfony\Component\Security\Core\User\UserInterface) {
            $owned_packs = $this->getUser()->getOwnedPacks();
        }

        if ($owned_packs) {
            // owned_packs is a per-pack count map ("id" / "id:count", legacy "id-2");
            // keep the ids whose count is > 0.
            $packs = [];
            foreach (explode(',', $owned_packs) as $token) {
                if (preg_match('/^(\\d+)(?:[:-](\\d+))?$/', trim($token), $m)) {
                    if (!isset($m[2]) || (int) $m[2] > 0) {
                        $packs[] = (int) $m[1];
                    }
                }
            }
        } else {
            $packs = $this->connection->executeQuery('SELECT id FROM pack WHERE date_release IS NOT NULL')->fetchFirstColumn();
        }

        $categories = [];
        $on = 0;
        $off = 0;
        $categories[] = ['label' => 'Core / Deluxe', 'packs' => []];
        $list_cycles = $this->cycleRepository->findBy([], ['position' => 'ASC']);
        foreach ($list_cycles as $cycle) {
            /* @var $cycle Cycle */
            $size = count($cycle->getPacks());
            $first_pack = $cycle->getPacks()->first();
            if (0 == $cycle->getPosition() || false === $first_pack) {
                continue;
            }

            if (1 === $size && $first_pack->getName() == $cycle->getName()) {
                $checked = count($packs) ? in_array($first_pack->getId(), $packs) : true;
                if ($checked) {
                    ++$on;
                } else {
                    ++$off;
                }

                $categories[0]['packs'][] = ['id' => $first_pack->getId(), 'label' => $first_pack->getName(), 'checked' => $checked, 'future' => null === $first_pack->getDateRelease()];
            } else {
                $category = ['label' => $cycle->getName(), 'packs' => []];
                foreach ($cycle->getPacks() as $pack) {
                    $checked = count($packs) ? in_array($pack->getId(), $packs) : true;
                    if ($checked) {
                        ++$on;
                    } else {
                        ++$off;
                    }

                    $category['packs'][] = ['id' => $pack->getId(), 'label' => $pack->getName(), 'checked' => $checked, 'future' => null === $pack->getDateRelease()];
                }

                $categories[] = $category;
            }
        }

        $searchForm = $this->renderView('Search/form.html.twig', ['spheres' => $spheres, 'allowed' => $categories, 'on' => $on, 'off' => $off, 'author' => '', 'name' => '', 'threat' => '', 'threato' => '', 'reputation' => '', 'reputationo' => '>', 'numcores' => '3', 'require_description' => 0]);

        return $this->render('Decklist/decklists.html.twig', ['pagetitle' => 'Decklist Search', 'decklists' => null, 'url' => $request->getRequestUri(), 'header' => $searchForm, 'type' => 'find', 'pages' => null, 'prevurl' => null, 'nexturl' => null], $response);
    }
}
