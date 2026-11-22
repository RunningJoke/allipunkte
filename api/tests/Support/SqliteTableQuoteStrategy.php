<?php

namespace App\Tests\Support;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\DefaultQuoteStrategy;

/**
 * Test-only. The table "transaction" is fine on MySQL but a keyword on SQLite, and the
 * ORM does not quote it by default. Quoting every table name lets the API tests run on SQLite.
 */
final class SqliteTableQuoteStrategy extends DefaultQuoteStrategy
{
    public function getTableName(ClassMetadata $class, AbstractPlatform $platform): string
    {
        return $platform->quoteIdentifier(parent::getTableName($class, $platform));
    }
}
