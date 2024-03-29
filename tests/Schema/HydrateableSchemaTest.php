<?php
/**
 * @author Adam Charron <adam@charrondev.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Hydrate\Tests\Schema;

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
     * Root level primitive types are not allowed (because all root types need to have a $hydrate parameter).
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
                    'type' => 'object',
                    'properties' => [
                        'num' => [
                            'type' => 'number',
                        ],
                        'required' => ['num'],
                    ],
                ],
                [
                    'type' => 'object',
                    'properties' => [
                        'str' => [
                            'type' => 'string',
                        ],
                        'required' => ['str'],
                    ],
                ],
            ],
        ];

        $expected = [
            'oneOf' => [
                [
                    'type' => 'object',
                    'properties' => [
                        'num' => [
                            'type' => 'number',
                        ],
                        'required' => ['num'],
                    ],
                ],
                [
                    'type' => 'object',
                    'properties' => [
                        'str' => [
                            'type' => 'string',
                        ],
                        'required' => ['str'],
                    ],
                ],
                [
                    '$ref' => '#/$defs/resolver',
                ],
            ],
            'type' => 'object',
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
            ['$ref' => '#/$defs/' . JsonSchemaGenerator::ROOT_HYDRATE_GROUP],
            $hydrateable['properties']['any']['oneOf'][1]
        );

        $this->assertSame(
            ['$ref' => '#/$defs/subgroup'],
            $hydrateable['properties']['limited']['oneOf'][1]
        );
    }

    /**
     * Test hydration of list items.
     */
    public function testHydrateItems() {
        $schema = [
            'type' => 'object',
            'properties' => [
                'childList' => [
                    HydrateableSchema::X_NO_HYDRATE => true,
                    HydrateableSchema::X_FORCE_HYDRATE_ITEMS => true,
                    'type' => 'array',
                    'items' => [
                        'type' => 'string',
                    ],
                ],
            ],
        ];
        $hydrateable = (new HydrateableSchema($schema, 'myType'))->getSchemaArray();

        $this->assertSame([
            'type' => 'object',
            'properties' => [
                'childList' => [
                    'x-no-hydrate' => true,
                    'x-force-hydrate-items' => true,
                    'type' => 'array',

                    // Items were are hydrateable but not the childList itself.
                    'items' => [
                        'oneOf' => [
                            [
                                'type' => 'string',
                            ],
                            [
                                '$ref' => '#/$defs/resolver',
                            ],
                        ],
                    ],
                ],
                '$hydrate' => [
                    'type' => 'string',
                    'enum' => ['myType'],
                ],
            ],
            'required' => [
                '$hydrate',
            ]
        ], $hydrateable);
    }
}
