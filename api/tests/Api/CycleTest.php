<?php

namespace App\Tests\Api;

use App\Entity\Cycle;
use App\Entity\Score;

/**
 * API Platform resource App\Entity\Cycle (/cycles, /cycles/{id}), the custom POST /api/cycles
 * (CycleController::createNewCycle) and GET /pastCycles.
 */
class CycleTest extends ApiTestCase
{
    private const CYCLE_KEYS = ['id', 'name', 'description', 'costPerPoint', 'fromDate', 'toDate'];

    private const DATE_PATTERN = '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:?\d{2}$/';

    // ---- GET /cycles and /cycles/{id} ------------------------------------

    public function testCollectionRequiresLogin(): void
    {
        self::assertSame(401, $this->jsonRequest('GET', '/cycles')->getStatusCode());
    }

    public function testCollectionForBasicUserReturnsExactFields(): void
    {
        $response = $this->jsonRequest('GET', '/cycles', null, 'basic');
        $list = $this->decode($response);

        self::assertSame(200, $response->getStatusCode());
        self::assertCount(1, $list);
        $this->assertKeysExactly(self::CYCLE_KEYS, $list[0]);
        self::assertSame(1, $list[0]['id']);
        self::assertSame('Testzyklus', $list[0]['name']);
        self::assertSame(500, $list[0]['costPerPoint']);
        self::assertMatchesRegularExpression(self::DATE_PATTERN, $list[0]['fromDate']);
        self::assertMatchesRegularExpression(self::DATE_PATTERN, $list[0]['toDate']);
    }

    public function testCollectionForAdminHasSameFieldsAsForUsers(): void
    {
        $list = $this->decode($this->jsonRequest('GET', '/cycles', null, 'admin'));

        $this->assertKeysExactly(self::CYCLE_KEYS, $list[0]);
    }

    public function testItemForBasicUserReturnsExactFields(): void
    {
        $response = $this->jsonRequest('GET', '/cycles/1', null, 'basic');
        $data = $this->decode($response);

        self::assertSame(200, $response->getStatusCode());
        $this->assertKeysExactly(self::CYCLE_KEYS, $data);
        self::assertSame(1, $data['id']);
    }

    public function testItemUnknownIdIs404(): void
    {
        self::assertSame(404, $this->jsonRequest('GET', '/cycles/999', null, 'basic')->getStatusCode());
    }

    // ---- PUT /cycles/{id} (ROLE_ADMIN only) --------------------------------

    public function testPutRequiresLogin(): void
    {
        self::assertSame(401, $this->jsonRequest('PUT', '/cycles/1', ['name' => 'x'])->getStatusCode());
    }

    public function testPutForBasicIs403AndChangesNothing(): void
    {
        $response = $this->jsonRequest('PUT', '/cycles/1', ['name' => 'Hacked'], 'basic');

        self::assertSame(403, $response->getStatusCode());
        self::assertSame('Testzyklus', $this->freshEm()->getRepository(Cycle::class)->find(1)->getName());
    }

    public function testPutForAdminUpdatesCycle(): void
    {
        $body = [
            'id' => 1,
            'name' => 'Umbenannt',
            'description' => 'neue Beschreibung',
            'costPerPoint' => 750,
            'fromDate' => '2026-01-01T00:00:00+01:00',
            'toDate' => '2028-01-01T00:00:00+01:00',
        ];

        $response = $this->jsonRequest('PUT', '/cycles/1', $body, 'admin');
        $data = $this->decode($response);

        self::assertSame(200, $response->getStatusCode());
        $this->assertKeysExactly(self::CYCLE_KEYS, $data);
        self::assertSame('Umbenannt', $data['name']);

        $stored = $this->freshEm()->getRepository(Cycle::class)->find(1);
        self::assertSame('Umbenannt', $stored->getName());
        self::assertSame('neue Beschreibung', $stored->getDescription());
        self::assertSame(750, $stored->getCostPerPoint());
    }

    public function testPutWithMalformedJsonIs400(): void
    {
        self::assertSame(400, $this->jsonRequest('PUT', '/cycles/1', '{"name":', 'admin')->getStatusCode());
    }

    // ---- POST /api/cycles (cycle_create) -----------------------------------

    private const NEW_CYCLE = [
        'name' => 'Zukunft',
        'description' => 'kommende Saison',
        'fromDate' => '2030-01-01',
        'toDate' => '2030-12-31',
        'costPerPoint' => 100,
        'targetAmount' => 10,
    ];

    public function testCreateCycleRequiresLogin(): void
    {
        self::assertSame(401, $this->jsonRequest('POST', '/api/cycles', self::NEW_CYCLE)->getStatusCode());
    }

    public function testCreateCycleForBasicIs403(): void
    {
        self::assertSame(403, $this->jsonRequest('POST', '/api/cycles', self::NEW_CYCLE, 'basic')->getStatusCode());
    }

