<?php

namespace App\Tests\Api;

use App\Entity\Petitioning\Petition;

/**
 * API Platform resource App\Entity\Petitioning\Petition (/petitions, /petitions/{id}).
 * Update rights come from PetitionCanEditVoter: only the creator may PUT, not even ROLE_ADMIN.
 */
class PetitionTest extends ApiTestCase
{
    private const KEYS = [
        'id', 'createUser', 'createDate', 'title', 'description', 'status',
        'dueDate', 'openPositions', 'filledPositions', 'offeredPoints',
    ];

    /**
     * Body as the frontend sends it from Petitions.vue (createNew / submitModal).
     */
    private function frontendBody(array $overrides = []): array
    {
        return array_merge([
            'createUser' => '/users/3',
            'createDate' => '2026-11-22T10:00:00+01:00',
            'title' => 'Helfer gesucht',
            'description' => 'Aufbau am Samstag',
            'dueDate' => '2027-01-01T10:00',
            'status' => 'open',
            'openPositions' => 1,
            'filledPositions' => 0,
            'offeredPoints' => 5,
        ], $overrides);
    }

    /**
     * Inserts a petition owned by the given user, directly via the EntityManager.
     */
    private function createPetition(string $ownerUsername, string $title = 'Testangebot'): Petition
    {
        $em = static::em();
        $petition = (new Petition())
            ->setCreateUser($this->user($ownerUsername))
            ->setCreateDate(new \DateTime('2026-11-22 10:00:00'))
            ->setTitle($title)
            ->setDescription('')
            ->setStatus('open')
            ->setDueDate(new \DateTime('2027-01-01 10:00:00'))
            ->setOpenPositions(1)
            ->setFilledPositions(0)
            ->setOfferedPoints(5);
        $em->persist($petition);
        $em->flush();

        return $petition;
    }

    // ---- GET /petitions and /petitions/{id} ------------------------------

    public function testCollectionRequiresLogin(): void
    {
        self::assertSame(401, $this->jsonRequest('GET', '/petitions')->getStatusCode());
    }

    public function testCollectionIsEmptyWithoutPetitions(): void
    {
        $response = $this->jsonRequest('GET', '/petitions', null, 'basic');

        self::assertSame(200, $response->getStatusCode());
        self::assertSame([], $this->decode($response));
    }

    public function testCollectionReturnsExactFieldsAndIriForCreator(): void
    {
        $this->createPetition('basic');

        $list = $this->decode($this->jsonRequest('GET', '/petitions', null, 'admin'));

        self::assertCount(1, $list);
        $this->assertKeysExactly(self::KEYS, $list[0]);
        $this->assertIri('users', $list[0]['createUser']);
        self::assertSame('/users/3', $list[0]['createUser']);
        self::assertSame(5, $list[0]['offeredPoints']);
        self::assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/', $list[0]['dueDate']);
    }

    public function testItemRequiresLogin(): void
    {
        $petition = $this->createPetition('basic');

        self::assertSame(401, $this->jsonRequest('GET', '/petitions/'.$petition->getId())->getStatusCode());
    }

    public function testItemIsReadableByAnyLoggedInUser(): void
    {
        $petition = $this->createPetition('basic');

        $response = $this->jsonRequest('GET', '/petitions/'.$petition->getId(), null, 'creator');
        $data = $this->decode($response);

        self::assertSame(200, $response->getStatusCode());
        $this->assertKeysExactly(self::KEYS, $data);
        self::assertSame('Testangebot', $data['title']);
    }

    public function testItemUnknownIdIs404(): void
    {
        self::assertSame(404, $this->jsonRequest('GET', '/petitions/999', null, 'basic')->getStatusCode());
    }

    // ---- POST /petitions ---------------------------------------------------

    public function testCreateRequiresLogin(): void
    {
        self::assertSame(401, $this->jsonRequest('POST', '/petitions', $this->frontendBody())->getStatusCode());
    }

    public function testCreateWithFrontendBodyReturns201AndStoresRow(): void
    {
        $response = $this->jsonRequest('POST', '/petitions', $this->frontendBody(), 'basic');
        $data = $this->decode($response);

        self::assertSame(201, $response->getStatusCode());
        $this->assertKeysExactly(self::KEYS, $data);
        self::assertSame('/users/3', $data['createUser']);
        self::assertSame('open', $data['status']);

        $stored = $this->freshEm()->getRepository(Petition::class)->find($data['id']);
        self::assertNotNull($stored);
        self::assertSame('Helfer gesucht', $stored->getTitle());
        self::assertSame('basic', $stored->getCreateUser()->getUsername());
    }

