<?php

namespace App\Security\Voter;

use App\Entity\Petitioning\Petition;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class PetitionCanEditVoter extends Voter
{
    protected function supports(string $attribute, $subject): bool
    {
        // replace with your own logic
        // https://symfony.com/doc/current/security/voters.html
        return in_array($attribute, ['PETITION_CAN_UPDATE'])
            && $subject instanceof Petition;
    }

    /**
     * Undocumented function
     *
     * @param string $attribute
     * @param Petition $subject
     * @param TokenInterface $token
     * @return boolean
     */
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        // if the user is anonymous, do not grant access
        if (!$user instanceof UserInterface) {
            return false;
        }

        // ... (check conditions and return true to grant permission) ...
        switch ($attribute) {
            case 'PETITION_CAN_UPDATE':
                return $subject->getCreateUser() == $user;
                break;
        }

        return false;
    }
}