    public function testCreateCycleForAdminReturns201WithExactFields(): void
    {
        $response = $this->jsonRequest('POST', '/api/cycles', self::NEW_CYCLE, 'admin');
        $data = $this->decode($response);

        self::assertSame(201, $response->getStatusCode());
        $this->assertKeysExactly(self::CYCLE_KEYS, $data);
        self::assertSame('Zukunft', $data['name']);
        self::assertSame(100, $data['costPerPoint']);
        self::assertMatchesRegularExpression(self::DATE_PATTERN, $data['fromDate']);
    }

    public function testCreateCycleAddsAScoreForEveryExistingUser(): void
    {
        $this->jsonRequest('POST', '/api/cycles', self::NEW_CYCLE, 'admin');

        $em = $this->freshEm();
        $cycle = $em->getRepository(Cycle::class)->findOneBy(['name' => 'Zukunft']);
        self::assertNotNull($cycle);
        self::assertSame(Cycle::STATUS_OPEN, $cycle->getStatus());

        $scores = $em->getRepository(Score::class)->findBy(['cycle' => $cycle]);
        self::assertCount(3, $scores);
        foreach ($scores as $score) {
            self::assertSame(10, $score->getTargetAmount());
            self::assertSame(0, $score->getAmount());
            self::assertSame(Score::PAYMENT_NOTSET, $score->getPaymentStatus());
        }
    }

    public function testCreateCycleWithUnknownFieldIs400AndCreatesNothing(): void
    {
        // invalid input: field not in the allow list
        $response = $this->jsonRequest('POST', '/api/cycles', self::NEW_CYCLE + ['foo' => 1], 'admin');

        self::assertSame(400, $response->getStatusCode());
        self::assertSame(['illegal request format'], $this->decode($response));
        self::assertNull($this->freshEm()->getRepository(Cycle::class)->findOneBy(['name' => 'Zukunft']));
    }

    public function testCreateCycleWithMissingFieldIs500(): void
    {
        // NOTE: current behaviour, possibly a bug: only unknown fields are rejected, a missing field
        // (here "toDate") reads an undefined array key and gives a 500.
        $body = self::NEW_CYCLE;
        unset($body['toDate']);

        $response = $this->jsonRequest('POST', '/api/cycles', $body, 'admin');

        self::assertSame(500, $response->getStatusCode());
        self::assertStringContainsString('Undefined array key "toDate"', (string) $this->decode($response)['detail']);
    }

    // ---- GET /pastCycles -------------------------------------------------

    public function testPastCyclesRequiresLogin(): void
    {
        self::assertSame(401, $this->jsonRequest('GET', '/pastCycles')->getStatusCode());
    }

    public function testPastCyclesReturnsOwnScoresWithNestedCycleAndUser(): void
    {
        $response = $this->jsonRequest('GET', '/pastCycles', null, 'basic');
        $list = $this->decode($response);

        self::assertSame(200, $response->getStatusCode());
        self::assertCount(1, $list);
        $this->assertKeysExactly(['id', 'cycle', 'user', 'amount', 'targetAmount', 'paymentStatus'], $list[0]);
        $this->assertKeysExactly(self::CYCLE_KEYS, $list[0]['cycle']);
        $this->assertKeysExactly(['id', 'firstname', 'lastname'], $list[0]['user']);
        self::assertSame(3, $list[0]['user']['id']);
        self::assertSame(20, $list[0]['targetAmount']);
    }

    public function testPastCyclesIncludesTheActiveCycle(): void
    {
        // NOTE: current behaviour, possibly a bug: the name says "past", but the query returns every
        // score of the user, including the one for the active cycle (id 1 here).
        $list = $this->decode($this->jsonRequest('GET', '/pastCycles', null, 'basic'));

        self::assertSame(1, $list[0]['cycle']['id']);
        self::assertSame(0, $list[0]['amount']);
    }

    // ---- JSON-LD ---------------------------------------------------------

    public function testJsonLdCollectionHasHydraKeys(): void
    {
        $response = $this->jsonRequest('GET', '/cycles', null, 'basic', 'application/ld+json');
        $data = $this->decode($response);

        self::assertStringContainsString('ld+json', (string) $response->headers->get('Content-Type'));
        self::assertSame('/contexts/Cycle', $data['@context']);
        self::assertSame('/cycles', $data['@id']);
        self::assertSame('hydra:Collection', $data['@type']);
        self::assertSame('/cycles/1', $data['hydra:member'][0]['@id']);
    }

    public function testJsonLdItemHasIdAndType(): void
    {
        $data = $this->decode($this->jsonRequest('GET', '/cycles/1', null, 'basic', 'application/ld+json'));

        self::assertSame('/cycles/1', $data['@id']);
        self::assertSame('Cycle', $data['@type']);
    }
}
