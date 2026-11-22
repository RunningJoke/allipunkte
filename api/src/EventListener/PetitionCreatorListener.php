<?php

namespace App\EventListener;

use App\Entity\Petitioning\Petition;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * The creator of a petition is always the logged-in user, never a value sent by the client (#62).
 */
#[AsEntityListener(event: Events::prePersist, method: 'prePersist', entity: Petition::class)]
#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: Petition::class)]
final class PetitionCreatorListener
{
    public function __construct(private TokenStorageInterface $tokenStorage)
    {
    }

    public function prePersist(Petition $petition): void
    {
        $user = $this->tokenStorage->getToken()?->getUser();
        // without a logged-in user (fixtures, console) the explicitly set creator is kept
        if ($user instanceof User) {
            $petition->setCreateUser($user);
        }
    }

    public function preUpdate(Petition $petition, PreUpdateEventArgs $args): void
    {
        // the creator cannot be reassigned after creation; restore it in the DB change set and in the response
        if ($args->hasChangedField('createUser')) {
            $original = $args->getOldValue('createUser');
            $args->setNewValue('createUser', $original);
            $petition->setCreateUser($original);
        }
    }
}
