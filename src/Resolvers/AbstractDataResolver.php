<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Hydrate\Resolvers;

use Garden\Hydrate\Schema\JsonSchemaGenerator;
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

    /**
     * {@inheritDoc}
     */
    public function validate(array $data): array {
        if ($this->schema !== null) {
            return $this->schema->validate($data);
        } else {
            return $data;
        }
    }

    /**
     * {@inheritDoc}
     */
    final public function resolve(array $data, array $params = []) {
        $result = $this->validate($data);
        $result = $this->resolveInternal($result, $params);
        return $result;
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
     * If a string is used that will be the key to group the resolver.
     *
     * Other items can allow properties to be only of a specific resolver group.
     *
     * @return string
     */
    public function getHydrateGroup(): string {
        return JsonSchemaGenerator::ROOT_HYDRATE_GROUP;
    }
}
