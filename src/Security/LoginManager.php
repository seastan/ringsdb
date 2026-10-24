<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Http\Session\SessionAuthenticationStrategyInterface;

/**
 * Logs a user in without the login form: after the registration confirmation and the password
 * reset.
 */
class LoginManager
{
    /**
     * The firewall of security.yaml.
     */
    private const string FIREWALL = 'main';

    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
        private readonly UserCheckerInterface $userChecker,
        private readonly SessionAuthenticationStrategyInterface $sessionStrategy,
        private readonly RequestStack $requestStack,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Does nothing if the account does not pass the user checker (not confirmed yet).
     */
    public function logInUser(User $user): void
    {
        try {
            $this->userChecker->checkPreAuth($user);
        } catch (AccountStatusException) {
            return;
        }

        $token = new UsernamePasswordToken($user, self::FIREWALL, $user->getRoles());
        $request = $this->requestStack->getCurrentRequest();
        if ($request instanceof \Symfony\Component\HttpFoundation\Request) {
            $this->sessionStrategy->onAuthentication($request, $token);
        }

        $this->tokenStorage->setToken($token);

        $user->setLastLogin(new \DateTime());
        $this->entityManager->flush();
    }

    /**
     * The page the user requested before being sent to the login page, if any.
     */
    public function getTargetPath(): ?string
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof \Symfony\Component\HttpFoundation\Request || !$request->hasSession()) {
            return null;
        }

        return $request->getSession()->get('_security.'.self::FIREWALL.'.target_path');
    }
}
