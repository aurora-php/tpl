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
 * Class for building function extensions.
 *
 * @copyright   copyright (c) 2018 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
final class Fun extends \Octris\Tpl\Extension\AbstractExtension {
    /**
     * Call defined code generator and return result.
     *
     * @return  string                                  Template code.
     */
    public function getCode(array $args = [])
    {
        return ($this->fn)(...$args);
    }
}
