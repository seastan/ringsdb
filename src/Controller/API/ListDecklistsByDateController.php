<?php

declare(strict_types=1);

namespace App\Controller\API;

use App\Entity\Card;
use App\Entity\Decklist;
use App\Entity\Pack;
use App\Model\JsonpDto;
use App\Repository\CardRepository;
use App\Repository\DecklistRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

class ListDecklistsByDateController extends AbstractController
{
    use JsonpTrait;

    public function __construct(
        private readonly int $cacheExpiration,
        private readonly CardRepository $cardRepository,
        private readonly DecklistRepository $decklistRepository
    ) {
    }

    /**
     * Get the description of all the decklists published at a given date, as an array of JSON objects.
     *
     * ApiDoc(
     *  section="Decklist",
     *  resource=true,
     *  description="All the Decklists from One Day",
     *  parameters={
     *      {"name"="jsonp", "dataType"="string", "required"=false, "description"="JSONP callback"}
     *  },
     *  requirements={
     *      {
     *          "name"="date",
     *          "dataType"="string",
     *          "requirement"="\d\d\d\d-\d\d-\d\d",
     *          "description"="The date, format 'Y-m-d'"
     *      },
     *      {
     *          "name"="_format",
     *          "dataType"="string",
     *          "requirement"="json",
     *          "description"="The format of the returned data. Only 'json' is supported at the moment."
     *      }
     *  },
     * )
     */
    #[Route(path: '/api/public/decklists/by_date/{date}.{_format}', name: 'api_decklists_by_date', requirements: ['_format' => 'json', 'date' => '\d\d\d\d-\d\d-\d\d'], defaults: ['_format' => 'json'], methods: ['GET'])]
    public function __invoke(Request $request, UserRepository $userRepository, string $date, #[MapQueryString] JsonpDto $query = new JsonpDto()): Response
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

        /* @var $em EntityManager */
        $qb = $this->decklistRepository->createQueryBuilder('d');
        $qb->andWhere("d.dateCreation LIKE '{$date}%'");

        $decklists = $qb->getQuery()->getResult();
        $cardRepo = $this->cardRepository;
        $userRepo = $userRepository;
        $decklists = json_decode((string) json_encode($decklists), true);
        foreach ($decklists as &$decklist) {
            $decklist['heroes_details'] = [];
            $username = '';
            $user = $userRepo->findOneBy(['id' => $decklist['user_id']]);
            if ($user instanceof \App\Entity\User) {
                $username = $user->getUsername();
            }

            $decklist['username'] = $username;
            $codes = array_keys($decklist['heroes']);
            foreach ($codes as $code) {
                $card = $cardRepo->findOneBy(['code' => $code]);
                if (!$card instanceof Card) {
                    continue;
                }

                $decklist['heroes_details'][] = [
                    'name' => $card->getName(),
                    'sphere' => $card->getSphere() instanceof \App\Entity\Sphere ? $card->getSphere()->getName() : null,
                    'pack' => $card->getPack() instanceof Pack ? $card->getPack()->getName() : null,
                ];
            }
        }

        $content = json_encode($decklists);
        $this->setJsonContent($response, (string) $content, $jsonp);

        return $response;
    }
}
