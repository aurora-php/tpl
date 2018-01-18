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
 * Trigger control structure.
 *
 * @copyright   copyright (c) 2018 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
class ControlTrigger extends ControlIf
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

class trigger extends ControlIf {
    protected $name = 'trigger';

    function atHead() {
        return 'trigger()';
    }

    function __invoke() {

    }

    // function atHead($id, $steps, $start, $reset) {
    //     $id = 'trigger:' . $id . ':' . crc32("$steps:$start");
    //
    //     if (!isset($this->meta[$id])) {
    //         $get_generator = function () use ($start, $steps, $reset) {
    //             $pos = $start;
    //
    //             while (true) {
    //                 if ($reset != ($tmp = yield)) {
    //                     $pos = $start;
    //                     $reset = $tmp;
    //                 } else {
    //                     $pos = $pos % $steps;
    //                 }
    //
    //                 yield(($steps - 1) == $pos++);
    //             }
    //         };
    //
    //         $this->meta[$id] = $get_generator();
    //     }
    //
    //     $this->meta[$id]->send($reset);
    //
    //     $return = $this->meta[$id]->current();
    //     $this->meta[$id]->next();
    //
    //     return $return;
    // }
}

$t = new trigger();
print $t->head();
