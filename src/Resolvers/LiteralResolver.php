<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Hydrate\Resolvers;

use Garden\Schema\Schema;

/**
 * A resolver that allows for a literal value.
 */
class LiteralResolver extends AbstractDataResolver {
    /**
     * LiteralResolver constructor.
     */
    public function __construct() {
        $this->schema = new Schema([
            'type' => 'object',
            'properties' => [
                'data' => [],
            ],
            'required' => ['data'],
        ]);
    }

    /**
     * Resolve by returning the data key.
     *
     * @param array $data
     * @param array $params
     * @return mixed
     */
    public function resolveInternal(array $data, array $params) {
        return $data['data'];
    }
}
