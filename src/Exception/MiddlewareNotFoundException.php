<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Hydrate\Exception;

/**
 * An exception for when a middleware that hasn't been registered is requested.
 */
class MiddlewareNotFoundException extends HydrateException {
    /**
     * MiddlewareNotFoundException constructor.
     * @param string $message
     */
    public function __construct($message = "") {
        parent::__construct($message, 404);
    }
}
