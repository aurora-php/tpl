<?php

/*
 * This file is part of the 'octris/tpl' package.
 *
 * (c) Harald Lapp <harald@octris.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Octris\Tpl;

/**
 * Extemsion related functionality.
 *
 * @copyright   copyright (c) 2018 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
class Extension {
    /**
     * Return min,max number of parameters.
     *
     * @return  array
     */
    public static function getNumberOfParameters(callable $fn)
    {
        $ref = (is_array($fn)
            ? new ReflectionMethod($fn[0], $fn[1])
            : (is_object($fn) && is_callable($fn, '__invoke')
                ? new ReflectionMethod($fn, '__invoke')
                : new ReflectionFunction($fn)));

        $min = $ref->getNumberOfRequiredParameters();
        $max = ($ref->isVariadic() ? -1 : $ref->getNumberOfParameters());

        return [$min, $max];
    }

    /**
     * Create uniq identifier required by some functions.
     *
     * @return  string
     */
    public static function getUniqId()
    {
        return str_replace('.', '_', uniqid('v', true));
    }
}
