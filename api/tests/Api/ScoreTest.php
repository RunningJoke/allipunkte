<?php

namespace App\Tests\Api;

use App\Entity\Cycle;
use App\Entity\Score;
use App\Entity\Transaction;
use App\Entity\User;

/**
 * ScoreController: GET /myScore, POST /api/scores/{id}/setPaymentStatus, GET /transfers, POST /transfer.
 *
 * Fixture scores start at 0. Points enter the system through a transfer with createPoints by a creator
 * (ROLE_CREATOR or ROLE_ADMIN), which is how the tests give users something to send.
 */
class ScoreTest extends ApiTestCase
{
    // ---- GET /myScore ------------------------------------------------------

    public function testMyScoreRequiresLogin(): void
    {
        $response = $this->jsonRequest('GET', '/myScore');

        self::assertSame(401, $response->getStatusCode());
    }

    public function testMyScoreReturnsExactKeyAndCurrentAmount(): void
    {
        $response = $this->jsonRequest('GET', '/myScore', null, 'basic');

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(['userScore' => 0], $this->decode($response));
    }

    public function testMyScoreReturnsStoredAmount(): void
    {
        $this->setScoreAmount('basic', 7);

        self::assertSame(['userScore' => 7], $this->decode($this->jsonRequest('GET', '/myScore', null, 'basic')));
    }

    public function testMyScoreWithoutActiveCycleIsZero(): void
    {
        $this->endCurrentCycle();

        self::assertSame(['userScore' => 0], $this->decode($this->jsonRequest('GET', '/myScore', null, 'basic')));
    }

    // ---- POST /api/scores/{id}/setPaymentStatus (ROLE_ADMIN) -----------------

    private function scoreIdOf(string $username): int
    {
        $em = static::em();
        $score = $this->user($username)->getScoreForCycle($em->getRepository(Cycle::class)->findCurrentCycle());

        return $score->getId();
    }

    public function testSetPaymentStatusRequiresLogin(): void
    {
        $id = $this->scoreIdOf('basic');

        self::assertSame(401, $this->jsonRequest('POST', "/api/scores/$id/setPaymentStatus", ['newPaymentStatus' => 3])->getStatusCode());
    }

    public function testSetPaymentStatusForBasicIs403(): void
    {
        $id = $this->scoreIdOf('basic');

        self::assertSame(403, $this->jsonRequest('POST', "/api/scores/$id/setPaymentStatus", ['newPaymentStatus' => 3], 'basic')->getStatusCode());
    }

    public function testSetPaymentStatusForCreatorIs403(): void
    {
        $id = $this->scoreIdOf('basic');

        self::assertSame(403, $this->jsonRequest('POST', "/api/scores/$id/setPaymentStatus", ['newPaymentStatus' => 3], 'creator')->getStatusCode());
    }

    public function testSetPaymentStatusForAdminUpdatesScore(): void
    {
        $id = $this->scoreIdOf('basic');

        $response = $this->jsonRequest('POST', "/api/scores/$id/setPaymentStatus", ['newPaymentStatus' => 3], 'admin');

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(['paymentStatus' => 3], $this->decode($response));
        self::assertSame(Score::PAYMENT_FULFILLED, $this->freshEm()->getRepository(Score::class)->find($id)->getPaymentStatus());
    }

    public function testSetPaymentStatusWithoutValueIs500(): void
    {
        // NOTE: current behaviour, possibly a bug: a missing newPaymentStatus reads an undefined key
        // and gives a 500 instead of a 400.
        $id = $this->scoreIdOf('basic');

        $response = $this->jsonRequest('POST', "/api/scores/$id/setPaymentStatus", [], 'admin');

        self::assertSame(500, $response->getStatusCode());
        self::assertStringContainsString('Undefined array key "newPaymentStatus"', (string) $this->decode($response)['detail']);
    }

    public function testSetPaymentStatusForUnknownScoreFailsWithError(): void
    {
        // NOTE: current behaviour, possibly a bug: findOneById() returns null and the method is called on
        // it, which is an Error (500) and not a 404.
        $this->expectException(\Error::class);
        $this->jsonRequest('POST', '/api/scores/999/setPaymentStatus', ['newPaymentStatus' => 3], 'admin');
    }

    // ---- GET /transfers -----------------------------------------------------

