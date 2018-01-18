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
     * Create uniq identifier required by some functions.
     *
     * @return  string
     */
    protected static function getUniqId()
    {
        return str_replace('.', '_', uniqid('v', true));
    }
}
