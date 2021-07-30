<?php
/**
 * @author Adam Charron <adam@charrondev.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Hydrate\Tests;

use Garden\Hydrate\DataHydrator;
use Garden\Hydrate\Tests\Fixtures\TestStringResolver;
use PHPUnit\Framework\TestCase;

class JsonSchemaGeneratorTest extends TestCase {

    public function testGenerateSchema() {
        $out = __DIR__ . '/../Fixtures/schemaReference.json';
        $hydrator = new DataHydrator();
        $hydrator->addResolver(new TestStringResolver("testString"));
        $generator = $hydrator->getSchemaGenerator();
        $schema = $generator->getDefaultSchema();
        $json = json_encode($schema, JSON_PRETTY_PRINT);
        file_put_contents($out, $json);
        $this->assertEquals(true, true);
    }

}
