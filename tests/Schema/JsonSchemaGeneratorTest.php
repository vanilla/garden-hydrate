<?php
/**
 * @author Adam Charron <adam@charrondev.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Hydrate\Tests;

use Garden\Hydrate\DataHydrator;
use Garden\Hydrate\Resolvers\FunctionResolver;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the schema generator.
 */
class JsonSchemaGeneratorTest extends TestCase {

    /**
     * Since \Garden\Schema can't currently validate with oneOf (until version 2 or 3) we just do a snapshot test.
     */
    public function testCompareSnapshot() {
        $testRoot = realpath(__DIR__ . '/..');
        $cacheDir = $testRoot . '/Fixtures';
        if (!file_exists($cacheDir)) {
            mkdir($cacheDir, 0777, true);
        }
        $referencePath = $cacheDir . '/schemaReference.json';
        $hydrator = new DataHydrator();
        $hydrator->addResolver(
            new FunctionResolver([TestCase::class, 'assertEquals'])
        );
        $generator = $hydrator->getSchemaGenerator();
        $schema = $generator->getDefaultSchema();

        $actual = json_encode($schema, JSON_PRETTY_PRINT);
        // Uncomment this to generate a new file.
        // file_put_contents($referencePath, $actual);

        $expected = file_get_contents($referencePath);
        $this->assertEquals($expected, $actual);
    }

}
