<?php

namespace App\Tests\Api;

use App\Entity\Cycle;
use App\Entity\User;
use App\Services\UserFactory\UserFactoryInterface;
use App\DataFixtures\TestFixtures;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Base class for the HTTP behaviour tests.
 *
 * Every test starts with a freshly built schema (from the entity mapping) and the
 * data from src/DataFixtures/TestFixtures.php: one open cycle and three users, all
 * with the password "1234":
 *   admin    ROLE_ADMIN + ROLE_CREATOR
 *   creator  ROLE_CREATOR
 *   basic    ROLE_USER only
 *
 * The database is SQLite (see .env.test), never the MySQL database from .env.
 */
abstract class ApiTestCase extends WebTestCase
{
    protected KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();

        // a fresh kernel per test: the previous one may still hold SQLite connections
        static::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->resetDatabase();
    }

    protected function tearDown(): void
    {
        // close the SQLite connections of the last kernel, otherwise its lock blocks the next test's schema reset
        $this->closeConnections();
        static::ensureKernelShutdown();

        parent::tearDown();
    }

    private function closeConnections(): void
    {
        try {
            foreach (static::getContainer()->get('doctrine')->getConnections() as $connection) {
                $connection->close();
            }
        } catch (\LogicException) {
            // no kernel booted, nothing to close
        }
    }

    private function resetDatabase(): void
    {
        $em = static::em();

        if (!$em->getConnection()->getDatabasePlatform() instanceof SqlitePlatform) {
            throw new \RuntimeException('API tests must run on SQLite, refusing to reset a different database.');
        }

        // drop every table explicitly: SchemaTool::dropSchema() misses the quoted "transaction" table
        $connection = $em->getConnection();
        foreach ($connection->createSchemaManager()->listTableNames() as $table) {
            if (!str_starts_with($table, 'sqlite_')) {
                $connection->executeStatement('DROP TABLE IF EXISTS '.$connection->quoteIdentifier($table));
            }
        }

        $metadata = $em->getMetadataFactory()->getAllMetadata();
        (new SchemaTool($em))->createSchema($metadata);

        $fixtures = new TestFixtures(static::getContainer()->get(UserFactoryInterface::class));
        $fixtures->load($em);

        $em->clear();
        $connection->close();
    }

    protected static function em(): EntityManagerInterface
    {
        return static::getContainer()->get('doctrine')->getManager();
    }

    /**
     * EntityManager with an empty identity map, so reads see what the last request wrote.
     */
    protected function freshEm(): EntityManagerInterface
    {
        $em = static::em();
        $em->clear();

        return $em;
    }

    protected function user(string $username): User
    {
        $user = static::em()->getRepository(User::class)->findOneBy(['username' => $username]);
        self::assertNotNull($user, "fixture user '$username' missing");

        return $user;
    }

    /**
     * Sends a request the way the frontend does (JSON in, JSON out).
     *
     * @param array|string|null $body array is JSON-encoded, a string is sent as-is (for malformed JSON)
     * @param string|null       $asUser username to log in as via loginUser(), null for anonymous
     */
    protected function jsonRequest(string $method, string $url, array|string|null $body = null, ?string $asUser = null, string $accept = 'application/json'): Response
    {
        // every call starts from a clean session, so anonymous really is anonymous
        $this->client->getCookieJar()->clear();

        if ($asUser !== null) {
            $this->client->loginUser($this->user($asUser));
        }

        $content = is_array($body) ? json_encode($body) : $body;

        $this->client->request(
            $method,
            $url,
            [],
            [],
            ['HTTP_ACCEPT' => $accept, 'CONTENT_TYPE' => 'application/json'],
            $content
        );

        return $this->client->getResponse();
    }

    /**
     * Sets the amount of the user's score in the current cycle (direct DB write, for setting up a test).
     */
    protected function setScoreAmount(string $username, int $amount): void
    {
        $em = static::em();
        $score = $this->user($username)->getScoreForCycle($em->getRepository(Cycle::class)->findCurrentCycle());
        $score->setAmount($amount);
        $em->flush();
    }

    /**
     * Moves the only open cycle into the past, so no cycle is active any more.
     */
    protected function endCurrentCycle(): void
    {
        $em = static::em();
        $cycle = $em->getRepository(Cycle::class)->findCurrentCycle();
        $cycle->setFromDate(new \DateTime('-2 days'));
        $cycle->setToDate(new \DateTime('-1 day'));
        $em->flush();
    }

    protected function decode(Response $response): array
    {
        $data = json_decode((string) $response->getContent(), true);
        self::assertIsArray($data, 'response is not a JSON object/array: '.$response->getContent());

        return $data;
    }

    /**
     * Asserts the exact set of keys, independent of their order.
     */
    protected function assertKeysExactly(array $expectedKeys, array $data, string $message = ''): void
    {
        $actual = array_keys($data);
        sort($actual);
        sort($expectedKeys);
        self::assertSame($expectedKeys, $actual, $message);
    }

    /**
     * Returns the value of the IRI ("/users/1") or fails if it is not an IRI of the given resource.
     */
    protected function assertIri(string $resource, mixed $value, string $message = ''): void
    {
        self::assertIsString($value, $message);
        self::assertMatchesRegularExpression('#^/'.preg_quote($resource, '#').'/\d+$#', $value, $message);
    }
}
