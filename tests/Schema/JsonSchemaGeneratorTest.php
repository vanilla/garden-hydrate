<?php
/**
 * @author Adam Charron <adam@charrondev.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Hydrate\Tests;

use Garden\Hydrate\DataHydrator;
use Garden\Hydrate\Resolvers\FunctionResolver;
use Garden\Hydrate\Schema\JsonSchemaGenerator;
use Garden\Hydrate\Tests\Fixtures\TestStringResolver;
use Garden\Hydrate\Tests\Fixtures\TestTypeGroupResolver;
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

    /**
     * Test that hydrators are put in the correct groups.
     */
    public function testHydrateGroups() {
        $hydrator = new DataHydrator();
        $hydrator->addResolver(new TestTypeGroupResolver('rootHydrate'));
        $hydrator->addResolver(new TestTypeGroupResolver('customHydrate1', 'custom1'));
        $hydrator->addResolver(new TestTypeGroupResolver('customHydrate2', 'custom2'));

        $schemaGenerator = $hydrator->getSchemaGenerator();
        $this->assertSame([
            JsonSchemaGenerator::ROOT_HYDRATE_GROUP => [
                'literal',
                'param',
                'ref',
                'sprintf',
                'rootHydrate',
                'customHydrate1',
                'customHydrate2',
            ],
            'custom1' => ['customHydrate1'],
            'custom2' => ['customHydrate2'],
        ], $schemaGenerator->getTypesByGroup());
    }
}
