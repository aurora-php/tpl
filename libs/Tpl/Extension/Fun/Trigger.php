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
 * Implementation for '#trigger' block function. The trigger can be used for example
 * inside a block of type '#loop' or '#foreach'. An internal counter will be increased
 * for each loop cycle. The trigger will return 'true' for every $steps steps.
 *
 * @copyright   copyright (c) 2018 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
final class Trigger extends \Octris\Tpl\Extension\Fun {
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
        $code_gen = function($steps = 2, $start = 0, $reset = 1) {
            return implode(', ', [ $steps, $start, $reset ]);
        };

        parent::__construct($name, $code_gen, $options);
    }

    /**
     * Execute trigger.
     * 
     * @param   string      $id         Uniq identifier of trigger.
     * @param   int         $steps      Optional number of steps trigger should go until signal is raised.
     * @param   int         $start      Optional step to start trigger at.
     * @param   mixed       $reset      Optional trigger reset flag. The trigger is reset if value provided differs from stored reset value.
     * @return  bool                    Returns true if trigger is raised.
     */
    public function __invoke($id, $steps = 2, $start = 0, $reset = 1)
    {
        $id = $id . ':' . $steps . ':' . $start;

        if (!isset($this->states[$id])) {
            $get_generator = function () use ($start, $steps, $reset) {
                $pos = $start;

                while (true) {
                    if ($reset != ($tmp = yield)) {
                        $pos = $start;
                        $reset = $tmp;
                    } else {
                        $pos = $pos % $steps;
                    }

                    yield(($steps - 1) == $pos++);
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
