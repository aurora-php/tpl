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

        /****v* sandbox/$meta
         * SYNOPSIS
         */
        protected $meta = array();
        /*
         * FUNCTION
         *      various meta data for block functions
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
        public function each($id, &$ctrl, $array, &$meta = NULL)
        /*
         * FUNCTION
         *      handles '#foreach' block -- iterates over an array and repeats an enclosed template block
         * INPUTS
         *      * $id (string) -- uniq identifier for loop
         *      * $ctrl (mixed) -- control variable is overwritten and used by this method
         *      * $array (array) -- array to use for iteration
         *      * $meta (array) -- (optional) control variable for meta information
         * OUTPUTS
         *      (bool) -- returns ~true~ as long is iterator is running and ~false~ if iterator reached his end
         ****
         */
        {
            $id = 'each:' . $id;
            
            if (!isset($this->meta[$id])) {
                $this->meta[$id] = $array->getIterator();
            }
            
            if (($return = $this->meta[$id]->valid())) {
                $item = $this->meta[$id]->current();
                
                $this->meta[$id]->next();
            } else {
                // $value = '';
                $this->meta[$id]->rewind();
                $item = $this->meta[$id]->current();
            }
            
            $ctrl = $item->item;
            $meta = array(
                'key'       => $item->key,
                'pos'       => $item->pos,
                'count'     => $item->count,
                'is_first'  => $item->is_first,
                'is_last'   => $item->is_last
            );

            return $return;
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
        public function loop($id, &$ctrl, $from, $to, &$meta = NULL)
        /*
         * FUNCTION
         *      handles #loop block -- creates something like a for loop
         * INPUTS
         *      * $id (string) -- uniq identifier for loop
         *      * $ctrl (mixed) -- control variable for loop
         *      * $from (int) -- value to start loop at
         *      * $to (int) -- value to end loop at
         *      * $meta (array) -- (optional) control variable for meta information
         * OUTPUTS
         *      (bool) -- returns true as long as loop did not reach the end
         ****
         */
        {
            $id = 'loop:' . $id;
            
            if (!isset($this->meta[$id])) {
                if ($from > $to) $step *= -1;

                $this->meta[$id] = array(
                    'from'  => $from,
                    'to'    => $to,
                    'step'  => $from,
                    'incr'  => ($from > $to ? -1 : 1)
                );
            } else {
                $this->meta[$id]['step'] += $this->meta[$id]['incr'];
            }

            if ($from > $to) {
                $ret = ($this->meta[$id]['step'] > $to);
            } else {
                $ret = ($this->meta[$id]['step'] < $to);
            }

            if (!$ret) {
                $ctrl = $to;
                $this->meta[$id]['step'] = $this->meta[$id]['from'];
            } else {
                $ctrl = $this->meta[$id]['step'];
            }

            $meta = array(
                'key'      => $this->meta[$id]['step'],
                'pos'      => $this->meta[$id]['step'],
                'is_first' => ($this->meta[$id]['step'] == $this->meta[$id]['from']),
                'is_last'  => ($this->meta[$id]['step'] == $this->meta[$id]['to'])
            );

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
            $id = 'trigger:' . $id;

            if (!isset($this->meta[$id]) || $this->meta[$id]['reset_value'] !== $reset) {
                $this->meta[$id] = array(
                    'current_step'  => $start,
                    'total_steps'   => $steps,
                    'reset_value'   => $reset
                );
            } else {
                $this->meta[$id]['current_step']++;
            }

            $ret = ($this->meta[$id]['current_step'] % $this->meta[$id]['total_steps']);

            $this->data[$id]['step'] = $ret;

            return ($ret == ($this->meta[$id]['total_steps'] - 1));
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
            $id = 'onchange:' . $id;
            
            if (!isset($this->meta[$id])) {
                $this->meta[$id] = NULL;
            }

            $return = ($this->meta[$id] !== $value);

            $this->meta[$id] = $value;

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
