<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Hydrate\Tests;

use Garden\Hydrate\Resolvers\ParamResolver;
use Garden\Schema\ValidationException;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the `ParamResolver` class.
 */
class ParamResolverTest extends TestCase {
    /**
     * @var ParamResolver
     */
    private $resolver;

    /**
     * {@inheritDoc}
     */
    public function setUp(): void {
        parent::setUp();

        $this->resolver = new ParamResolver();
    }

    /**
     * A resolver without a ref is invalid.
     */
    public function testValidate(): void {
        $this->expectException(ValidationException::class);

        $this->resolver->resolve([], []);
    }

    /**
     * The default should be respected.
     */
    public function testDefault(): void {
        $actual = $this->resolver->resolve(['ref' => 'foo', 'default' => 'bar'], []);
        $this->assertSame('bar', $actual);
    }
}
