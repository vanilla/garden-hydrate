<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Hydrate\Tests\Fixtures;

use Garden\Hydrate\DataResolverInterface;
use Garden\Hydrate\ExceptionHandlerInterface;

/**
 * A test exception handler that only handles if the exception says not to.
 */
class TestExceptionHandler implements ExceptionHandlerInterface {
    /**
     * {@inheritDoc}
     */
    public function process(array $data, array $context, DataResolverInterface $next) {
        try {
            $r = $next->resolve($data, $context);
            return $r;
        } catch (\Exception $ex) {
            if ($data['throw'] ?? true) {
                throw $ex;
            } else {
                return [
                    'exception' => true,
                    'class' => get_class($ex),
                    'message' => $ex->getMessage(),
                    'code' => $ex->getCode(),
                ];
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function handleException(\Throwable $ex, array $data, array $params) {
        if ($data['throw'] ?? true) {
            throw $ex;
        } else {
            return [
                'exception' => true,
                'class' => get_class($ex),
                'message' => $ex->getMessage(),
                'code' => $ex->getCode(),
            ];
        }
    }
}
