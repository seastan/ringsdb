<?php

declare(strict_types=1);

namespace App\Controller\API;

use App\Entity\Card;
use App\Entity\Decklist;
use App\Model\JsonpDto;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Decklist')]
class ListTopDecklistsByCardController extends AbstractController
{
    use JsonpTrait;

    public function __construct(
        private readonly int $cacheExpiration,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Top 10 Decklists containing a specific card.
     *
     * Get the top 10 decklists published containing given card, as an array of JSON objects.
     */
    #[OA\Parameter(name: 'card_code', description: "The code of the card, e.g. '01001'", in: 'path')]
    #[Route(path: '/api/public/decklists/top_by_card/{card_code}.{_format}', name: 'api_decklists_by_card', requirements: ['_format' => 'json'], defaults: ['_format' => 'json'], methods: ['GET'])]
    public function __invoke(Request $request, #[MapEntity(mapping: ['card_code' => 'code'])] ?Card $card, #[MapQueryString] JsonpDto $query = new JsonpDto()): Response
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->cacheExpiration);
        $response->headers->add(['Access-Control-Allow-Origin' => '*']);

        $jsonp = $query->jsonp;
        $format = $request->getRequestFormat();
        if ('json' !== $format) {
            $response->setContent($request->getRequestFormat().' format not supported. Only json is supported.');

            return $response;
        }

        if (!$card instanceof Card) {
            $response->setContent('[]');

            return $response;
        }

        $qb = $this->entityManager->createQueryBuilder();
        // Select decklists
        $qb->select('d.id, d.name, d.nameCanonical, d.dateCreation, d.dateUpdate');
        $qb->from(Decklist::class, 'd');
        // high popularity
        $qb->addSelect('(1+d.nbVotes)/(1+POWER(DATE_DIFF(CURRENT_TIMESTAMP(), d.dateCreation), 2)) AS HIDDEN popularity');
        $qb->orderBy('popularity', \SortDirection::Descending);
        $qb->addOrderBy('d.id', \SortDirection::Descending);
        // containing the card
        $qb->innerJoin('d.slots', 's');
        $qb->andWhere('s.card = :card');
        $qb->setParameter('card', $card);
        // limit 10
        $qb->setMaxResults(10);

        $query = $qb->getQuery();
        /* @var $decklists ArrayCollection */
        $decklists = $query->getArrayResult();
        $lastModified = null;
        foreach ($decklists as &$decklist) {
            if (!$lastModified || $lastModified < $decklist['dateUpdate']) {
                $lastModified = $decklist['dateUpdate'];
            }
        }

        $response->setLastModified($lastModified);
        if ($response->isNotModified($request)) {
            return $response;
        }

        foreach ($decklists as &$decklist) {
            $decklist['url'] = $this->generateUrl('decklist_detail', ['decklist_id' => $decklist['id'], 'decklist_name' => $decklist['nameCanonical']]);
            unset($decklist['descriptionMd']);
            unset($decklist['descriptionHtml']);
            $decklist['dateCreation'] = $decklist['dateCreation']->format('c');
            $decklist['dateUpdate'] = $decklist['dateUpdate']->format('c');
        }

        $content = json_encode($decklists);
        $this->setJsonContent($response, (string) $content, $jsonp);

        return $response;
    }
}
