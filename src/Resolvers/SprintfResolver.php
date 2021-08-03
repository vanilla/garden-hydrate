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

    public const TYPE = "sprintf";

    /**
     * SprintfResolver constructor.
     */
    public function __construct() {
        $this->schema = new Schema([
            'type' => 'object',
            'description' => 'Call sprintf($format, $args).',
            'properties' => [
                'format' => [
                    'description' => 'The format string.',
                    'type' => 'string',
                ],
                'args' => [
                    'description' => 'Arguments to interpolate into the format string.',
                    'type' => 'array',
                ],
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

    /**
     * @inheritDoc
     */
    public function getType(): string {
        return self::TYPE;
    }
}
