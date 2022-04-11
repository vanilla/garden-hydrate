<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Hydrate\Resolvers;

use Garden\Hydrate\ValidatableResolverInterface;
use Garden\Schema\Schema;

/**
 * A useful base class for data resolvers.
 */
abstract class AbstractDataResolver implements ValidatableResolverInterface {
    /**
     * @var ?Schema
     */
    protected $schema;

    /** @var array */
    protected $layoutCacheNode;

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
     * {@inheritDoc}
     */
    final public function resolve(array $data, array $params = []) {
        global $layoutCacheNode;
        if (!empty($data['$hydrate'])) {
            $layoutCacheNodeKey = md5(json_encode($data));
            if (!empty($this->layoutCacheNode[$layoutCacheNodeKey])) {
                //return result from node cache over entire request
                $result = $this->layoutCacheNode[$layoutCacheNodeKey];
            } else {
                $result = $this->validate($data);
                $result = $this->resolveInternal($result, $params);
                //cache unique node result for entire request
                $this->layoutCacheNode[$layoutCacheNodeKey] = $result;
                $layoutCacheNode[$layoutCacheNodeKey] = $this->layoutCacheNode[$layoutCacheNodeKey];
            }
        } else {
            $result = $this->validate($data);
            $result = $this->resolveInternal($result, $params);
        }
        return $result;
    }

    /**
     * Empty the cache of hydrated nodes (may be needed for tests)
     */
    final public function clearCache() {
        global $layoutCacheNode;
        $layoutCacheNode = $this->layoutCacheNode = [];
    }


    /**
     * Override this method to do the actual resolution.
     *
     * @param array $data The input data spec.
     * @param array $params Additional parameters passed to the resolution
     * @return mixed
     */
    abstract protected function resolveInternal(array $data, array $params);

    /**
     * @return Schema|null
     */
    public function getSchema(): ?Schema {
        return $this->schema;
    }

    /**
     * Define groups that the hydrator belongs to.
     * Hydrators will always belong to the root hydrate group in addition to ones defined here.
     *
     * This is meant to be used alongside the x-hydrate-group schema parameter when creating HydrateableSchema.
     * Only resolvers that return that given type would be allowed on that particular property (as opposed to any resolver at all).
     *
     * @return string[]
     */
    public function getHydrateGroups(): array {
        return [];
    }
}
