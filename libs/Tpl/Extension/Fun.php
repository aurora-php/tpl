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
class Fun extends \Octris\Tpl\Extension\AbstractExtension {
    /**
     * Code generator.
     *
     * @return  string                                  Template code.
     */
    final public function getCode()
    {
        return '$this->library[static::class](\'' . \Octris\Tpl\Extension::getUniqId() . '\', ' . $this->fn() . ')';
    }
}
