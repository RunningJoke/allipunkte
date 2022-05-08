<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Services\BatchImporter\BatchImporterInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ImportController extends AbstractController
{

    private BatchImporterInterface $batchImporter;

    public function __construct(BatchImporterInterface $batchImporter) {
        $this->batchImporter = $batchImporter;
    }


    #[Route('/admin/users/csvimport', name: 'csvuserimport')]
    public function index(Request $request): Response
    {
        //limit access to admins only
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $fileObject = $request->files->get('csv');



        $result = $this->batchImporter->handleCsv($fileObject);

        return $this->json($result);
    }
}
