<?php

namespace App\Tests\Api;

use App\Entity\Cycle;
use App\Entity\Score;
use App\Entity\User;

/**
 * The custom user endpoints of UserController (these are not API Platform operations):
 * POST /api/users, POST /api/users/{id}/setTargetPoints, POST /updatePassword, POST /updatePasswordAdmin.
 *
 * Password checks go through the real /jlogin endpoint, so they test what a user can actually do.
 */
class UserManagementTest extends ApiTestCase
{
    // ---- POST /api/users (user_create) ------------------------------------

    private const NEW_USER = [
        'firstname' => 'Anna',
        'lastname' => 'Neu',
        'username' => 'anna',
        'mail' => 'anna@example.de',
        'license' => 'L-42',
        'targetAmount' => 5,
        'password' => 'geheim1',
    ];

    public function testCreateUserRequiresLogin(): void
    {
        $response = $this->jsonRequest('POST', '/api/users', self::NEW_USER);

        self::assertSame(401, $response->getStatusCode());
    }

    public function testCreateUserForBasicIs403AndCreatesNothing(): void
    {
        $response = $this->jsonRequest('POST', '/api/users', self::NEW_USER, 'basic');

        self::assertSame(403, $response->getStatusCode());
        self::assertNull($this->freshEm()->getRepository(User::class)->findOneBy(['username' => 'anna']));
    }

    public function testCreateUserForAdminCreatesUserWithScoreForCurrentCycle(): void
    {
        $response = $this->jsonRequest('POST', '/api/users', self::NEW_USER, 'admin');

        self::assertSame(201, $response->getStatusCode());
        // the response echoes the request body (note: including the plain password)
        $this->assertKeysExactly(array_keys(self::NEW_USER), $this->decode($response));

        $em = $this->freshEm();
        $user = $em->getRepository(User::class)->findOneBy(['username' => 'anna']);
        self::assertNotNull($user);
        // getRoles() merges ROLE_USER in again, so it can appear twice; only membership is pinned
        self::assertContains('ROLE_USER', $user->getRoles());
        self::assertNotContains('ROLE_ADMIN', $user->getRoles());

        $score = $user->getScoreForCycle($em->getRepository(Cycle::class)->findCurrentCycle());
        self::assertNotNull($score, 'new user must get a score for the active cycle');
        self::assertSame(0, $score->getAmount());
        self::assertSame(5, $score->getTargetAmount());
        self::assertSame(Score::PAYMENT_NOTSET, $score->getPaymentStatus());
    }

    public function testNewUserCanLogInWithGivenPassword(): void
    {
        $this->jsonRequest('POST', '/api/users', self::NEW_USER, 'admin');

        $response = $this->jsonRequest('POST', '/jlogin', ['username' => 'anna', 'password' => 'geheim1']);

        self::assertSame(200, $response->getStatusCode());
    }

    public function testNewUserWithoutPasswordUsesLicenseAsPassword(): void
    {
        $body = self::NEW_USER;
        unset($body['password']);

        $this->jsonRequest('POST', '/api/users', $body, 'admin');

        self::assertSame(200, $this->jsonRequest('POST', '/jlogin', ['username' => 'anna', 'password' => 'L-42'])->getStatusCode());
    }

    public function testNewAdminUserGetsAdminAndCreatorRoles(): void
    {
        $body = ['isAdmin' => true, 'isCreator' => true] + self::NEW_USER;

        $this->jsonRequest('POST', '/api/users', $body, 'admin');

        $user = $this->freshEm()->getRepository(User::class)->findOneBy(['username' => 'anna']);
        self::assertContains('ROLE_ADMIN', $user->getRoles());
        self::assertContains('ROLE_CREATOR', $user->getRoles());
    }

    public function testCreateUserWithUnknownFieldIs400(): void
    {
        // invalid input: field not in the allow list
        $response = $this->jsonRequest('POST', '/api/users', self::NEW_USER + ['unknown' => 1], 'admin');

        self::assertSame(400, $response->getStatusCode());
        self::assertSame(['illegal request format'], $this->decode($response));
    }

