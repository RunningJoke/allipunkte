<?php

namespace App\Tests\Api;

/**
 * Routes that are not resources: the API entrypoint at "/", the JSON-LD context and genid
 * routes of API Platform, and the leftover "/security" page from the Symfony skeleton.
 */
class SiteRoutesTest extends ApiTestCase
{
    public function testRootReturnsApiPlatformEntrypointNotTheLandingController(): void
    {
        // NOTE: current behaviour, possibly a bug: the LandingController route "/" is shadowed by
        // API Platform's entrypoint route, so the landing controller can never answer.
        $response = $this->jsonRequest('GET', '/', null, null, 'application/ld+json');
        $data = $this->decode($response);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('Entrypoint', $data['@type']);
        foreach (['/cycles', '/petitions', '/users'] as $iri) {
            self::assertContains($iri, $data);
        }
    }

    public function testRootRejectsPlainJson(): void
    {
        // Deliberate change with API Platform 4 (#57): the entrypoint no longer serves application/json, which
        // exposed internal class names. The frontend never requests "/".
        self::assertSame(406, $this->jsonRequest('GET', '/')->getStatusCode());
    }

    public function testJsonLdContextForUserIsPublic(): void
    {
        $response = $this->jsonRequest('GET', '/contexts/User', null, null, 'application/ld+json');
        $data = $this->decode($response);

        self::assertSame(200, $response->getStatusCode());
        self::assertArrayHasKey('@vocab', $data['@context']);
        self::assertSame('User/firstname', $data['@context']['firstname']);
    }

    public function testGenidRouteIsDeliberatelyNotExposed(): void
    {
        // the route exists (debug:router) but API Platform answers 404 by design for collection-level ids
        $response = $this->jsonRequest('GET', '/.well-known/genid/abc123', null, null, 'application/ld+json');

        self::assertSame(404, $response->getStatusCode());
        self::assertStringContainsString('not exposed on purpose', (string) $this->decode($response)['hydra:description']);
    }

    public function testSecurityPageFailsBecauseTemplateIsMissing(): void
    {
        // NOTE: current behaviour, possibly a bug: the route renders security/index.html.twig, which does not
        // exist in templates/, so the page is a 500 error.
        $response = $this->jsonRequest('GET', '/security');

        self::assertSame(500, $response->getStatusCode());
        self::assertStringContainsString('security/index.html.twig', (string) $this->decode($response)['detail']);
    }
}
