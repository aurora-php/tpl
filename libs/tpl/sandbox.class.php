<?php

namespace org\octris\core\tpl {
    require_once('type/collection.class.php');
    
    /****c* tpl/sandbox
     * NAME
     *      sandbox
     * FUNCTION
     *      sandbox to execute templates in
     * COPYRIGHT
     *      copyright (c) 2010 by Harald Lapp
     * AUTHOR
     *      Harald Lapp <harald@octris.org>
     ****
     */

    class sandbox {
        /****v* sandbox/$data
         * SYNOPSIS
         */
        public $data = array();
        /*
         * FUNCTION
         *      template data
         ****
         */

        /****v* sandbox/$pastebin
         * SYNOPSIS
         */
        protected $pastebin = array();
        /*
         * FUNCTION
         *      pastebin for cut/copied buffers
         ****
         */
        
        /****v* sandbox/$loops
         * SYNOPSIS
         */
        protected $loops = array();
        /*
         * FUNCTION
         *      storage for meta-data of loops
         ****
         */
        
        /****v* sandbox/$triggers
         * SYNOPSIS
         */
        protected $triggers = array();
        /*
         * FUNCTION
         *      storage for meta-data of triggers
         ****
         */
        
        /****v* sandbox/$onchange
         * SYNOPSIS
         */
        protected $onchange = array();
        /*
         * FUNCTION
         *      storage for onchange events
         ****
         */
        
        /****m* sandbox/setValue
         * SYNOPSIS
         */
        public function setValue($name, $value)
        /*
         * FUNCTION
         *      set value for sandbox
         * INPUTS
         *      * $name (string) -- name of variable to set
         *      * $value (mixed) -- value of variable
         ****
         */
        {
            if (is_scalar($value)) {
                $this->data[$name] = $value;
            } else {
                $this->data[$name] = new type\collection($value);
            }
        }
        
        /****m* sandbox/each
         * SYNOPSIS
         */
        public function each(&$ctrl, $array)
        /*
         * FUNCTION
         *      handles '#foreach' block -- iterates over an array and repeats an enclosed template block
         * INPUTS
         *      * $ctrl (mixed) -- control variable is overwritten and used by this method
         *      * $array (array) -- array to use for iteration
         * OUTPUTS
         *      (bool) -- returns ~true~ as long is iterator is running and ~false~ if iterator reached his end
         ****
         */
        {
            // static $each = array();
            // 
            // // $key = crc32(serialize($array));
            // 
            // print_r($array);
            // 
            // if (!isset($each[$key])) {
            //     $each[$key] = new lima_tpl_sandbox_array((array)$array);
            // }
            // 
            // if ($return = $each[$key]->valid()) {
            //     $value = $each[$key]->current();
            //     $each[$key]->next();
            // } else {
            //     $value = '';
            //     $each[$key]->rewind();
            // }
            // 
            // $this->__data__[$name] = $value;

            return false;
            
        }
        
        /****m* sandbox/bufferStart
         * SYNOPSIS
         */
        public function bufferStart(&$ctrl, $cut = true)
        /*
         * FUNCTION
         *      start output buffer
         * INPUTS
         *      * $ctrl (mixed) -- control variable to store buffer data in
         *      * $cut (bool) -- (optional) whether to cut or to copy to buffer
         ****
         */
        {
            array_push($this->pastebin, array(
                'buffer' => &$ctrl,
                'cut'    => $cut
            ));

            ob_start();
        }

        /****m* sandbox/bufferEnd
         * SYNOPSIS
         */
        public function bufferEnd()
        /*
         * FUNCTION
         *      stop output buffer
         ****
         */
        {
            $buffer = array_pop($this->pastebin);
            $buffer['buffer'] = ob_get_contents();
            
            if ($buffer['cut']) {
                ob_end_clean();
            } else {
                ob_end_flush();
            }
        }
        
        /****m* sandbox/cacheStart
         * SYNOPSIS
         */
        public function cacheStart($key, $timeout) 
        /*
         * FUNCTION
         *      handles #cache block -- starts a cache buffer. returns cache contents by specified key or generates
         *      cached content, if cache content is not available. as second parameter a cache timeout is required. the 
         *      cache timeout can have one of the following values:
         *      * (int) -- relative timeout in seconds
         *      * (int) -- a absolute unix timestamp. note, that if $timeout contains an integer > current timestamp, 
         *        it's guessed, that the value is not ment to be a relative timeout in seconds
         *      * (string) -- a datetime string as absolute timeout
         *      * 0 -- no cache
         *      * -1 -- cache never expires     
         * INPUTS
         *      * $key (string) -- cache-key to use for buffer
         *      * $timeout (mixed) -- cache timeout
         * OUTPUTS
         *      (bool) -- returns true, if caching succeeded
         ****
         */
        {
            // TODO
        }

        /****m* sandbox/cacheEnd
         * SYNOPSIS
         */
        public function cacheEnd() 
        /*
         * FUNCTION
         *      stop caching output buffer
         ****
         */
        {
            // TODO
        }

