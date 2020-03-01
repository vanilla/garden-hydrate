<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Hydrate;

/**
 * A basic exception handler that just re-throws all exceptions.
 */
class NullExceptionHandler implements ExceptionHandlerInterface {
    /**
     * {@inheritDoc}
     */
    public function handleException(\Throwable $ex, array $data, array $params) {
        throw $ex;
    }
}
