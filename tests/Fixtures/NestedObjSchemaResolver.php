<?php
/**
 * @author Adam Charron <adam@charrondev.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Hydrate\Tests\Fixtures;

use Garden\Hydrate\Resolvers\AbstractDataResolver;
use Garden\Schema\Schema;

/**
 * A resolver with a nested object schema.
 */
class NestedObjSchemaResolver extends AbstractDataResolver {

    /**
     * {@inheritDoc}
     */
    protected function resolveInternal(array $data, array $params = []) {
        return $data;
    }

    /**
     * @return Schema|null
     */
    public function getSchema(): ?Schema {
        return Schema::parse([
            'nested' => [
                'type' => 'object',
                'properties' => [
                    'foo' => [
                        'type' => 'string'
                    ],
                    'bar' => [
                        'type' => 'string'
                    ],
                ],
            ],
        ]);
    }


    /**
     * @inheritDoc
     */
    public function getType(): string {
        return 'nestedObjSchema';
    }
}
