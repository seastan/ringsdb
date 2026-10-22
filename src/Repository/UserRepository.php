<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements UserLoaderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * The user provider of the firewall (security.yaml): login by username or email.
     */
    public function loadUserByIdentifier(string $identifier): ?User
    {
        return $this->findOneByUsernameOrEmail($identifier);
    }

    /**
     * Symfony 5.4 still calls it when loadUserByIdentifier() is missing; removed in Symfony 6.
     *
     * @param string $username
     */
    public function loadUserByUsername($username): ?User
    {
        return $this->loadUserByIdentifier($username);
    }

    /**
     * Case-insensitive, as the canonical columns are lowercased.
     */
    public function findOneByUsername(string $username): ?User
    {
        return $this->findOneBy(['usernameCanonical' => User::canonicalize($username)]);
    }

    /**
     * Case-insensitive, as the canonical columns are lowercased.
     */
    public function findOneByEmail(string $email): ?User
    {
        return $this->findOneBy(['emailCanonical' => User::canonicalize($email)]);
    }

    /**
     * Something that looks like an email is first looked up as an email, then as a username.
     */
    public function findOneByUsernameOrEmail(string $usernameOrEmail): ?User
    {
        if (preg_match('/^.+\@\S+\.\S+$/', $usernameOrEmail)) {
            $user = $this->findOneByEmail($usernameOrEmail);
            if ($user instanceof User) {
                return $user;
            }
        }

        return $this->findOneByUsername($usernameOrEmail);
    }

    public function findOneByConfirmationToken(string $token): ?User
    {
        return $this->findOneBy(['confirmationToken' => $token]);
    }
}
