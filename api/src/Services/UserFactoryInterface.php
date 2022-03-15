<?php

namespace App\Services;

use App\Entity\User;

interface UserFactoryInterface {

    public function createNewUser(
        string $firstname,
        string $lastname,
        string $username,
        string $mail,
        string $license,
        int $targetAmount,
        bool $isAdmin = false,
        bool $isCreator = false
    ) : User;


}