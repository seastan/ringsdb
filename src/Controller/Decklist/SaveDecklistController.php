<?php

declare(strict_types=1);

namespace App\Controller\Decklist;

use App\Entity\Decklist;
use App\Helper\StringSanitizer;
use App\Repository\DecklistRepository;
use App\Services\Texts;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class SaveDecklistController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly DecklistRepository $decklistRepository,
        private readonly Texts $texts
    ) {
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
        if (!$user instanceof \Symfony\Component\Security\Core\User\UserInterface) {
            throw $this->createAccessDeniedException('Anonymous access denied');
        }

        $decklist = $this->decklistRepository->find($decklist_id);
        if (!$decklist) {
            throw $this->createNotFoundException('Decklist not found');
        }

        if (!$this->isGranted('ROLE_SUPER_ADMIN') && !$decklist->getUser()->isEqualTo($user)) {
            throw $this->createAccessDeniedException('Access denied');
        }

        $name = trim(StringSanitizer::sanitize($request->request->get('name'), false));
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
