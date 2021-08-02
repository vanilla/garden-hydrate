<?php
/**
 * @author Adam Charron <adam@charrondev.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Hydrate\Tests;

use Garden\Hydrate\Schema\HydrateableSchema;
use Garden\Schema\Schema;
use PHPUnit\Framework\TestCase;

class HydrateableSchemaTest extends TestCase {

    public function testConvertObject() {
        $in = Schema::parse([
            'foo:s?',
        ])->getSchemaArray();
        $hydrateable = new HydrateableSchema($in, 'withFoo');

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
                            'enum' => [],
                        ],
                    ],
                    'required' => [
                        '$hydrate',
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
        $this->assertSame($expected, $hydrateable->getSchemaArray());
    }

    public function testPrimitiveType() {
        $in = Schema::parse([
            'type' => ['number', 'string', 'null'],
            'minLength' => 20,
        ])->getSchemaArray();

        $expected = [
            'oneOf' => [
                [
                    'type' => ['number', 'string', 'null'],
                    'minLength' => 20,
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
}
