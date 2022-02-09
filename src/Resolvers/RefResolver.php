<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Hydrate\Resolvers;

use Garden\Hydrate\DataHydrator;
use Garden\Hydrate\Schema\HydrateableSchema;
use Garden\JSON\ReferenceResolverTrait;
use Garden\Schema\Schema;

/**
 * A resolver that can reference the entire data array.
 */
final class RefResolver extends AbstractDataResolver {

    use ReferenceResolverTrait;

    public const TYPE = "ref";

    /**
     * RefResolver constructor.
     */
    public function __construct() {
        $this->schema = new Schema([
            'type' => 'object',
            'description' => 'Reference data from other parts of the hydration by it\'s path.',
            'properties' => [
                'ref' => [
                    'description' => 'A local reference within the document. For example: "/path/to/property/from/root".',
                    'type' => 'string',
                    HydrateableSchema::X_NO_HYDRATE => true,
                ],
                'default' => [
                    'description' => 'Default value if the ref could not be resolved. Defaults to null.',
                    'type' => HydrateableSchema::ALL_SCHEMA_TYPES,
                    'default' => null,
                ]
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

    /**
     * @inheritDoc
     */
    public function getType(): string {
        return self::TYPE;
    }
}