    public function testCreateUserWithMissingFieldIs500(): void
    {
        // NOTE: current behaviour, possibly a bug: only unknown fields are rejected, a missing
        // required field (here "lastname") reads an undefined array key and gives a 500.
        $body = self::NEW_USER;
        unset($body['lastname']);

        $response = $this->jsonRequest('POST', '/api/users', $body, 'admin');

        self::assertSame(500, $response->getStatusCode());
        self::assertStringContainsString('Undefined array key "lastname"', (string) $this->decode($response)['detail']);
    }

    // ---- POST /api/users/{id}/setTargetPoints (user_update_target_points) --

    public function testSetTargetPointsRequiresLogin(): void
    {
        $response = $this->jsonRequest('POST', '/api/users/3/setTargetPoints', ['newTargetScore' => 42]);

        self::assertSame(401, $response->getStatusCode());
    }

    public function testSetTargetPointsForBasicIs403(): void
    {
        $response = $this->jsonRequest('POST', '/api/users/3/setTargetPoints', ['newTargetScore' => 42], 'basic');

        self::assertSame(403, $response->getStatusCode());
        self::assertSame(20, $this->currentTargetOf('basic'));
    }

    public function testSetTargetPointsForAdminUpdatesCurrentScore(): void
    {
        $response = $this->jsonRequest('POST', '/api/users/3/setTargetPoints', ['newTargetScore' => 42], 'admin');

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(['targetScore' => 42], $this->decode($response));
        self::assertSame(42, $this->currentTargetOf('basic'));
    }

    public function testSetTargetPointsForUnknownUserIs404(): void
    {
        $response = $this->jsonRequest('POST', '/api/users/999/setTargetPoints', ['newTargetScore' => 42], 'admin');

        self::assertSame(404, $response->getStatusCode());
        self::assertSame(['status' => 404, 'message' => 'User not found'], $this->decode($response));
    }

    public function testSetTargetPointsWithoutNewTargetScoreIs400(): void
    {
        // invalid input
        $response = $this->jsonRequest('POST', '/api/users/3/setTargetPoints', [], 'admin');

        self::assertSame(400, $response->getStatusCode());
        self::assertSame(['status' => 400, 'message' => 'Invalid request'], $this->decode($response));
    }

    public function testSetTargetPointsWithoutActiveCycleFailsWithTypeError(): void
    {
        // NOTE: current behaviour, possibly a bug: with no active cycle findCurrentCycle() returns null,
        // which getScoreForCycle(Cycle $cycle) rejects, so the 404 branch is never reached.
        $this->endCurrentCycle();

        $this->expectException(\TypeError::class);
        $this->jsonRequest('POST', '/api/users/3/setTargetPoints', ['newTargetScore' => 42], 'admin');
    }

    // ---- POST /updatePassword (user_update_password, own password) ----------

    public function testUpdatePasswordAnonymousFailsWithTypeError(): void
    {
        // NOTE: current behaviour, possibly a bug: /updatePassword has no access check. An anonymous
        // request reaches hashPassword(null) and crashes instead of answering 401.
        $this->expectException(\TypeError::class);
        $this->jsonRequest('POST', '/updatePassword', ['oldPassword' => '1234', 'newPassword' => 'abcdef', 'newPasswordVerification' => 'abcdef']);
    }