    public function testTransfersRequiresLogin(): void
    {
        self::assertSame(401, $this->jsonRequest('GET', '/transfers')->getStatusCode());
    }

    public function testTransfersIsEmptyBeforeAnyTransfer(): void
    {
        $response = $this->jsonRequest('GET', '/transfers', null, 'basic');

        self::assertSame(200, $response->getStatusCode());
        self::assertSame([], $this->decode($response));
    }

    public function testTransfersReturnsExactKeysAndIrisForParticipants(): void
    {
        $this->givePoints('basic', 5);

        $list = $this->decode($this->jsonRequest('GET', '/transfers', null, 'basic'));

        self::assertCount(1, $list);
        $transfer = $list[0];
        $this->assertKeysExactly(['id', 'origin', 'target', 'description', 'amount', 'timestamp', 'cycle', 'createdPoints'], $transfer);
        $this->assertIri('users', $transfer['origin']);
        $this->assertIri('users', $transfer['target']);
        self::assertSame('/users/2', $transfer['origin']);
        self::assertSame('/users/3', $transfer['target']);
        self::assertSame('/cycles/1', $transfer['cycle']);
        self::assertSame(5, $transfer['amount']);
        self::assertTrue($transfer['createdPoints']);
        self::assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/', $transfer['timestamp']);
    }

    public function testTransfersOnlyContainsOwnTransactions(): void
    {
        $this->givePoints('admin', 5);

        self::assertSame([], $this->decode($this->jsonRequest('GET', '/transfers', null, 'basic')));
        self::assertCount(1, $this->decode($this->jsonRequest('GET', '/transfers', null, 'admin')));
    }

    // ---- POST /transfer -----------------------------------------------------

    /**
     * The request body as SendPoints.vue sends it: one object per row, with the extra "user" field.
     */
    private function transferBody(string $targetIri, $amount, string $description = 'Danke', bool $createPoints = false): array
    {
        return [[
            'user' => 'Name egal',
            'target' => $targetIri,
            'amount' => $amount,
            'description' => $description,
            'createPoints' => $createPoints,
        ]];
    }

    /**
     * Gives a user points through a creator (createPoints=true).
     */
    private function givePoints(string $username, int $amount): void
    {
        $id = $this->user($username)->getId();
        $response = $this->jsonRequest('POST', '/transfer', $this->transferBody("/users/$id", $amount, 'Startpunkte', true), 'creator');
        self::assertSame(200, $response->getStatusCode(), 'could not give starting points');
    }

    private function amountOf(string $username): int
    {
        $em = $this->freshEm();
        $cycle = $em->getRepository(Cycle::class)->findCurrentCycle();

        return $em->getRepository(User::class)->findOneBy(['username' => $username])->getScoreForCycle($cycle)->getAmount();
    }

    private function transactionCount(): int
    {
        return $this->freshEm()->getRepository(Transaction::class)->count([]);
    }

    public function testTransferRequiresLogin(): void
    {
        self::assertSame(401, $this->jsonRequest('POST', '/transfer', $this->transferBody('/users/3', 5))->getStatusCode());
    }

    public function testCreatorCanCreatePointsForAnotherUser(): void
    {
        $response = $this->jsonRequest('POST', '/transfer', $this->transferBody('/users/3', 5, 'Startpunkte', true), 'creator');

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(['userScore' => 0], $this->decode($response));
        self::assertSame(5, $this->amountOf('basic'));
        self::assertSame(0, $this->amountOf('creator'), 'created points do not reduce the sender');
        self::assertSame(1, $this->transactionCount());
    }

    public function testTransferSubtractsFromSenderAndAddsToTarget(): void
    {
        $this->givePoints('basic', 5);

        $response = $this->jsonRequest('POST', '/transfer', $this->transferBody('/users/1', 3), 'basic');

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(['userScore' => 2], $this->decode($response));
        self::assertSame(2, $this->amountOf('basic'));
        self::assertSame(3, $this->amountOf('admin'));

        $stored = $this->freshEm()->getRepository(Transaction::class)->findOneBy(['description' => 'Danke']);
        self::assertNotNull($stored);
        self::assertFalse($stored->getCreatedPoints());
        self::assertSame(3, $stored->getAmount());
    }

    public function testNumericStringAmountIsAccepted(): void
    {
        $this->givePoints('basic', 5);

        $response = $this->jsonRequest('POST', '/transfer', $this->transferBody('/users/1', '2'), 'basic');

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(3, $this->amountOf('basic'));
    }

