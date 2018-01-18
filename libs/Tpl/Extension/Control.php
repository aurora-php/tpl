<?php

/*
 * This file is part of the 'octris/tpl' package.
 *
 * (c) Harald Lapp <harald@octris.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Octris\Tpl\Extension;

/**
 * Abstract class for implementing control structures.
 *
 * @copyright   copyright (c) 2018 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
class Control extends \Octris\Tpl\Extension\AbstractExtension
{
    /**
     * Code generator.
     *
     * @param   array               $args               Function arguments definition.
     * @param   array               $env                Engine environment.
     * @return  string                                  Template code.
     */
    final public function getCode(array $args, array $env)
    {
        $code = $this->fn(...$args);
        
        return [ $head, '}' ];
    }
}
