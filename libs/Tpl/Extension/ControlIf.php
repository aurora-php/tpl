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

use \Octris\Tpl\Compiler as compiler;

/**
 * IF control structure.
 *
 * @copyright   copyright (c) 2018 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
class ControlIf
{
    /**
     * Code generator for head of control structure.
     */
    final public function head($args) {
        $code = '';

        if (__CLASS__ != static::class) {
            $fn = '$this->block["' . static::class . '"]->atHead(%s)';
        } else {
            $fn = '%s';
        }

        return sprintf('if (' . $fn . ') {';
    }

    /**
     * Code generator for foot of control structure.
     */
    final public function foot() {
        return '}';
    }

    public function atHead($args) {
        return true;
    }
}
