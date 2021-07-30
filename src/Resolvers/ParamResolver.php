<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Hydrate\Resolvers;

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