    public function testCreateIgnoresAnotherUsersIriAndUsesTheLoggedInUser(): void
    {
        // #62: the creator is always the logged-in user, a client-supplied createUser is ignored
        $response = $this->jsonRequest('POST', '/petitions', $this->frontendBody(['createUser' => '/users/1']), 'basic');
        $data = $this->decode($response);

        self::assertSame(201, $response->getStatusCode());
        self::assertSame('/users/3', $data['createUser']);
        $stored = $this->freshEm()->getRepository(Petition::class)->find($data['id']);
        self::assertSame('basic', $stored->getCreateUser()->getUsername());
    }

    public function testCreateWithoutCreateUserUsesTheLoggedInUser(): void
    {
        // #62: a missing createUser no longer ends in a NOT NULL error
        $body = $this->frontendBody();
        unset($body['createUser']);

        $response = $this->jsonRequest('POST', '/petitions', $body, 'basic');

        self::assertSame(201, $response->getStatusCode());
        self::assertSame('/users/3', $this->decode($response)['createUser']);
    }

    public function testPutCannotReassignTheCreator(): void
    {
        // #62: the creator cannot hand the petition over to someone else
        $petition = $this->createPetition('basic');

        $response = $this->jsonRequest('PUT', '/petitions/'.$petition->getId(), $this->frontendBody(['createUser' => '/users/1', 'title' => 'Geändert']), 'basic');
        $data = $this->decode($response);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('/users/3', $data['createUser']);
        $stored = $this->freshEm()->getRepository(Petition::class)->find($petition->getId());
        self::assertSame('basic', $stored->getCreateUser()->getUsername());
        self::assertSame('Geändert', $stored->getTitle());
    }

    // ---- PUT /petitions/{id} (creator only) --------------------------------

    public function testPutRequiresLogin(): void
    {
        $petition = $this->createPetition('basic');

        self::assertSame(401, $this->jsonRequest('PUT', '/petitions/'.$petition->getId(), $this->frontendBody())->getStatusCode());
    }

    public function testPutByAdminWhoIsNotTheCreatorIs403(): void
    {
        $petition = $this->createPetition('basic');

        $response = $this->jsonRequest('PUT', '/petitions/'.$petition->getId(), $this->frontendBody(['title' => 'Admin-Edit']), 'admin');

        self::assertSame(403, $response->getStatusCode());
        self::assertSame('Testangebot', $this->freshEm()->getRepository(Petition::class)->find($petition->getId())->getTitle());
    }

    public function testPutByOtherRegularUserIs403(): void
    {
        $petition = $this->createPetition('basic');

        $response = $this->jsonRequest('PUT', '/petitions/'.$petition->getId(), $this->frontendBody(['title' => 'x']), 'creator');

        self::assertSame(403, $response->getStatusCode());
    }

    public function testPutByCreatorUpdatesPetition(): void
    {
        $petition = $this->createPetition('basic');

        $response = $this->jsonRequest('PUT', '/petitions/'.$petition->getId(), $this->frontendBody(['title' => 'Geändert', 'filledPositions' => 1]), 'basic');
        $data = $this->decode($response);

        self::assertSame(200, $response->getStatusCode());
        $this->assertKeysExactly(self::KEYS, $data);
        self::assertSame('Geändert', $data['title']);
        self::assertSame(1, $data['filledPositions']);

        $stored = $this->freshEm()->getRepository(Petition::class)->find($petition->getId());
        self::assertSame('Geändert', $stored->getTitle());
    }

    public function testPutWithMalformedJsonByCreatorIs400(): void
    {
        $petition = $this->createPetition('basic');

        self::assertSame(400, $this->jsonRequest('PUT', '/petitions/'.$petition->getId(), '{"title":', 'basic')->getStatusCode());
    }

    // ---- JSON-LD ---------------------------------------------------------

    public function testJsonLdCollectionHasHydraKeys(): void
    {
        $this->createPetition('basic');

        $response = $this->jsonRequest('GET', '/petitions', null, 'basic', 'application/ld+json');
        $data = $this->decode($response);

        self::assertStringContainsString('ld+json', (string) $response->headers->get('Content-Type'));
        self::assertSame('/contexts/Petition', $data['@context']);
        self::assertSame('/petitions', $data['@id']);
        self::assertSame('hydra:Collection', $data['@type']);
        self::assertSame('/users/3', $data['hydra:member'][0]['createUser']);
    }

    public function testJsonLdItemHasIdAndTypeAndCreatorIri(): void
    {
        $petition = $this->createPetition('basic');

        $data = $this->decode($this->jsonRequest('GET', '/petitions/'.$petition->getId(), null, 'basic', 'application/ld+json'));

        self::assertSame('/petitions/'.$petition->getId(), $data['@id']);
        self::assertSame('Petition', $data['@type']);
        self::assertSame('/users/3', $data['createUser']);
    }
}
