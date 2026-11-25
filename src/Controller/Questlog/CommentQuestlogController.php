<?php

declare(strict_types=1);

namespace App\Controller\Questlog;

use App\Entity\QuestlogComment;
use App\Entity\User;
use App\Model\CommentDto;
use App\Repository\QuestlogRepository;
use App\Repository\UserRepository;
use App\Services\Texts;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CommentQuestlogController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly QuestlogRepository $questlogRepository,
        private readonly Texts $texts
    ) {
    }

    #[Route(path: '/user/questlog_comment', name: 'questlog_comment', methods: ['POST'])]
    public function __invoke(MailerInterface $mailer, UserRepository $userRepository, #[MapRequestPayload] CommentDto $payload = new CommentDto()): RedirectResponse
    {
        /* @var $user User */
        $user = $this->getUser();
        if (!$user instanceof \Symfony\Component\Security\Core\User\UserInterface) {
            throw new AccessDeniedHttpException('You must be logged in to comment.');
        }

        $questlog_id = filter_var($payload->id, FILTER_SANITIZE_NUMBER_INT);
        $questlog = $this->questlogRepository->find($questlog_id);
        if (!$questlog instanceof \App\Entity\Questlog) {
            throw new BadRequestHttpException('Wrong quest log id');
        }

        $comment_text = trim($payload->comment);
        if (!empty($comment_text)) {
            $comment_text = (string) preg_replace('%(?<!\\()\\b(?:(?:https?|ftp)://)(?:((?:(?:[a-z\\d\\x{00a1}-\\x{ffff}]+-?)*[a-z\\d\\x{00a1}-\\x{ffff}]+)(?:\\.(?:[a-z\\d\\x{00a1}-\\x{ffff}]+-?)*[a-z\\d\\x{00a1}-\\x{ffff}]+)*(?:\\.[a-z\\x{00a1}-\\x{ffff}]{2,6}))(?::\\d+)?)(?:[^\\s]*)?%iu', '[$1]($0)', $comment_text);
            $mentionned_usernames = [];
            $matches = [];
            if (preg_match_all('/`@([\\w_]+)`/', $comment_text, $matches, PREG_PATTERN_ORDER)) {
                $mentionned_usernames = array_unique($matches[1]);
            }

            $comment_html = $this->texts->markdown($comment_text);
            $comment = new QuestlogComment($user, $questlog, $comment_html);
            $this->entityManager->persist($comment);
            $questlog->setDateUpdate(new \DateTime());
            $questlog->setNbcomments($questlog->getNbcomments() + 1);
            $this->entityManager->flush();
            // send emails
            $spool = [];
            if ($questlog->getUser()->getIsNotifAuthor()) {
                $spool[$questlog->getUser()->getEmail()] = 'Emails/newquestlogcomment_author.html.twig';
            }

            foreach ($questlog->getComments() as $comment) {
                /* @var $comment \App\Entity\QuestlogComment */
                $commenter = $comment->getUser();
                if ($commenter->getIsNotifCommenter()) {
                    $spool[$commenter->getEmail()] ??= 'Emails/newquestlogcomment_commenter.html.twig';
                }
            }

            foreach ($mentionned_usernames as $mentionned_username) {
                /* @var $mentionned_user User */
                $mentionned_user = $userRepository->findOneBy(['username' => $mentionned_username]);
                if ($mentionned_user && $mentionned_user->getIsNotifMention()) {
                    $spool[$mentionned_user->getEmail()] ??= 'Emails/newquestlogcomment_mentionned.html.twig';
                }
            }

            unset($spool[$user->getEmail()]);
            $email_data = ['username' => $user->getUsername(), 'questlog_name' => $questlog->getName(), 'url' => $this->generateUrl('questlog_view', ['questlog_id' => $questlog->getId(), 'questlog_name' => $questlog->getNameCanonical()], UrlGeneratorInterface::ABSOLUTE_URL).'#'.$comment->getId(), 'comment' => $comment_html, 'profile' => $this->generateUrl('user_profile_edit', [], UrlGeneratorInterface::ABSOLUTE_URL)];
            foreach ($spool as $email => $view) {
                $message = new Email()->subject('[ringsdb] New comment')->from(new Address('seastan@ringsdb.com', 'Seastan'))->to(new Address($email, $user->getUsername()))->html($this->renderView($view, $email_data));
                $mailer->send($message);
            }
        }

        return $this->redirect($this->generateUrl('questlog_view', ['questlog_id' => $questlog_id, 'questlog_name' => $questlog->getNameCanonical()]));
    }
}
