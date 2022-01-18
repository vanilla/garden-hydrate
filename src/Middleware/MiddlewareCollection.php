<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Hydrate\Middleware;

use Garden\Hydrate\MiddlewareCollectionTrait;
use Garden\Hydrate\Middleware\AbstractMiddleware;
use Garden\Schema\Schema;

/**
 * A collection of middleware that can be processed as a single middleware.
 *
 * This class is useful if you want to group middleware together. You can add a middleware collection to your main
 * data hydrator and then add middlewares after the fact. They will all be processed together even though other
 * middleware was added to the main data hydrator.
 */
class MiddlewareCollection extends AbstractMiddleware {
    use MiddlewareCollectionTrait;

    public static function getMiddlewareSchema(): Schema {
        return new Schema([]);
    }
}
