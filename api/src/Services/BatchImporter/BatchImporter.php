<?php

namespace App\Services\BatchImporter;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Services\UserFactory\UserFactoryInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class BatchImporter implements BatchImporterInterface {

    private UserFactoryInterface $userFactory;
    private UserRepository $userRepository;

    /**
     * @var User[]
     */
    private array $userLookupTable;

    public function __construct(UserFactoryInterface $userFactory, UserRepository $userRepository) {
        $this->userFactory = $userFactory;
        $this->userRepository = $userRepository;
    }


    public function handleCsv(
        UploadedFile $file
    ) : array
    {
        $resultBatch = [];
        $this->userLookupTable = $this->loadAllUsersAndMap();

        //open file
        $fh = fopen($file->getRealPath(),'r');

        //parse first line of csv
        //this is required to include the headers
        $headers = fgetcsv($fh,null,";");


        while(($currentRow = fgetcsv($fh, null,";")) !== false) {

            $resultBatch[] = $this->buildUserFromRow($headers, $currentRow);

        }






        return $resultBatch;
    }

    /**
     * Fetch all existing users from the database and load them into the lookup array
     *
     * @return User[]
     */
    private function loadAllUsersAndMap() : array
    {
        $allUsers = $this->userRepository->findAll();
        // create a dictionary to quickly find the users: this is structured as following:
        // - save user under the key email 
        // - save user under allsmallletters firstname_lastname
        // - save user under username

        $userDictionary = [];

        foreach($allUsers as $user) {

            $userDictionary[$user->getUsername()] = $user;

            $userMail = $user->getMail();
            if($userMail !== '') {
                $userDictionary[$user->getMail()] = $user;
            }

            $userKey = strtolower(trim($user->getFirstname())).'_'.strtolower(trim($user->getLastname()));
            $userDictionary[$userKey] = $user;


        }

        return $userDictionary;

    
    }


    private function buildUserFromRow(array $headers, array $rowData) : ?User
    {
        $namedFields = array_combine($headers, $rowData);

        $firstname = trim($namedFields['Vorname'] ?? '');
        $lastname = trim($namedFields['Nachname'] ?? '');
        $mail = trim($namedFields['Email'] ?? '');
        $password = trim($namedFields['Passwort'] ?? '');
        $license = trim($namedFields['Lizenz'] ?? '');

        $lookupKey = strtolower($firstname).'_'.strtolower($lastname);

        //check if the user already exists
        if(isset($this->userLookupTable[$mail]) || isset($this->userLookupTable[$lookupKey])) {
            //mail or user already exist in the system
            return null;
        }

        //no collision: generate password
        $generatedPassword = $this->generatePassword($mail , $password , $license);
        $generatedUsername = $this->generateUsername($firstname , $lastname);

        if($generatedUsername === null) {
            //creation failed because username could not be generated
            return null;
        }

        //now run user through factory
        $newUser = $this->userFactory->createNewUser(
            $firstname,
            $lastname,
            $generatedUsername,
            $mail,
            $license,
            0,
            false,
            false,
            $generatedPassword
        );

        return $newUser;

    }


    private function generatePassword($mail , $password = '' , $license = '') : string
    {
        if($password !== '') {
            return $password;
        }
        
        if($license !== '') {
            return $license;
        }

        //get first 5 and last 3 letters
        $start = substr($mail,0,5);
        $end = strrev(substr(strrev($mail),0,3));

        return ucfirst(strtolower($start.$end));
    }

    private function generateUsername($firstname , $lastname) : ?string
    {

        for($i=0;$i<strlen($firstname);$i++) {
            $firstnameAbbr = substr($firstname,0,$i);

            $username = $firstnameAbbr.$lastname;

            if(!isset($this->userLookupTable[$username])) {
                return $username;
            }
        }

        return null;

    }


}