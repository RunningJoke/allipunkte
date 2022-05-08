<?php

namespace App\Services\BatchImporter;

use Symfony\Component\HttpFoundation\File\UploadedFile;



interface BatchImporterInterface {

    public function handleCsv(
        UploadedFile $file
    ) : array;


}