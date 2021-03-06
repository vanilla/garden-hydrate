<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Hydrate\Resolvers;

use Garden\Schema\Schema;

/**
 * A data resolver that calls `sprintf()`.
 */
class SprintfResolver extends AbstractDataResolver {
    /**
     * SprintfResolver constructor.
     */
    public function __construct() {
        $this->schema = new Schema([
            'type' => 'object',
            'properties' => [
                'format' => ['type' => 'string'],
                'args' => ['type' => 'array'],
            ],
            'required' => ['format'],
        ]);
    }

    /**
     * {@inheritDoc}
     */
    protected function resolveInternal(array $data, array $params) {
        $args = $data['args'] ?? [];
        $result = sprintf($data['format'], ...$args);
        return $result;
    }
}
