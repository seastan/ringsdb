<?php

declare(strict_types=1);

namespace App\Controller\Fellowship;

use App\Controller\CurrentUserTrait;
use App\Services\FellowshipArchiver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OctgnExportController extends AbstractController
{
    use CurrentUserTrait;

    private FellowshipArchiver $fellowshipArchiver;

    public function __construct(
        FellowshipArchiver $fellowshipArchiver
    ) {
        $this->fellowshipArchiver = $fellowshipArchiver;
    }

    /**
     * @Route(
     *     "/fellowship/export/octgn/{fellowship_id}",
     *     name="fellowship_export_octgn",
     *     methods={"GET"},
     *     requirements={"fellowship_id"="\d+"}
     * )
     */
    public function __invoke(int $fellowship_id): Response
    {
        return $this->fellowshipArchiver->downloadFromSelection($this->currentUser(), $fellowship_id, true);
    }
}
