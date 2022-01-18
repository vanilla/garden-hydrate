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
     * @inheritdoc
     */
    protected function processInternal(array $nodeData, array $middlewareParams, array $hydrateParams, DataResolverInterface $next) {
        $resolvedData = $next->resolve($nodeData, $hydrateParams);
        $jsontSpec = $middlewareParams['jsont'];
        $transformer = new Transformer($jsontSpec);
        $result = $transformer->transform($resolvedData);
        return $result;
    }

    /**
     * Get the middleware schema.
     *
     * @return Schema
     */
    public function getSchema(): Schema {
        $schema = new Schema([
            'type' => 'object',
            'description' => 'Methods of transforming a resolved node. Applies the node is hydrated.',
            'properties' => [
                'jsont' => [
                    'type' => ['object', 'string'],
                    'additionalProperties' => true,
                    'description' =>
                        'A jsont specification for transforming the API response data. You may want to escape this with $hydrate: \'literal\'.'
                        . 'See https://github.com/vanilla/garden-jsont',
                ],
            ],
            'required' => ['jsont'],
        ]);
        return $schema;
    }

    /**
     * @inheritdoc
     */
    public function getType(): string {
        return "transform";
    }
}
