<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Hydrate\Middleware;

use Garden\Hydrate\DataHydrator;
use Garden\Hydrate\DataResolverInterface;
use Garden\JSON\Transformer;
use Garden\Schema\Schema;

/**
 * Middleware that transforms the data after it has been resolved.
 */
class TransformMiddleware extends AbstractMiddleware {
    /**
     * {@inheritDoc}
     */
    public function process(array $data, array $params, DataResolverInterface $next) {
        $transform = $data[DataHydrator::KEY_MIDDLEWARE]['transform'] ?? null;
        $data = $next->resolve($data, $params);

        if ($transform !== null) {
            $transformer = new Transformer($transform);

            $result = $transformer->transform($data);
            return $result;
        }
        return $data;
    }

    /**
     * Get the middleware schema.
     *
     * @return Schema
     */
    public static function getMiddlewareSchema(): Schema {
        $schema = new Schema([
            "x-no-hydrate" => true,
            "description" => "transform middleware",
            "type" => "object",
            "properties" => [
                "key" => Schema::parse([
                    "type" => "string",
                    "description" => "ref"
                ])
            ]
        ]);
        return $schema;
    }
}
