<?php

namespace App\Tests\Api;

use App\Entity\User;

/**
 * API Platform resource App\Entity\User: /users and /users/{id}.
 *
 * Normalization groups: USER_READ for everyone, USER_READ_ADMIN added by
 * UserContextBuilder only for ROLE_ADMIN.
 */
class UserTest extends ApiTestCase
{
    private const PUBLIC_KEYS = ['id', 'firstname', 'lastname'];

    private const ADMIN_KEYS = ['id', 'firstname', 'lastname', 'username', 'license', 'mail', 'scores', 'isCreator', 'isAdmin'];

    // ---- GET /users -------------------------------------------------------

    public function testCollectionRequiresLogin(): void
    {
        $response = $this->jsonRequest('GET', '/users');

        self::assertSame(401, $response->getStatusCode());
    }

    public function testCollectionForBasicUserReturnsOnlyPublicFields(): void
    {
        $response = $this->jsonRequest('GET', '/users', null, 'basic');
        $list = $this->decode($response);

        self::assertSame(200, $response->getStatusCode());
        self::assertStringStartsWith('application/json', (string) $response->headers->get('Content-Type'));
        self::assertCount(3, $list);
        foreach ($list as $item) {
            $this->assertKeysExactly(self::PUBLIC_KEYS, $item);
        }
    }

    public function testCollectionForCreatorDoesNotGetAdminFields(): void
    {
        // ROLE_CREATOR is not ROLE_ADMIN, so the admin group must not be added
        $list = $this->decode($this->jsonRequest('GET', '/users', null, 'creator'));

        foreach ($list as $item) {
            $this->assertKeysExactly(self::PUBLIC_KEYS, $item);
        }
    }

    public function testCollectionForAdminAddsAdminFields(): void
    {
        $list = $this->decode($this->jsonRequest('GET', '/users', null, 'admin'));

        self::assertCount(3, $list);
        foreach ($list as $item) {
            $this->assertKeysExactly(self::ADMIN_KEYS, $item);
        }

        $admin = $list[0];
        self::assertSame('admin', $admin['username']);
        self::assertTrue($admin['isAdmin']);
        self::assertTrue($admin['isCreator']);

        $basic = $list[2];
        self::assertFalse($basic['isAdmin']);
        self::assertFalse($basic['isCreator']);
    }

    public function testAdminScoresHaveExactKeysAndNestedCycle(): void
    {
        $list = $this->decode($this->jsonRequest('GET', '/users', null, 'admin'));

        $score = $list[0]['scores'][0];
        $this->assertKeysExactly(['id', 'cycle', 'amount', 'targetAmount', 'paymentStatus'], $score);
        // in plain JSON the cycle is nested, not an IRI (the JSON-LD test below checks the IRI)
        self::assertIsArray($score['cycle']);
        self::assertSame(1, $score['cycle']['id']);
        self::assertSame(0, $score['amount']);
        self::assertSame(15, $score['targetAmount']);
        self::assertSame(0, $score['paymentStatus']);
    }

    // ---- GET /users/{id} --------------------------------------------------

    public function testItemRequiresLogin(): void
    {
        $response = $this->jsonRequest('GET', '/users/1');

        self::assertSame(401, $response->getStatusCode());
    }

    public function testItemForBasicUserReturnsOnlyPublicFields(): void
    {
        $response = $this->jsonRequest('GET', '/users/1', null, 'basic');

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(['id' => 1, 'firstname' => 'Peter', 'lastname' => 'Administrator'], $this->decode($response));
    }

    public function testItemForAdminReturnsAdminFields(): void
    {
        $data = $this->decode($this->jsonRequest('GET', '/users/1', null, 'admin'));

        $this->assertKeysExactly(self::ADMIN_KEYS, $data);
        self::assertSame('admin@example.de', $data['mail']);
        self::assertSame('1234', $data['license']);
    }

    public function testItemUnknownIdIs404(): void
    {
        $response = $this->jsonRequest('GET', '/users/999', null, 'admin');

        self::assertSame(404, $response->getStatusCode());
    }

    // ---- PUT /users/{id} (ROLE_ADMIN only) --------------------------------

    public function testPutRequiresLogin(): void
    {
        $response = $this->jsonRequest('PUT', '/users/3', ['firstname' => 'X']);

        self::assertSame(401, $response->getStatusCode());
    }

    public function testPutForBasicUserIs403AndChangesNothing(): void
    {
        $response = $this->jsonRequest('PUT', '/users/3', ['firstname' => 'Hacked'], 'basic');

        self::assertSame(403, $response->getStatusCode());
        self::assertSame('Marta', $this->freshEm()->getRepository(User::class)->find(3)->getFirstname());
    }

    public function testPutForAdminUpdatesTheUserAndReturnsAdminFields(): void
    {
        // same shape the admin UI sends (scores and password are removed there)
        $body = [
            'id' => 3,
            'firstname' => 'Martha',
            'lastname' => 'Changed',
            'username' => 'basic',
            'license' => '1234',
            'mail' => 'basic@example.de',
            'targetAmount' => 20,
            'isAdmin' => false,
            'isCreator' => false,
        ];

        $response = $this->jsonRequest('PUT', '/users/3', $body, 'admin');
        $data = $this->decode($response);

        self::assertSame(200, $response->getStatusCode());
        $this->assertKeysExactly(self::ADMIN_KEYS, $data);
        self::assertSame('Changed', $data['lastname']);

        $stored = $this->freshEm()->getRepository(User::class)->find(3);
        self::assertSame('Changed', $stored->getLastname());
        self::assertSame('Martha', $stored->getFirstname());
    }

    public function testPutWithMalformedJsonIs400(): void
    {
        // invalid input
        $response = $this->jsonRequest('PUT', '/users/3', '{"firstname":', 'admin');

        self::assertSame(400, $response->getStatusCode());
    }

    // ---- JSON-LD (Accept: application/ld+json) ----------------------------

    public function testJsonLdCollectionHasHydraKeys(): void
    {
        $response = $this->jsonRequest('GET', '/users', null, 'basic', 'application/ld+json');
        $data = $this->decode($response);

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('ld+json', (string) $response->headers->get('Content-Type'));
        self::assertSame('/contexts/User', $data['@context']);
        self::assertSame('/users', $data['@id']);
        self::assertSame('hydra:Collection', $data['@type']);
        self::assertCount(3, $data['hydra:member']);
        self::assertSame('/users/1', $data['hydra:member'][0]['@id']);
    }

    public function testJsonLdItemHasIdAndType(): void
    {
        $data = $this->decode($this->jsonRequest('GET', '/users/3', null, 'basic', 'application/ld+json'));

        self::assertSame('/contexts/User', $data['@context']);
        self::assertSame('/users/3', $data['@id']);
        self::assertSame('User', $data['@type']);
    }

    public function testJsonLdItemReferencesCycleByIri(): void
    {
        $data = $this->decode($this->jsonRequest('GET', '/users/1', null, 'admin', 'application/ld+json'));

        self::assertSame('/cycles/1', $data['scores'][0]['cycle']['@id']);
        self::assertSame('Cycle', $data['scores'][0]['cycle']['@type']);
    }
}
