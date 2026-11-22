<?php

namespace App\Tests\Api;

use App\Entity\Cycle;
use App\Entity\User;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * ImportController: POST /admin/users/csvimport (route name csvuserimport, accepts any method).
 * The upload is multipart/form-data with the file in the field "csv", separated by ";".
 */
class ImportTest extends ApiTestCase
{
    private const CSV = "Vorname;Nachname;Email;Passwort;Lizenz\n"
        ."Max;Mustermann;max@example.de;geheim;L-1\n"
        ."Peter;Administrator;neu@example.de;;L-2\n";

    /**
     * Uploads a CSV file as the given user (or anonymously when null).
     */
    private function upload(?string $asUser, ?string $csv = self::CSV): void
    {
        $this->client->getCookieJar()->clear();
        if ($asUser !== null) {
            $this->client->loginUser($this->user($asUser));
        }

        $files = [];
        $path = null;
        if ($csv !== null) {
            $path = tempnam(sys_get_temp_dir(), 'users_csv_');
            file_put_contents($path, $csv);
            $files['csv'] = new UploadedFile($path, 'users.csv', 'text/csv', null, true);
        }

        $this->client->request('POST', '/admin/users/csvimport', [], $files, ['HTTP_ACCEPT' => 'application/json']);

        if ($path !== null) {
            unlink($path);
        }
    }

    public function testImportRequiresLogin(): void
    {
        $this->upload(null);

        self::assertSame(401, $this->client->getResponse()->getStatusCode());
    }

    public function testImportForBasicIs403(): void
    {
        $this->upload('basic');

        self::assertSame(403, $this->client->getResponse()->getStatusCode());
        self::assertNull($this->freshEm()->getRepository(User::class)->findOneBy(['username' => 'mmustermann']));
    }

    public function testImportWithoutFileFailsWithTypeError(): void
    {
        // NOTE: current behaviour, possibly a bug: a request without the "csv" field is not validated
        // and the importer is called with null.
        $this->expectException(\TypeError::class);
        $this->upload('admin', null);
    }

    public function testImportForAdminCreatesNewUsersAndSkipsExistingOnes(): void
    {
        $this->upload('admin');

        $response = $this->client->getResponse();
        self::assertSame(200, $response->getStatusCode());

        $result = $this->decode($response);
        // one entry per data row: the second row collides with the existing "Peter Administrator" and is null
        self::assertCount(2, $result);
        self::assertNull($result[1]);
        self::assertIsArray($result[0]);

        $em = $this->freshEm();
        $created = $em->getRepository(User::class)->findOneBy(['username' => 'mmustermann']);
        self::assertNotNull($created);
        self::assertSame('Max', $created->getFirstname());
        self::assertSame('max@example.de', $created->getMail());
        // getRoles() merges ROLE_USER in again, so it can appear twice; only membership is pinned
        self::assertContains('ROLE_USER', $created->getRoles());
        self::assertNotContains('ROLE_ADMIN', $created->getRoles());
        self::assertSame(0, $created->getScoreForCycle($em->getRepository(Cycle::class)->findCurrentCycle())->getTargetAmount());
    }

    public function testImportedUserCanLogInWithPasswordFromCsv(): void
    {
        $this->upload('admin');

        self::assertSame(200, $this->jsonRequest('POST', '/jlogin', ['username' => 'mmustermann', 'password' => 'geheim'])->getStatusCode());
    }

    public function testImportedUserWithoutPasswordUsesLicense(): void
    {
        // empty Passwort column: the license becomes the password
        $csv = "Vorname;Nachname;Email;Passwort;Lizenz\nAnna;Lehmann;anna@example.de;;L-9\n";
        $this->upload('admin', $csv);

        self::assertSame(200, $this->jsonRequest('POST', '/jlogin', ['username' => 'alehmann', 'password' => 'L-9'])->getStatusCode());
    }
}
