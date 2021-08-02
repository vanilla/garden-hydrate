<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Hydrate\Resolvers;

use Garden\Hydrate\Schema\HydrateableSchema;
use Garden\Hydrate\Schema\JsonSchemaGenerator;
use Garden\JSON\ReferenceResolverTrait;
use Garden\Schema\Schema;

/**
 * A resolver that grabs its data from passed parameters.
 */
final class ParamResolver extends AbstractDataResolver {

    use ReferenceResolverTrait;

    public const TYPE = "param";

    /**
     * ParamResolver constructor.
     */
    public function __construct() {
        $this->schema = new Schema([
            'description' => 'Params are data passed in during rendering. In order to ',
            'type' => 'object',
            'properties' => [
                'ref' => [
                    'type' => 'string',
                ],
                'default' => [
                    'type' => HydrateableSchema::ALL_SCHEMA_TYPES,
                    'default' => null,
                ],
            ],
            'required' => ['ref'],
        ]);
    }


    /**
     * Set valid parameter names.
     *
     * @param string[] $paramNames
     */
    public function setParamNames(array $paramNames) {
        $this->schema->setField('properties.ref.enum', $paramNames);
    }

    /**
     * {@inheritDoc}
     */
    public function resolveInternal(array $data, array $params) {
        $ref = $data['ref'];
        $default = $data['default'] ?? null;

        $result = $this->resolveReference($ref, $params, $params, $found);
        return $found ? $result : $default;
    }

    /**
     * @inheritDoc
     */
    public function getType(): string {
        return self::TYPE;
    }
}
