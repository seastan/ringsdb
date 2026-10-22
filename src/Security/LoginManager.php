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
    private const FIREWALL = 'main';

    private TokenStorageInterface $tokenStorage;

    private UserCheckerInterface $userChecker;

    private SessionAuthenticationStrategyInterface $sessionStrategy;

    private RequestStack $requestStack;

    private EntityManagerInterface $entityManager;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        UserCheckerInterface $userChecker,
        SessionAuthenticationStrategyInterface $sessionStrategy,
        RequestStack $requestStack,
        EntityManagerInterface $entityManager
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->userChecker = $userChecker;
        $this->sessionStrategy = $sessionStrategy;
        $this->requestStack = $requestStack;
        $this->entityManager = $entityManager;
    }

    /**
     * Does nothing if the account does not pass the user checker (not confirmed yet).
     */
    public function logInUser(User $user): void
    {
        try {
            $this->userChecker->checkPreAuth($user);
        } catch (AccountStatusException $accountStatusException) {
            return;
        }

        $token = new UsernamePasswordToken($user, self::FIREWALL, $user->getRoles());
        $request = $this->requestStack->getCurrentRequest();
        if (null !== $request) {
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
        if (null === $request || !$request->hasSession()) {
            return null;
        }

        return $request->getSession()->get('_security.'.self::FIREWALL.'.target_path');
    }
}
