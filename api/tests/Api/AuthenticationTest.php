<?php

namespace App\Tests\Api;

/**
 * /jlogin, /jlogout and /updateUserData (the frontend's login and session bootstrap).
 */
class AuthenticationTest extends ApiTestCase
{
    public function testJloginWithValidCredentialsReturnsSuccessMessage(): void
    {
        $response = $this->jsonRequest('POST', '/jlogin', ['username' => 'admin', 'password' => '1234']);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(['status' => 200, 'message' => 'authentication successful'], $this->decode($response));
    }

    public function testJloginAcceptsTheMailAddressInPlaceOfTheUsername(): void
    {
        $response = $this->jsonRequest('POST', '/jlogin', ['username' => 'admin@example.de', 'password' => '1234']);

        self::assertSame(200, $response->getStatusCode());
    }

    public function testJloginWithWrongPasswordIs401(): void
    {
        $response = $this->jsonRequest('POST', '/jlogin', ['username' => 'admin', 'password' => 'wrong']);

        self::assertSame(401, $response->getStatusCode());
        self::assertSame(['error' => 'Invalid credentials.'], $this->decode($response));
    }

    public function testJloginWithUnknownUserIs401(): void
    {
        $response = $this->jsonRequest('POST', '/jlogin', ['username' => 'nobody', 'password' => '1234']);

        self::assertSame(401, $response->getStatusCode());
        self::assertSame(['error' => 'Invalid credentials.'], $this->decode($response));
    }

    public function testJloginWithoutPasswordIsRejected(): void
    {
        // invalid input: the json_login authenticator needs both fields
        $response = $this->jsonRequest('POST', '/jlogin', ['username' => 'admin']);

        self::assertSame(400, $response->getStatusCode());
    }

    /**
     * The real flow: the session cookie from /jlogin is what authenticates /updateUserData afterwards.
     * No loginUser() here.
     */
    public function testRealLoginSessionAuthenticatesLaterRequests(): void
    {
        $this->client->getCookieJar()->clear();
        $this->login('basic', '1234');

        self::assertNotEmpty($this->client->getCookieJar()->all(), 'login must set a session cookie');

        $this->client->request('GET', '/updateUserData', [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response = $this->client->getResponse();
        self::assertSame(200, $response->getStatusCode());
        self::assertSame('basic', $this->decode($response)['username']);
    }

    public function testLogoutEndsTheSessionAndRedirectsToTheRoot(): void
    {
        $this->client->getCookieJar()->clear();
        $this->login('basic', '1234');

        $this->client->request('GET', '/jlogout', [], [], ['HTTP_ACCEPT' => 'application/json']);

        // the logout listener answers with a redirect to "/", not with the JSON from the controller
        self::assertSame(302, $this->client->getResponse()->getStatusCode());
        self::assertSame('http://localhost/', $this->client->getResponse()->headers->get('Location'));

        $this->client->request('GET', '/updateUserData', [], [], ['HTTP_ACCEPT' => 'application/json']);
        self::assertSame(401, $this->client->getResponse()->getStatusCode());
    }

    public function testUpdateUserDataRequiresLogin(): void
    {
        $response = $this->jsonRequest('GET', '/updateUserData');

        self::assertSame(401, $response->getStatusCode());
    }

    public function testUpdateUserDataForBasicUserHasExactKeysAndNoAdminFlags(): void
    {
        $response = $this->jsonRequest('GET', '/updateUserData', null, 'basic');
        $data = $this->decode($response);

        self::assertSame(200, $response->getStatusCode());
        $this->assertKeysExactly(
            ['id', 'username', 'firstname', 'lastname', 'mail', 'license', 'userScore', 'targetScore', 'cycle', 'isAdmin', 'isCreator'],
            $data
        );
        self::assertSame(3, $data['id']);
        self::assertSame('basic', $data['username']);
        self::assertFalse($data['isAdmin']);
        self::assertFalse($data['isCreator']);
        self::assertSame(0, $data['userScore']);
        self::assertSame(20, $data['targetScore']);
    }

    public function testUpdateUserDataForAdminReportsAdminAndCreatorFlags(): void
    {
        $data = $this->decode($this->jsonRequest('GET', '/updateUserData', null, 'admin'));

        self::assertTrue($data['isAdmin']);
        self::assertTrue($data['isCreator']);
        self::assertSame(15, $data['targetScore']);
    }

    public function testUpdateUserDataCycleBlockHasExactKeysAndIsoDates(): void
    {
        $data = $this->decode($this->jsonRequest('GET', '/updateUserData', null, 'basic'));

        $this->assertKeysExactly(['id', 'name', 'description', 'from', 'to'], $data['cycle']);
        self::assertSame(1, $data['cycle']['id']);
        self::assertSame('Testzyklus', $data['cycle']['name']);
        // ISO8601 without a colon in the offset, e.g. 2026-11-22T00:00:00+0100
        self::assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{4}$/', $data['cycle']['from']);
        self::assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{4}$/', $data['cycle']['to']);
    }

    public function testUpdateUserDataReportsTheStoredScoreOfTheCurrentCycle(): void
    {
        $this->setScoreAmount('basic', 7);

        $data = $this->decode($this->jsonRequest('GET', '/updateUserData', null, 'basic'));

        self::assertSame(7, $data['userScore']);
    }

    public function testUpdateUserDataWithoutActiveCycleReturnsPlaceholderCycle(): void
    {
        $this->endCurrentCycle();

        $data = $this->decode($this->jsonRequest('GET', '/updateUserData', null, 'basic'));

        self::assertSame(200, $this->client->getResponse()->getStatusCode());
        self::assertSame(0, $data['userScore']);
        self::assertSame(0, $data['targetScore']);
        $this->assertKeysExactly(['name', 'description', 'from', 'to'], $data['cycle']);
        self::assertSame('Keine aktive Saison', $data['cycle']['name']);
    }

    /**
     * Logs in through the real /jlogin endpoint on the current client (cookies are kept).
     */
    private function login(string $username, string $password): void
    {
        $this->client->request(
            'POST',
            '/jlogin',
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json', 'CONTENT_TYPE' => 'application/json'],
            json_encode(['username' => $username, 'password' => $password])
        );

        self::assertSame(200, $this->client->getResponse()->getStatusCode(), 'login failed');
    }
}
