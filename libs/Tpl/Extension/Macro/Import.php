<?php

/*
 * This file is part of the 'octris/tpl' package.
 *
 * (c) Harald Lapp <harald@octris.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Octris\Tpl\Extension\Macro;

/**
 * Macro for importing sub-template.
 *
 * @copyright   copyright (c) 2018 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
final class Import extends \Octris\Tpl\Extension\Macro {    
    /**
     * Import sub-template.
     * 
     * @param   string      $id         Uniq identifier for cycle.
     * @param   array       $array      List of elements to use for cycling.
     * @param   bool        $pingpong   Optional flag indicates whether to start with first element or moving pointer
     *                                  back and forth in case the pointer reached first (or last) element in the list.
     * @param   mixed       $reset      Optional reset flag. The cycle pointer is reset if value provided differs from stored
     *                                  reset value
     * @return  mixed                   Current list item.
     */
    public function getCode()
    {
        $ret = '';
        $err = '';

        $c = clone($env['compiler']);

        if (($file = $c->findFile($args[0])) !== false) {
            $ret = $c->process($file, $env['escape']);
        } else {
            $err = sprintf(
                'unable to locate file "%s" in "%s"',
                $args[0],
                implode(':', $c->getSearchPath())
            );
        }

        return array($ret, $err);
    }
}
