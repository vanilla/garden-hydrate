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
            'description' => 'A literal returns it\'s exact exact value.'
                .'For objects you can add additional properties, but for other types you can set data to be that value.',
            'type' => 'object',
            'properties' => [
                'data' => [
                    'description' => 'A literal returns it\'s exact exact value.'
                        .'For objects you can add additional properties, but for other types you can set data to be that value.',
                    'type' => HydrateableSchema::ALL_SCHEMA_TYPES,
                ],
            ],
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
        if (isset($data['data'])) {
            return $data['data'];
        } else {
            unset($data[DataHydrator::KEY_HYDRATE]);
            return $data;
        }
    }

    /**
     * @inheritDoc
     */
    public function getType(): string {
        return self::TYPE;
    }
}
