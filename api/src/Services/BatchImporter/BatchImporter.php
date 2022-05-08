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

        $firstname = trim($this->removeAnnotations($namedFields['Vorname'] ?? ''));
        $lastname = trim($this->removeAnnotations($namedFields['Nachname'] ?? ''));
        $mail = trim($namedFields['Email'] ?? '');
        $password = trim($namedFields['Passwort'] ?? '');
        $license = trim($namedFields['Lizenz'] ?? '');

        //these are stripped from umlauts, accents, spaces etc... Just letters
        $cleanFirstName = strtolower($this->cleanString($firstname));
        $cleanLastName = strtolower($this->cleanString($lastname));

        $lookupKey = strtolower($cleanFirstName).'_'.strtolower($cleanLastName);

        //check if the user already exists
        if(isset($this->userLookupTable[$mail]) || isset($this->userLookupTable[$lookupKey])) {
            //mail or user already exist in the system
            return null;
        }

        //no collision: generate password
        $generatedPassword = $this->generatePassword($mail , $password , $license);

        //use the clean variants for user generation so people will stay sane
        $generatedUsername = $this->generateUsername($cleanFirstName , $cleanLastName);

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

        if($newUser !== null) {
            //add newly created user to the lookup table to prevent collision with other newly created users
            $this->userLookupTable[$mail] = $newUser;
            $this->userLookupTable[$lookupKey] = $newUser;
            $this->userLookupTable[$newUser->getUsername()] = $newUser;
        }

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
        
        for($i=1;$i<strlen($firstname);$i++) {
            $firstnameAbbr = substr($firstname,0,$i);

            $username = $firstnameAbbr.$lastname;

            if(!isset($this->userLookupTable[$username])) {
                return $username;
            }
        }

        return null;

    }

    private function removeAnnotations($text) {
        return preg_replace('/\(.*\)/', '', $text);
    }

    private function cleanString($text) {
        $utf8 = array(
            '/[áàâãªä]/u'   =>   'a',
            '/[ÁÀÂÃÄ]/u'    =>   'A',
            '/[ÍÌÎÏ]/u'     =>   'I',
            '/[íìîï]/u'     =>   'i',
            '/[éèêë]/u'     =>   'e',
            '/[ÉÈÊË]/u'     =>   'E',
            '/[óòôõºö]/u'   =>   'o',
            '/[ÓÒÔÕÖ]/u'    =>   'O',
            '/[úùûü]/u'     =>   'u',
            '/[ÚÙÛÜ]/u'     =>   'U',
            '/ç/'           =>   'c',
            '/Ç/'           =>   'C',
            '/ñ/'           =>   'n',
            '/Ñ/'           =>   'N',
            '/ß/'           =>   'ss',
            '/[\'\`\´\-]/'      =>   '', //just remove 
            '/–/'           =>   '-', // UTF-8 hyphen to "normal" hyphen
            '/[’‘‹›‚]/u'    =>   '', // Literally a single quote
            '/[“”«»„]/u'    =>   '', // Double quote
            '/ /'           =>   '' // nonbreaking space (equiv. to 0x160)
        );
        return preg_replace(array_keys($utf8), array_values($utf8), $text);
    }


}