        /****m* sandbox/cron
         * SYNOPSIS
         */
        public function cron($start, $end = 0) 
        /*
         * FUNCTION
         *      handles #cron block -- display block for a period of time
         * INPUTS
         *      * $start (mixed) -- start date/time as string or unix timestamp
         *      * $end (mixed) -- (optional) end date/time as string or unix timestamp
         * OUTPUTS
         *      (bool) -- returns true, if cron block creation succeeded
         ****
         */
        {
            if (!ctype_digit($start)) {
                $start = (int)strtotime($start);
            }
            if (!ctype_digit($end)) {
                $end = (int)strtotime($end);
            }

            if ($start > $end && $end > 0) {
                $tmp   = $end;
                $end   = $start;
                $start = $tmp;
            }

            $current = time();

            return (($start <= $current && $end >= $current) || $end == 0);
        }

        /****m* sandbox/loop
         * SYNOPSIS
         */
        public function loop($id, &$ctrl, $from, $to, $step = 1) 
        /*
         * FUNCTION
         *      handles #loop block -- creates something like a for loop
         * INPUTS
         *      * $id (string) -- uniq identifier for loop
         *      * $ctrl (mixed) -- control variable for loop
         *      * $from (int) -- value to start loop at
         *      * $to (int) -- value to end loop at
         *      * $stept (int) -- (optional) step to increase/decrease value for each cycle
         * OUTPUTS
         *      (bool) -- returns true as long as loop did not reach the end
         ****
         */
        {
            if (!isset($this->loops[$id])) {
                $step = abs($step);

                if ($from > $to) {
                    $step *= -1;
                }

                $this->loops[$name] = array(
                    'name'  => $name,
                    'from'  => $from,
                    'to'    => $to,
                    'incr'  => $step,
                    'step'  => $from
                );
            } else {
                $this->loops[$name]['step'] += $this->loops[$name]['incr'];
            }

            if ($from > $to) {
                $ret = ($this->loops[$name]['step'] > $to);
            } else {
                $ret = ($this->loops[$name]['step'] < $to);
            }

            if (!$ret) {
                $this->data[$name]['step'] = $to;
                $this->loops[$name]['step'] = $this->loops[$name]['from'];
            } else {
                $this->data[$name]['step'] = $this->loops[$name]['step'];
            }

            $this->data[$name]['__is_first__'] = ($this->data[$name]['step'] == $from);
            $this->data[$name]['__is_last__'] = ($this->data[$name]['step'] == $to);

            return $ret;
        }

        /****m* sandbox/trigger
         * SYNOPSIS
         */
        public function trigger($id, $steps = 2, $start = 0, $reset = 1) 
        /*
         * FUNCTION
         *      handles #trigger block -- the trigger can be used inside a loop- or each-block. an internal 
         *      counter will be increased for each loop cycle. the trigger will return true for every $steps steps
         * INPUTS
         *      * $id (string) -- uniq identifier of trigger
         *      * $steps (int) -- (optional) number of steps trigger should go until signal is raised
         *      * $start (int) -- (optional) step to start trigger at
         *      * $reset (mixed) -- reset trigger. reset's trigger, if value provided differs from stored reset value
         * OUTPUTS
         *      (bool) -- returns true, if trigger raised
         ****
         */
        {
            if (!isset($this->triggers[$id]) || $this->triggers[$id]['reset_value'] !== $reset) {
                $this->triggers[$id] = array(
                    'current_step'  => $start,
                    'total_steps'   => $steps,
                    'reset_value'   => $reset
                );
            } else {
                $this->triggers[$id]['current_step']++;
            }

            $ret = ($this->triggers[$id]['current_step'] % $this->triggers[$id]['total_steps']);

            $this->data[$id]['step'] = $ret;

            return ($ret == ($this->triggers[$id]['total_steps'] - 1));
        }

        /****m* sandbox/onchange
         * SYNOPSIS
         */
        public function onchange($id, $value)
        /*
         * FUNCTION
         *      handles #onchange block -- triggers an event, if the contents of a variable changes. 
         * INPUTS
         *      * $id (string) -- uniq identifier of event
         *      * $value (mixed) -- value of observed variable
         * OUTPUTS
         *      (bool) -- returns true, if change was detected
         ****
         */
        {
            if (!isset($this->onhange[$id])) {
                $this->onchange[$id] = NULL;
            }

            $return = ($this->onchange[$id] !== $value);

            $this->onchange[$id] = $value;

            return $return;
        }

        /****m* sandbox/write
         * SYNOPSIS
         */
        public function write($val, $auto_escape = true)
        /*
         * FUNCTION
         *      output a specified value
         * INPUTS
         *      * $val (string) -- value to output
         *      * $auto_escape (bool) -- (optional) flag whether to auto-escape value
         ****
         */
        {
            print $val;
        }
    }
}
