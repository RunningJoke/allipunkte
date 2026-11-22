<?php

namespace App\Tests\Api;

use App\Entity\ResetPasswordRequest;
use Symfony\Bundle\FrameworkBundle\Test\MailerAssertionsTrait;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

/**
 * ResetPasswordController (routes app_forgot_password_request, app_check_email, app_reset_password).
 * These are server-rendered HTML pages with forms, not JSON endpoints. The forms are CSRF-protected,
 * so each POST carries the _token that the page rendered.
 */
class ResetPasswordTest extends ApiTestCase
{
    use MailerAssertionsTrait;

    private const REQUEST_FORM = 'reset_password_request_form';

    private const CHANGE_FORM = 'change_password_form';

    public function testRequestPageRendersForm(): void
    {
        $this->client->request('GET', '/reset-password');

        $response = $this->client->getResponse();
        self::assertSame(200, $response->getStatusCode());
        self::assertStringStartsWith('text/html', (string) $response->headers->get('Content-Type'));
        self::assertStringContainsString(self::REQUEST_FORM.'[mail]', (string) $response->getContent());
    }

    public function testRequestForUnknownMailRedirectsWithoutSendingOrRevealing(): void
    {
        $this->submitResetRequest('nobody@example.de');

        self::assertSame(302, $this->client->getResponse()->getStatusCode());
        // Symfony 6.0 emits the Location as a path
        self::assertSame('/reset-password/check-email', $this->client->getResponse()->headers->get('Location'));
        self::assertEmailCount(0);
        self::assertSame(0, static::em()->getRepository(ResetPasswordRequest::class)->count([]));
    }

    public function testRequestForKnownMailSendsOneMailAndStoresRequest(): void
    {
        $this->submitResetRequest('basic@example.de');

        self::assertSame(302, $this->client->getResponse()->getStatusCode());
        self::assertSame('/reset-password/check-email', $this->client->getResponse()->headers->get('Location'));

        self::assertEmailCount(1);
        $mail = $this->getMailerMessage();
        self::assertSame('basic@example.de', $mail->getTo()[0]->getAddress());
        self::assertSame('punkte@alli-go.de', $mail->getFrom()[0]->getAddress());

        self::assertSame(1, static::em()->getRepository(ResetPasswordRequest::class)->count([]));
    }

    public function testCheckEmailPageRenders(): void
    {
        $this->client->request('GET', '/reset-password/check-email');

        self::assertSame(200, $this->client->getResponse()->getStatusCode());
        self::assertStringStartsWith('text/html', (string) $this->client->getResponse()->headers->get('Content-Type'));
    }

    public function testInvalidTokenIsStoredAndRedirectsBackToRequestPage(): void
    {
        $this->client->request('GET', '/reset-password/reset/not-a-real-token');

        self::assertSame(302, $this->client->getResponse()->getStatusCode());
        self::assertSame('/reset-password/reset', $this->client->getResponse()->headers->get('Location'));

        $this->client->request('GET', '/reset-password/reset');

        self::assertSame(302, $this->client->getResponse()->getStatusCode());
        self::assertSame('/reset-password', $this->client->getResponse()->headers->get('Location'));
    }

    public function testResetPageWithoutTokenIs404(): void
    {
        $this->client->request('GET', '/reset-password/reset');

        self::assertSame(404, $this->client->getResponse()->getStatusCode());
    }

    public function testValidTokenAllowsSettingNewPasswordOnce(): void
    {
        $token = $this->issueToken('basic');

        // the link from the mail: the token moves into the session and the URL is redirected
        $this->client->request('GET', '/reset-password/reset/'.$token);
        self::assertSame('/reset-password/reset', $this->client->getResponse()->headers->get('Location'));

        $this->client->request('GET', '/reset-password/reset');
        self::assertSame(200, $this->client->getResponse()->getStatusCode());

        $this->client->request('POST', '/reset-password/reset', [
            self::CHANGE_FORM => [
                'plainPassword' => ['first' => 'newsecret', 'second' => 'newsecret'],
                '_token' => $this->csrfTokenOf(self::CHANGE_FORM),
            ],
        ]);

        self::assertSame(302, $this->client->getResponse()->getStatusCode());
        self::assertSame('https://punkte.alli-go.de', $this->client->getResponse()->headers->get('Location'));

        // the new password works through the normal login, the old one does not
        self::assertSame(200, $this->jsonRequest('POST', '/jlogin', ['username' => 'basic', 'password' => 'newsecret'])->getStatusCode());
        self::assertSame(401, $this->jsonRequest('POST', '/jlogin', ['username' => 'basic', 'password' => '1234'])->getStatusCode());

        // the token is consumed
        self::assertSame(0, static::em()->getRepository(ResetPasswordRequest::class)->count([]));
    }

    public function testMismatchedNewPasswordsRerenderFormAndChangeNothing(): void
    {
        $token = $this->issueToken('basic');
        $this->client->request('GET', '/reset-password/reset/'.$token);
        $this->client->request('GET', '/reset-password/reset');

        $this->client->request('POST', '/reset-password/reset', [
            self::CHANGE_FORM => [
                'plainPassword' => ['first' => 'newsecret', 'second' => 'different'],
                '_token' => $this->csrfTokenOf(self::CHANGE_FORM),
            ],
        ]);

        self::assertSame(200, $this->client->getResponse()->getStatusCode());
        self::assertStringContainsString('Die Passwörter müssen übereinstimmen', (string) $this->client->getResponse()->getContent());
        self::assertSame(200, $this->jsonRequest('POST', '/jlogin', ['username' => 'basic', 'password' => '1234'])->getStatusCode());
    }

    /**
     * Loads the request page, then posts the mail address with the CSRF token it rendered.
     */
    private function submitResetRequest(string $mail): void
    {
        $this->client->request('GET', '/reset-password');

        $this->client->request('POST', '/reset-password', [
            self::REQUEST_FORM => ['mail' => $mail, '_token' => $this->csrfTokenOf(self::REQUEST_FORM)],
        ]);
    }

    /**
     * The hidden CSRF field rendered on the page the client is currently showing.
     */
    private function csrfTokenOf(string $formName): string
    {
        $token = $this->client->getCrawler()->filter('input[name="'.$formName.'[_token]"]')->attr('value');
        self::assertNotEmpty($token, "no CSRF token rendered for $formName");

        return $token;
    }

    /**
     * Creates a reset request for the user, like the "forgot password" form does, and returns the raw token.
     */
    private function issueToken(string $username): string
    {
        $helper = static::getContainer()->get(ResetPasswordHelperInterface::class);

        return $helper->generateResetToken($this->user($username))->getToken();
    }
}
