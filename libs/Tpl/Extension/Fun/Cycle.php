<?php

/*
 * This file is part of the 'octris/tpl' package.
 *
 * (c) Harald Lapp <harald@octris.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Octris\Tpl\Extension\Fun;

/**
 * Implementation for 'cycle' function. Cycle can be used inside a block of type '#loop' or '#foreach'. An
 * internal counter will be increased for each loop cycle. Cycle will return an element of a specified list
 * according to the internal pointer position.
 *
 * @copyright   copyright (c) 2018-present by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
final class Cycle extends \Octris\Tpl\Extension\Fun {
    /**
     * Stores state of triggers.
     *
     * @param   array
     */
    protected $states = [];
    
    /**
     * Constructor.
     *
     * @param   string              $name               Name to register extension with.
     * @param   array               $options            Optional options.
     */
    public function __construct($name, array $options = [])
    {
        $code_gen = function(array $array, $pingpong = false, $reset = 1) {
            return implode(', ', [ '\'' . \Octris\Tpl\Extension::getUniqId() . '\'', $array, $pingpong, $reset ]);
        };

        parent::__construct($name, $code_gen, $options);
    }

    /**
     * Implementation for the called function.
     * 
     * @param   string      $id         Uniq identifier for cycle.
     * @param   array       $array      List of elements to use for cycling.
     * @param   bool        $pingpong   Optional flag indicates whether to start with first element or moving pointer
     *                                  back and forth in case the pointer reached first (or last) element in the list.
     * @param   mixed       $reset      Optional reset flag. The cycle pointer is reset if value provided differs from stored
     *                                  reset value
     * @return  mixed                   Current list item.
     */
    public function call($id, array $array, $pingpong = false, $reset = 1)
    {
        if (!isset($this->states[$id])) {
            if ($pingpong) {
                $array = array_merge($array, array_slice(array_reverse($array), 1, count($array) - 2));
            }

            $get_generator = function () use ($array, $reset) {
                $pos = 0;
                $cnt = count($array);

                while (true) {
                    if ($reset != ($tmp = yield)) {
                        $pos = 0;
                        $reset = $tmp;
                    }

                    yield $array[$pos++];

                    if ($pos >= $cnt) {
                        $pos = 0;
                    }
                }
            };

            $this->states[$id] = $get_generator();
        }

        $this->states[$id]->send($reset);

        $return = $this->states[$id]->current();
        
        $this->states[$id]->next();

        return $return;
    }
}
