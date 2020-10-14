<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Hydrate\Tests;

use Garden\Hydrate\DataHydrator;
use Garden\Hydrate\Resolvers\LiteralResolver;
use Garden\Schema\ValidationException;
use PHPUnit\Framework\TestCase;

class LiteralResolverTest extends TestCase {
    /**
     * @var LiteralResolver
     */
    private $resolver;

    /**
     * Set up test fixtures.
     */
    public function setUp(): void {
        parent::setUp();
        $this->resolver = new LiteralResolver();
    }

    /**
     * The data key is required.
     */
    public function testMissingData() {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('data is required.');
        $this->resolver->resolve([], []);
    }

    /**
     * A literal should not resolve its children.
     */
    public function testLiteralResolverReserved(): void {
        $hydrator = new DataHydrator();
        $spec = [
            DataHydrator::KEY_HYDRATE => 'literal',
            'data' => [
                DataHydrator::KEY_HYDRATE => 'literal',
                'data' => 123
            ]
        ];
        $actual = $hydrator->hydrate($spec);
        $expected = [
            DataHydrator::KEY_HYDRATE => 'literal',
            'data' => 123
        ];
        $this->assertSame($expected, $actual);
    }
}
