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
 * Class for building macro extensions.
 *
 * @copyright   copyright (c) 2018 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
abstract class Macro extends \Octris\Tpl\Extension\AbstractExtension {
    /**
     * Code generator.
     *
     * @param   array               $args               Function arguments definition.
     * @param   array               $env                Engine environment.
     * @return  string                                  Template code.
     */
    public function getCode(array $args, array $env)
    {
        return $this->fn(...$args);
    }
}
