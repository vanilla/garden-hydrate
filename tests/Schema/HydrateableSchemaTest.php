<?php
/**
 * @author Adam Charron <adam@charrondev.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Hydrate\Tests;

use Garden\Hydrate\DataHydrator;
use Garden\Hydrate\Exception\InvalidHydrateSpecException;
use Garden\Hydrate\Schema\HydrateableSchema;
use Garden\Hydrate\Schema\JsonSchemaGenerator;
use Garden\Schema\Schema;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the hydrateable schema modifications.
 */
class HydrateableSchemaTest extends TestCase {

    /**
     * Test addition of the hydrate property and unions to sub-properties.
     */
    public function testConvertObject() {
        $hydrateable = HydrateableSchema::parse([
            'foo:s?',
        ], 'withFoo')->getSchemaArray();

        $expected = [
            'type' => 'object',
            'properties' => [
                'foo' => [
                    'oneOf' => [
                        [
                            'type' => 'string',
                        ],
                        [
                            '$ref' => '#/$defs/resolver',
                        ],
                    ],
                    'properties' => [
                        '$hydrate' => [
                            'type' => 'string',
                            // No enum here because we didn't give a type/group mapping.
                        ],
                    ],
                ],
                '$hydrate' => [
                    'type' => 'string',
                    'enum' => [
                        'withFoo',
                    ],
                ],
            ],
            'required' => [
                '$hydrate',
            ],
        ];
        $this->assertSame($expected, $hydrateable);
    }

    /**
     * Root level primitive types are not allowed (because all root types need to have a $hydrate parameter.
     */
    public function testPrimitiveTypeException() {
        $in = [
            'type' => ['number', 'string', 'null'],
            'minLength' => 20,
        ];
        $this->expectException(InvalidHydrateSpecException::class);
        $hydrateable = new HydrateableSchema($in, 'primitive');
    }

    /**
     * Test mixing of hydrate into an existing oneOf.
     */
    public function testExistingOneOf() {
        $in = [
            'oneOf' => [
                [
                    'type' => 'number'
                ],
                [
                    'type' => 'string'
                ]
            ],
        ];

        $expected = [
            'oneOf' => [
                [
                    'type' => 'number'
                ],
                [
                    'type' => 'string'
                ],
                [
                    '$ref' => '#/$defs/resolver',
                ],
            ],
            'properties' => [
                '$hydrate' => [
                    'type' => 'string',
                    'enum' => [
                        'primitive',
                    ],
                ],
            ],
            'required' => [
                '$hydrate',
            ],
        ];
        $hydrateable = new HydrateableSchema($in, 'primitive');
        $this->assertSame($expected, $hydrateable->getSchemaArray());
    }

    /**
     * Test that required properties are preserved and that $hydrate becomes a required property.
     */
    public function testHydrateRequirement() {
        $in = [
            'type' => 'object',
            'properties' => [
                'foo' => [
                    'type' => 'string',
                ],
            ],
            'required' => ['foo'],
        ];
        $hydrateable = HydrateableSchema::parse($in, 'myType')->getSchemaArray();
        $this->assertEquals(['foo', '$hydrate'], $hydrateable['required']);
    }

    /**
     * Test that fields can opt-out of being hydrated.
     */
    public function testNoHydrate() {
        $in = [
            'type' => 'object',
            'properties' => [
                'foo' => [
                    HydrateableSchema::X_NO_HYDRATE => true,
                    'type' => 'string',
                ],
            ],
            'required' => ['foo'],
        ];
        $hydrateable = HydrateableSchema::parse($in, 'myType')->getSchemaArray();
        $this->assertEquals($hydrateable['properties']['foo'], $in['properties']['foo']);
    }

    /**
     * Test that hydrate groups can be limited to certain ones.
     */
    public function testHydrateGroups() {
        $groups = [
            JsonSchemaGenerator::ROOT_HYDRATE_GROUP => ['one', 'two', 'three'],
            'subgroup' => ['one', 'two'],
        ];
        $hydrateable = HydrateableSchema::parse([
            'any' => [
                'type' => 'string',
            ],
            'limited' => [
                'type' => 'string',
                HydrateableSchema::X_HYDRATE_GROUP => 'subgroup',
            ],
        ], 'inType', $groups)->getSchemaArray();

        $this->assertSame(
            $groups[JsonSchemaGenerator::ROOT_HYDRATE_GROUP],
            $hydrateable['properties']['any']['properties'][DataHydrator::KEY_HYDRATE]['enum']
        );
        $this->assertSame(
            $groups['subgroup'],
            $hydrateable['properties']['limited']['properties'][DataHydrator::KEY_HYDRATE]['enum']
        );
    }
}
