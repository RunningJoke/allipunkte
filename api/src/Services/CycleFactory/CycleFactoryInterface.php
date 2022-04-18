<?php

namespace App\Services\CycleFactory;

use App\Entity\Cycle;

interface CycleFactoryInterface {

    public function createNewCycle(
        string $name,
        \DateTime $fromDate,
        \DateTime $toDate,
        string $description = "",
        int $costPerPoint = 0
    ) : Cycle;


}