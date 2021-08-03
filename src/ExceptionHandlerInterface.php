<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Hydrate;

/**
 * An interface that classes implement to handle exceptions in the `DataHydrator`.
 */
interface ExceptionHandlerInterface {
    /**
     * Handle an exception while resolving data.
     *
     * When implementing this method you want to either return new data that represents or fixes the exception or re-throw the exception.
     *
     * @param \Throwable $ex The exception that occurred.
     * @param array $data The data that was being hydrated.
     * @param array $params The additional parameters passed when hydrating.
     * @return mixed
     */
    public function handleException(\Throwable $ex, array $data, array $params);
}
