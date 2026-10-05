<?php

declare(strict_types=1);

namespace App\Controller\Decklist;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PatronsController extends AbstractController
{
    private int $cacheExpiration;

    private Connection $connection;

    public function __construct(
        int $cacheExpiration,
        Connection $connection
    ) {
        $this->cacheExpiration = $cacheExpiration;
        $this->connection = $connection;
    }

    /**
     * @Route("/patrons", name="patrons", methods={"GET"})
     */
    public function __invoke(): Response
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->cacheExpiration);

        $users = $this->connection->executeQuery('SELECT * FROM user WHERE donation > 0 ORDER BY donation DESC, username', [])->fetchAll(\PDO::FETCH_ASSOC);

        return $this->render('Default/patrons.html.twig', ['pagetitle' => 'The Gracious Patrons', 'patrons' => $users], $response);
    }
}