    public function testUpdatePasswordChangesOwnPassword(): void
    {
        $response = $this->jsonRequest(
            'POST',
            '/updatePassword',
            ['oldPassword' => '1234', 'newPassword' => 'abcdef', 'newPasswordVerification' => 'abcdef'],
            'basic'
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(['status' => 200, 'message' => 'password updated'], $this->decode($response));

        self::assertSame(200, $this->jsonRequest('POST', '/jlogin', ['username' => 'basic', 'password' => 'abcdef'])->getStatusCode());
        self::assertSame(401, $this->jsonRequest('POST', '/jlogin', ['username' => 'basic', 'password' => '1234'])->getStatusCode());
    }

    public function testUpdatePasswordWithMismatchedVerificationIs400AndKeepsOldPassword(): void
    {
        $response = $this->jsonRequest(
            'POST',
            '/updatePassword',
            ['oldPassword' => '1234', 'newPassword' => 'abcdef', 'newPasswordVerification' => 'xxxxxx'],
            'basic'
        );

        self::assertSame(400, $response->getStatusCode());
        self::assertSame(['status' => 400, 'message' => 'invalid request'], $this->decode($response));
        self::assertSame(200, $this->jsonRequest('POST', '/jlogin', ['username' => 'basic', 'password' => '1234'])->getStatusCode());
    }

    public function testUpdatePasswordWithoutFieldsIs400(): void
    {
        // invalid input: oldPassword is not checked at all, but the other two fields are required
        $response = $this->jsonRequest('POST', '/updatePassword', ['newPassword' => 'abcdef'], 'basic');

        self::assertSame(400, $response->getStatusCode());
    }

    // ---- POST /updatePasswordAdmin (user_update_password_admin) -----------

    public function testUpdatePasswordAdminRequiresLogin(): void
    {
        $response = $this->jsonRequest('POST', '/updatePasswordAdmin', ['targetUser' => '/users/3', 'newPassword' => 'nn', 'newPasswordVerification' => 'nn']);

        self::assertSame(401, $response->getStatusCode());
    }

    public function testUpdatePasswordAdminForBasicIs403(): void
    {
        $response = $this->jsonRequest('POST', '/updatePasswordAdmin', ['targetUser' => '/users/1', 'newPassword' => 'nn', 'newPasswordVerification' => 'nn'], 'basic');

        self::assertSame(403, $response->getStatusCode());
    }

    public function testUpdatePasswordAdminSetsPasswordOfTargetUser(): void
    {
        $response = $this->jsonRequest(
            'POST',
            '/updatePasswordAdmin',
            ['targetUser' => '/users/3', 'newPassword' => 'reset99', 'newPasswordVerification' => 'reset99'],
            'admin'
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(['status' => 200, 'message' => 'password updated'], $this->decode($response));
        self::assertSame(200, $this->jsonRequest('POST', '/jlogin', ['username' => 'basic', 'password' => 'reset99'])->getStatusCode());
    }

    public function testUpdatePasswordAdminWithMismatchedVerificationIs400(): void
    {
        $response = $this->jsonRequest(
            'POST',
            '/updatePasswordAdmin',
            ['targetUser' => '/users/3', 'newPassword' => 'reset99', 'newPasswordVerification' => 'other'],
            'admin'
        );

        self::assertSame(400, $response->getStatusCode());
        self::assertSame(['status' => 400, 'message' => 'invalid request'], $this->decode($response));
    }

    public function testUpdatePasswordAdminWithIriOfAnotherResourceIs400(): void
    {
        // invalid input: targetUser resolves, but to a cycle and not to a user
        $response = $this->jsonRequest(
            'POST',
            '/updatePasswordAdmin',
            ['targetUser' => '/cycles/1', 'newPassword' => 'reset99', 'newPasswordVerification' => 'reset99'],
            'admin'
        );

        self::assertSame(400, $response->getStatusCode());
        self::assertSame(['status' => 400, 'message' => 'invalid request'], $this->decode($response));
    }

    public function testUpdatePasswordAdminWithUnknownUserIriIs500(): void
    {
        // NOTE: current behaviour, possibly a bug: ItemNotFoundException is not caught, so an unknown
        // IRI gives a 500 instead of the 400 "invalid request" used for the other invalid inputs.
        $response = $this->jsonRequest(
            'POST',
            '/updatePasswordAdmin',
            ['targetUser' => '/users/999', 'newPassword' => 'reset99', 'newPasswordVerification' => 'reset99'],
            'admin'
        );

        self::assertSame(500, $response->getStatusCode());
        self::assertStringContainsString('Item not found for "/users/999"', (string) $this->decode($response)['detail']);
    }

    private function currentTargetOf(string $username): int
    {
        $em = $this->freshEm();
        $cycle = $em->getRepository(Cycle::class)->findCurrentCycle();

        return $em->getRepository(User::class)->findOneBy(['username' => $username])->getScoreForCycle($cycle)->getTargetAmount();
    }
}
