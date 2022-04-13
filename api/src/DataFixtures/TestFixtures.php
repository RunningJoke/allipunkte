<?php

namespace App\DataFixtures;

use DateTime;
use DateInterval;
use App\Entity\Cycle;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use App\Services\UserFactoryInterface;

class TestFixtures extends Fixture
{

    private $userFactory;

    public function __construct(
        UserFactoryInterface $userFactory
    )
    {
        $this->userFactory = $userFactory;
    }

    public function load(ObjectManager $manager): void
    {
        // $product = new Product();
        // $manager->persist($product);


        $today = new DateTime();
        $endOfCycle = (new DateTime())->add(new DateInterval('P1Y'));

        $cycle = new Cycle();
        $cycle  ->setName('Testzyklus')
                ->setDescription('Zyklus zum Testen erstellt durch die Fixture')
                ->setFromDate($today)
                ->setToDate($endOfCycle)
                ->setStatus(Cycle::STATUS_OPEN);

        $manager->persist($cycle);
        $manager->flush();

        //create admin user

        $adminUser = $this->userFactory->createNewUser(
            "Peter","Administrator", "admin", "admin@example.de" , "1234", 15, true, true
        );

        $creatorUser = $this->userFactory->createNewUser(
            "Isabell","Erstellerin", "creator", "ersteller@example.de" , "1234", 15, false, true        
        );

        $basicUser = $this->userFactory->createNewUser(
            "Marta","Standard", "basic", "basic@example.de" , "1234", 20, false, false        
        );

$manager->flush();
    }
}
