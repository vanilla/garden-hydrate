<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Hydrate\Resolvers;

use Garden\Hydrate\DataHydrator;
use Garden\Hydrate\Schema\HydrateableSchema;
use Garden\Hydrate\Schema\JsonSchemaGenerator;
use Garden\Schema\Schema;

/**
 * A resolver that allows for a literal value.
 */
class LiteralResolver extends AbstractDataResolver {

    public const TYPE = "literal";

    /**
     * LiteralResolver constructor.
     */
    public function __construct() {
        $this->schema = new Schema([
            'description' => 'A literal returns it\'s exact data value before any other processing.',
            'type' => 'object',
            'properties' => [
                'data' => [
                    HydrateableSchema::X_NO_HYDRATE => true,
                    'description' => 'The value of the literal',
                    'type' => HydrateableSchema::ALL_SCHEMA_TYPES,
                ],
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

    /**
     * @inheritDoc
     */
    public function getType(): string {
        return self::TYPE;
    }
}
