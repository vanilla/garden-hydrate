<?php
/**
 * @author Adam Charron <adam@charrondev.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Hydrate\Exception;

/**
 * An exception for when a middleware that hasn't been registered is requested.
 */
class InvalidHydrateSpecException extends HydrateException {
    /**
     * MiddlewareNotFoundException constructor.
     * @param string $message
     */
    public function __construct($message = "") {
        parent::__construct($message, 400);
    }
}
