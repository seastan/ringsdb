<?php

declare(strict_types=1);

namespace App\Controller\Fellowship;

use App\Entity\FellowshipComment;
use App\Entity\User;
use App\Repository\FellowshipRepository;
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

class CommentFellowshipController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly Texts $texts,
        private readonly MailerInterface $mailer,
        private readonly FellowshipRepository $fellowshipRepository,
        private readonly UserRepository $userRepository
    ) {
    }

    /**
     * records a user's comment.
     *
     * @Route("/user/fellowship_comment", name="fellowship_comment", methods={"POST"})
     */
    public function __invoke(Request $request): RedirectResponse
    {
        /* @var $user User */
        $user = $this->getUser();
        if (!$user instanceof \Symfony\Component\Security\Core\User\UserInterface) {
            throw new AccessDeniedHttpException('You must be logged in to comment.');
        }

        $fellowship_id = filter_var($request->get('id'), FILTER_SANITIZE_NUMBER_INT);
        $fellowship = $this->fellowshipRepository->find($fellowship_id);
        if (!$fellowship) {
            throw new BadRequestHttpException('Wrong fellowship id');
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
            $comment = new FellowshipComment($user, $fellowship, $comment_html);
            $this->entityManager->persist($comment);
            $fellowship->setDateUpdate(new \DateTime());
            $fellowship->setDateLastComment($comment->getDateCreation());
            $fellowship->setNbcomments($fellowship->getNbcomments() + 1);
            $this->entityManager->flush();
            // send emails
            $spool = [];
            if ($fellowship->getUser()->getIsNotifAuthor()) {
                $spool[$fellowship->getUser()->getEmail()] = 'Emails/newfellowshipcomment_author.html.twig';
            }

            foreach ($fellowship->getComments() as $comment) {
                /* @var $comment \App\Entity\FellowshipComment */
                $commenter = $comment->getUser();
                if ($commenter->getIsNotifCommenter()) {
                    $spool[$commenter->getEmail()] ??= 'Emails/newfellowshipcomment_commenter.html.twig';
                }
            }

            foreach ($mentionned_usernames as $mentionned_username) {
                /* @var $mentionned_user User */
                $mentionned_user = $this->userRepository->findOneBy(['username' => $mentionned_username]);
                if ($mentionned_user && $mentionned_user->getIsNotifMention()) {
                    $spool[$mentionned_user->getEmail()] ??= 'Emails/newfellowshipcomment_mentionned.html.twig';
                }
            }

            unset($spool[$user->getEmail()]);
            $email_data = ['username' => $user->getUsername(), 'fellowship_name' => $fellowship->getName(), 'url' => $this->generateUrl('fellowship_view', ['fellowship_id' => $fellowship->getId(), 'fellowship_name' => $fellowship->getNameCanonical()], UrlGeneratorInterface::ABSOLUTE_URL).'#'.$comment->getId(), 'comment' => $comment_html, 'profile' => $this->generateUrl('user_profile_edit', [], UrlGeneratorInterface::ABSOLUTE_URL)];
            foreach ($spool as $email => $view) {
                $message = (new Email())->subject('[ringsdb] New comment')->from(new Address('seastan@ringsdb.com', 'Seastan'))->to(new Address($email, $user->getUsername()))->html($this->renderView($view, $email_data));
                $this->mailer->send($message);
            }
        }

        return $this->redirect($this->generateUrl('fellowship_view', ['fellowship_id' => $fellowship_id, 'fellowship_name' => $fellowship->getNameCanonical()]));
    }
}
