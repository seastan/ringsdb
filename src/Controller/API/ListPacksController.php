<?php

declare(strict_types=1);

namespace App\Controller\API;

use App\Entity\Pack;
use App\Repository\PackRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ListPacksController extends AbstractController
{
    use JsonpTrait;

    public function __construct(
        private readonly int $cacheExpiration,
        private readonly PackRepository $packRepository
    ) {
    }

    /**
     * Get the description of all the packs as an array of JSON objects.
     *
     * ApiDoc(
     *  section="Pack",
     *  resource=true,
     *  description="All the Packs",
     *  parameters={
     *    {"name"="jsonp", "dataType"="string", "required"=false, "description"="JSONP callback"}
     *  },
     * )
     */
    #[Route(path: '/api/public/packs/', name: 'api_packs', methods: ['GET'])]
    public function __invoke(Request $request): Response
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->cacheExpiration);
        $response->headers->add(['Access-Control-Allow-Origin' => '*']);

        $jsonp = $request->query->getString('jsonp');
        /* @var $em EntityManager */
        /* @var $list_packs \App\Entity\Pack[] */
        $list_packs = $this->packRepository->findBy([], ['dateRelease' => 'ASC', 'position' => 'ASC']);
        // check the last-modified-since header
        $lastModified = null;
        foreach ($list_packs as $pack) {
            if (!$lastModified || $lastModified < $pack->getDateUpdate()) {
                $lastModified = $pack->getDateUpdate();
            }
        }

        $response->setLastModified($lastModified);
        if ($response->isNotModified($request)) {
            return $response;
        }

        $packs = [];
        /* @var $pack \App\Entity\Pack */
        foreach ($list_packs as $pack) {
            $real = count($pack->getCards());
            $max = $pack->getSize();
            $packs[] = [
                'name' => $pack->getName(),
                'code' => $pack->getCode(),
                'position' => $pack->getPosition(),
                'cycle_position' => $pack->getCycle() ? $pack->getCycle()->getPosition() : null,
                'available' => $pack->getDateRelease()
                    ? $pack->getDateRelease()->format('Y-m-d')
                    : '',
                'known' => intval($real),
                'total' => $max,
                'url' => $this->generateUrl('cards_list', ['pack_code' => $pack->getCode()], UrlGeneratorInterface::ABSOLUTE_URL),
                'id' => $pack->getId(),
            ];
        }

        $content = json_encode($packs);
        $this->setJsonContent($response, (string) $content, $jsonp);

        return $response;
    }
}