    public function testBasicUserCannotCreatePoints(): void
    {
        $this->givePoints('basic', 5);

        $response = $this->jsonRequest('POST', '/transfer', $this->transferBody('/users/1', 5, 'x', true), 'basic');

        self::assertSame(400, $response->getStatusCode());
        self::assertSame('not allowed to create points', $this->decode($response)['detail']);
    }

    public function testTransferWithoutEnoughPointsIs400AndWritesNothing(): void
    {
        $response = $this->jsonRequest('POST', '/transfer', $this->transferBody('/users/1', 5), 'basic');

        self::assertSame(400, $response->getStatusCode());
        self::assertSame('user does not have enough points', $this->decode($response)['detail']);
        self::assertSame(0, $this->amountOf('basic'));
        self::assertSame(0, $this->amountOf('admin'));
        self::assertSame(0, $this->transactionCount());
    }

    public function testOneFailingRowRollsBackTheWholeBatch(): void
    {
        $this->givePoints('basic', 5);
        $body = array_merge(
            $this->transferBody('/users/1', 2),
            $this->transferBody('/users/1', -1)
        );

        $response = $this->jsonRequest('POST', '/transfer', $body, 'basic');

        self::assertSame(400, $response->getStatusCode());
        self::assertSame(5, $this->amountOf('basic'), 'the valid first row must not be applied');
        self::assertSame(0, $this->amountOf('admin'));
        self::assertSame(1, $this->transactionCount(), 'only the points creation from givePoints exists');
    }

    public function testNegativeAmountIs400(): void
    {
        $response = $this->jsonRequest('POST', '/transfer', $this->transferBody('/users/1', -1), 'creator');

        self::assertSame(400, $response->getStatusCode());
        self::assertSame("can't send negative points", $this->decode($response)['detail']);
    }

    public function testZeroAmountIs400(): void
    {
        $response = $this->jsonRequest('POST', '/transfer', $this->transferBody('/users/1', 0), 'creator');

        self::assertSame(400, $response->getStatusCode());
    }

    public function testSendingToYourselfIs400(): void
    {
        $response = $this->jsonRequest('POST', '/transfer', $this->transferBody('/users/3', 1), 'basic');

        self::assertSame(400, $response->getStatusCode());
        self::assertSame("can't send points to yourself", $this->decode($response)['detail']);
    }

    public function testTransferWithInvalidJsonIs400(): void
    {
        $response = $this->jsonRequest('POST', '/transfer', '{"not": json', 'basic');

        self::assertSame(400, $response->getStatusCode());
        self::assertSame('invalid json format', $this->decode($response)['detail']);
    }

    public function testTransferToUnknownUserIs500(): void
    {
        // NOTE: current behaviour, possibly a bug: an unknown target IRI raises ItemNotFoundException,
        // which is not turned into a 400.
        $response = $this->jsonRequest('POST', '/transfer', $this->transferBody('/users/999', 1), 'creator');

        self::assertSame(500, $response->getStatusCode());
        self::assertStringContainsString('Item not found for "/users/999"', (string) $this->decode($response)['detail']);
    }

    public function testTransferIsSilentlyIgnoredWhenCycleIsClosed(): void
    {
        // NOTE: current behaviour, possibly a bug: with a closed cycle the request succeeds with
        // userScore 0 and no error, so the frontend cannot tell that nothing was sent.
        $this->closeCurrentCycle();

        $response = $this->jsonRequest('POST', '/transfer', $this->transferBody('/users/3', 5, 'x', true), 'creator');

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(['userScore' => 0], $this->decode($response));
        self::assertSame(0, $this->amountOf('basic'));
        self::assertSame(0, $this->transactionCount());
    }

    public function testTransferIsSilentlyIgnoredWithoutActiveCycle(): void
    {
        $this->endCurrentCycle();

        $response = $this->jsonRequest('POST', '/transfer', $this->transferBody('/users/3', 5, 'x', true), 'creator');

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(['userScore' => 0], $this->decode($response));
        self::assertSame(0, $this->transactionCount());
    }

    private function closeCurrentCycle(): void
    {
        $em = static::em();
        $cycle = $em->getRepository(Cycle::class)->findCurrentCycle();
        $cycle->setStatus(Cycle::STATUS_CLOSED);
        $em->flush();
    }
}
