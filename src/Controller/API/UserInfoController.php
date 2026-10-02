<?php

declare(strict_types=1);

namespace App\Controller\API;

use App\Controller\CurrentUserTrait;
use App\Entity\Card;
use App\Entity\Decklist;
use App\Entity\Fellowship;
use App\Entity\Questlog;
use App\Entity\Review;
use App\Repository\CardRepository;
use App\Repository\DecklistRepository;
use App\Repository\FellowshipRepository;
use App\Repository\QuestlogRepository;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UserInfoController extends AbstractController
{
    use CurrentUserTrait;

    private Connection $connection;

    public function __construct(
        Connection $connection
    ) {
        $this->connection = $connection;
    }

    /**
     * @Route("/api/private/user/info", name="api_private_user_info")
     */
    public function infoAction(Request $request, CardRepository $cardRepository, DecklistRepository $decklistRepository, FellowshipRepository $fellowshipRepository, QuestlogRepository $questlogRepository): Response
    {
        $jsonp = $request->query->get('jsonp');
        $decklist_id = $request->query->get('decklist_id');
        $fellowship_id = $request->query->get('fellowship_id');
        $questlog_id = $request->query->get('questlog_id');
        $card_id = $request->query->get('card_id');
        $content = null;
        if ($this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            $user = $this->currentUser();
            $user_id = $user->getId();
            $public_profile_url = $this->generateUrl('user_profile_public', ['user_id' => $user_id, 'user_name' => urlencode($user->getUsername())]);
            $content = ['public_profile_url' => $public_profile_url, 'id' => $user_id, 'name' => $user->getUsername(), 'sphere' => $user->getColor(), 'donation' => $user->getDonation(), 'owned_packs' => $user->getOwnedPacks(), 'dark_mode' => $user->getDarkMode(), 'art_preferences' => $user->getArtPreferences()];
            if (isset($decklist_id)) {
                /* @var $decklist Decklist */
                $decklist = $decklistRepository->find($decklist_id);
                if ($decklist) {
                    $decklist_id = $decklist->getId();
                    $content['is_liked'] = (bool) $this->connection->executeQuery("SELECT\n        \t\t\t\tcount(*)\n        \t\t\t\tFROM decklist d\n        \t\t\t\tJOIN vote v ON v.decklist_id = d.id\n        \t\t\t\tWHERE v.user_id = ?\n        \t\t\t\tAND d.id = ?", [$user_id, $decklist_id])->fetch(\PDO::FETCH_NUM)[0];
                    $content['is_favorite'] = (bool) $this->connection->executeQuery("SELECT\n        \t\t\t\tcount(*)\n        \t\t\t\tFROM decklist d\n        \t\t\t\tJOIN favorite f ON f.decklist_id = d.id\n        \t\t\t\tWHERE f.user_id = ?\n        \t\t\t\tAND d.id = ?", [$user_id, $decklist_id])->fetch(\PDO::FETCH_NUM)[0];
                    $content['is_author'] = $user_id == $decklist->getUser()->getId();
                    $content['can_delete'] = 0 == $decklist->getNbcomments() && 0 == $decklist->getNbfavorites() && 0 == $decklist->getNbVotes();
                }
            }
            if (isset($fellowship_id)) {
                /* @var $fellowship Fellowship */
                $fellowship = $fellowshipRepository->find($fellowship_id);
                if ($fellowship) {
                    $fellowship_id = $fellowship->getId();
                    $content['is_liked'] = (bool) $this->connection->executeQuery("SELECT\n        \t\t\t\tcount(*)\n        \t\t\t\tFROM fellowship d\n        \t\t\t\tJOIN fellowship_vote v ON v.fellowship_id = d.id\n        \t\t\t\tWHERE v.user_id = ?\n        \t\t\t\tAND d.id = ?", [$user_id, $fellowship_id])->fetch(\PDO::FETCH_NUM)[0];
                    $content['is_favorite'] = (bool) $this->connection->executeQuery("SELECT\n        \t\t\t\tcount(*)\n        \t\t\t\tFROM fellowship d\n        \t\t\t\tJOIN fellowship_favorite f ON f.fellowship_id = d.id\n        \t\t\t\tWHERE f.user_id = ?\n        \t\t\t\tAND d.id = ?", [$user_id, $fellowship_id])->fetch(\PDO::FETCH_NUM)[0];
                    $content['is_author'] = $user_id == $fellowship->getUser()->getId();
                    $content['can_delete'] = 0 == $fellowship->getNbcomments() && 0 == $fellowship->getNbfavorites() && 0 == $fellowship->getNbVotes();
                }
            }
            if (isset($questlog_id)) {
                /* @var $questlog Questlog */
                $questlog = $questlogRepository->find($questlog_id);
                if ($questlog) {
                    $questlog_id = $questlog->getId();
                    $content['is_liked'] = (bool) $this->connection->executeQuery("SELECT\n        \t\t\t\tcount(*)\n        \t\t\t\tFROM questlog d\n        \t\t\t\tJOIN questlog_vote v ON v.questlog_id = d.id\n        \t\t\t\tWHERE v.user_id = ?\n        \t\t\t\tAND d.id = ?", [$user_id, $questlog_id])->fetch(\PDO::FETCH_NUM)[0];
                    $content['is_favorite'] = (bool) $this->connection->executeQuery("SELECT\n        \t\t\t\tcount(*)\n        \t\t\t\tFROM questlog d\n        \t\t\t\tJOIN questlog_favorite f ON f.questlog_id = d.id\n        \t\t\t\tWHERE f.user_id = ?\n        \t\t\t\tAND d.id = ?", [$user_id, $questlog_id])->fetch(\PDO::FETCH_NUM)[0];
                    $content['is_author'] = $user_id == $questlog->getUser()->getId();
                    $content['can_delete'] = 0 == $questlog->getNbcomments() && 0 == $questlog->getNbfavorites() && 0 == $questlog->getNbVotes();
                }
            }
            if (isset($card_id)) {
                /* @var $card Card */
                $card = $cardRepository->find($card_id);
                if ($card) {
                    $reviews = $card->getReviews();
                    /* @var $review Review */
                    foreach ($reviews as $review) {
                        if ($review->getUser()->getId() === $user->getId()) {
                            $content['review_id'] = $review->getId();
                            $content['review_text'] = $review->getTextMd();
                        }
                    }
                }
            }
        }
        $content = json_encode($content);
        $response = new Response();
        $response->setPrivate();
        if (isset($jsonp)) {
            $content = "{$jsonp}({$content})";
            $response->headers->set('Content-Type', 'application/javascript');
        } else {
            $response->headers->set('Content-Type', 'application/json');
        }
        $response->setContent($content);

        return $response;
    }
}
