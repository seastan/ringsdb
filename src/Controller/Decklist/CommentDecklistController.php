<?php

declare(strict_types=1);

namespace App\Controller\Decklist;

use App\Entity\Comment;
use App\Entity\Decklist;
use App\Entity\User;
use App\Repository\DecklistRepository;
use App\Repository\UserRepository;
use App\Services\Texts;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CommentDecklistController extends AbstractController
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly UserRepository $userRepository,
        private readonly DecklistRepository $decklistRepository,
        private readonly Texts $texts,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * records a user's comment.
     *
     * @Route("/user/comment", name="decklist_comment", methods={"POST"})
     */
    public function __invoke(Request $request): RedirectResponse
    {
        /* @var $user User */
        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedHttpException('You must be logged in to comment.');
        }

        $decklist_id = filter_var($request->get('id'), FILTER_SANITIZE_NUMBER_INT);
        $decklist = $this->decklistRepository->find($decklist_id);
        if (!$decklist instanceof Decklist) {
            throw new BadRequestHttpException('Wrong decklist id');
        }

        $comment_text = trim($request->get('comment'));
        if (!empty($comment_text)) {
            $comment_text = (string) preg_replace('%(?<!\\()\\b(?:(?:https?|ftp)://)(?:((?:(?:[a-z\\d\\x{00a1}-\\x{ffff}]+-?)*[a-z\\d\\x{00a1}-\\x{ffff}]+)(?:\\.(?:[a-z\\d\\x{00a1}-\\x{ffff}]+-?)*[a-z\\d\\x{00a1}-\\x{ffff}]+)*(?:\\.[a-z\\x{00a1}-\\x{ffff}]{2,6}))(?::\\d+)?)(?:[^\\s]*)?%iu', '[$1]($0)', $comment_text);
            $mentionned_usernames = [];
            $matches = [];
            if (preg_match_all('/`@([\\w_]+)`/', $comment_text, $matches, PREG_PATTERN_ORDER)) {
                $mentionned_usernames = array_unique($matches[1]);
            }

            $comment_html = $this->texts->markdown($comment_text);
            $now = new \DateTime();
            $comment = new Comment($user, $decklist, $comment_html);
            $this->entityManager->persist($comment);
            $decklist->setDateUpdate($now);
            $decklist->setDateLastComment($comment->getDateCreation());
            $decklist->setNbcomments($decklist->getNbcomments() + 1);
            $this->entityManager->flush();
            // send emails
            $spool = [];
            if ($decklist->getUser()->getIsNotifAuthor()) {
                $spool[$decklist->getUser()->getEmail()] = 'Emails/newcomment_author.html.twig';
            }

            foreach ($decklist->getComments() as $comment) {
                /* @var $comment Comment */
                $commenter = $comment->getUser();
                if ($commenter->getIsNotifCommenter()) {
                    $spool[$commenter->getEmail()] ??= 'Emails/newcomment_commenter.html.twig';
                }
            }

            foreach ($mentionned_usernames as $mentionned_username) {
                /* @var $mentionned_user User */
                $mentionned_user = $this->userRepository->findOneBy(['username' => $mentionned_username]);
                if ($mentionned_user && $mentionned_user->getIsNotifMention()) {
                    $spool[$mentionned_user->getEmail()] ??= 'Emails/newcomment_mentionned.html.twig';
                }
            }

            unset($spool[$user->getEmail()]);
            $email_data = ['username' => $user->getUsername(), 'decklist_name' => $decklist->getName(), 'url' => $this->generateUrl('decklist_detail', ['decklist_id' => $decklist->getId(), 'decklist_name' => $decklist->getNameCanonical()], UrlGeneratorInterface::ABSOLUTE_URL).'#'.$comment->getId(), 'comment' => $comment_html, 'profile' => $this->generateUrl('user_profile_edit', [], UrlGeneratorInterface::ABSOLUTE_URL)];
            foreach ($spool as $email => $view) {
                $message = (new Email())->subject('[ringsdb] New comment')->from(new Address('seastan@ringsdb.com', 'Seastan'))->to(new Address($email, $user->getUsername()))->html($this->renderView($view, $email_data));
                $this->mailer->send($message);
            }
        }

        return $this->redirect($this->generateUrl('decklist_detail', ['decklist_id' => $decklist_id, 'decklist_name' => $decklist->getNameCanonical()]));
    }
}
