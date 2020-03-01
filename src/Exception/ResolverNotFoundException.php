<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Hydrate\Exception;

/**
 * An exception that is thrown when a resolver hasn't been registered.
 */
class ResolverNotFoundException extends HydrateException {
    /**
     * ResolverNotFoundException constructor.
     * @param string $message
     */
    public function __construct($message = "") {
        parent::__construct($message, 404);
    }
}
