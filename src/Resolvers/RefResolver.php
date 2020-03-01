<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Hydrate\Resolvers;

use Garden\Hydrate\DataHydrator;
use Garden\JSON\ReferenceResolverTrait;
use Garden\Schema\Schema;

/**
 * A resolver that can reference he entire data array.
 */
final class RefResolver extends AbstractDataResolver {
    use ReferenceResolverTrait;

    /**
     * RefResolver constructor.
     */
    public function __construct() {
        $this->schema = new Schema([
            'type' => 'object',
            'properties' => [
                'ref' => [
                    'type' => 'string',
                ],
                'default' => [

                ],
            ],
            'required' => ['ref'],
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function resolveInternal(array $data, array $params) {
        $ref = $data['ref'];
        $default = $data['default'] ?? null;

        $result = $this->resolveReference($ref, $data, $params[DataHydrator::KEY_ROOT] ?? [], $found);
        return $found ? $result : $default;
    }
}
