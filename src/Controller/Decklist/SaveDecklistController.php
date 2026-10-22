<?php

declare(strict_types=1);

namespace App\Controller\Decklist;

use App\Entity\Decklist;
use App\Repository\DecklistRepository;
use App\Services\Texts;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class SaveDecklistController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    private DecklistRepository $decklistRepository;

    private Texts $texts;

    public function __construct(
        EntityManagerInterface $entityManager,
        DecklistRepository $decklistRepository,
        Texts $texts
    ) {
        $this->entityManager = $entityManager;
        $this->decklistRepository = $decklistRepository;
        $this->texts = $texts;
    }

    /**
     * save the name and description of a decklist by its publisher.
     *
     * @Route(
     *     "/decklist/save/{decklist_id}",
     *     name="decklist_save",
     *     methods={"POST"},
     *     requirements={"decklist_id"="\d+"}
     * )
     */
    public function __invoke(Request $request, int $decklist_id): RedirectResponse
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Anonymous access denied');
        }

        $decklist = $this->decklistRepository->find($decklist_id);
        if (!$decklist) {
            throw $this->createNotFoundException('Decklist not found');
        }

        if (!$this->isGranted('ROLE_SUPER_ADMIN') && !$decklist->getUser()->isEqualTo($user)) {
            throw $this->createAccessDeniedException('Access denied');
        }

        $name = trim((string) filter_var($request->request->get('name'), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));
        $name = substr($name, 0, 60);
        if (empty($name)) {
            $name = 'Untitled';
        }

        $descriptionMd = trim((string) $request->request->get('descriptionMd'));
        $descriptionHtml = $this->texts->markdown($descriptionMd);
        $precedent_id = trim((string) $request->request->get('precedent'));
        if (!preg_match('/^\\d+$/', $precedent_id)) {
            // route decklist_detail hard-coded
            if (preg_match('/view\\/(\\d+)/', $precedent_id, $matches)) {
                $precedent_id = $matches[1];
            } else {
                $precedent_id = null;
            }
        }

        $precedent = $precedent_id && $precedent_id != $decklist_id ? $this->decklistRepository->find($precedent_id) : null;
        $decklist->setName($name);
        $decklist->setNameCanonical($this->texts->slugify($name).'-'.$decklist->getVersion());
        $decklist->setDescriptionMd($descriptionMd);
        $decklist->setDescriptionHtml($descriptionHtml);
        $decklist->setPrecedent($precedent);
        $decklist->setDateUpdate(new \DateTime());

        $this->entityManager->flush();

        return $this->redirect($this->generateUrl('decklist_detail', ['decklist_id' => $decklist_id, 'decklist_name' => $decklist->getNameCanonical()]));
    }
}
