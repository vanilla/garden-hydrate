<?php
/**
 * @author Gary Pomerant <gpomerant@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Hydrate\Middleware;

use Garden\Hydrate\DataResolverInterface;
use Garden\Hydrate\MiddlewareInterface;
use Garden\Schema\Schema;

/**
 * A useful base class for middleware.
 */
abstract class AbstractMiddleware implements MiddlewareInterface {
    /**
     * @var ?Schema
     */
    protected $schema;

    /**
     * {@inheritDoc}
     */
    public function validate(array $data): array {
        $schema = $this->getSchema();
        if ($schema !== null) {
            return $schema->validate($data);
        } else {
            return $data;
        }
    }

    /**
     * @return Schema|null
     */
    public function getSchema(): ?Schema {
        return $this->schema;
    }
}
