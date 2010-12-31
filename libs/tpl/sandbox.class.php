<?php

namespace org\octris\core\tpl {
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
        /****d* sandbox/T_CONTEXT_HTML, T_CONTEXT_JAVASCRIPT, T_CONTEXT_TEXT, T_CONTEXT_XML
         * SYNOPSIS
         */
        const T_CONTEXT_HTML       = 1; 
        const T_CONTEXT_JAVASCRIPT = 2; 
        const T_CONTEXT_TEXT       = 3;
        const T_CONTEXT_XML        = 4;
        /*
         * FUNCTION
         *      contexts
         ****
         */
        
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
        
        /****v* sandbox/$registry
         * SYNOPSIS
         */
        protected $registry = array();
        /*
         * FUNCTION
         *      function registry
         ****
         */
        
        /****v* sandbox/$context
         * SYNOPSIS
         */
        protected $context;
        /*
         * FUNCTION
         *      context to use for autoescaping
         ****
         */
        
        /****v* sandbox/$filename
         * SYNOPSIS
         */
        protected $filename = '';
        /*
         * FUNCTION
         *      name of file that rendered through the sandbox
         ****
         */
        
        /****v* sandbox/$l10n
         * SYNOPSIS
         */
        protected $l10n;
        /*
         * FUNCTION
         *      l10n dependency
         ****
         */
        
        /****m* sandbox/__construct
         * SYNOPSIS
         */
        public function __construct()
        /*
         * FUNCTION
         *      constructor
         ****
         */
        {
        }
        
        /****m* sandbox/setL10n
         * SYNOPSIS
         */
        public function setL10n($l10n)
        /*
         * FUNCTION
         *      set l10n dependency
         * INPUTS
         *      * $l10n (l10n) -- l10n instance to set as dependency
         ****
         */
        {
            $this->l10n = $l10n;
        }
        
        /****m* sandbox/__call
         * SYNOPSIS
         */
        public function __call($name, $args)
        /*
         * FUNCTION
         *      magic caller for registered template functions
         * INPUTS
         *      * $name (string) -- name of function to call
         *      * $args (mixed) -- function arguments
         * OUTPUTS
         *      (mixed) -- output of the registered function
         ****
         */
        {
            if (!isset($this->registry[$name])) {
                $this->error(sprintf('"%s" -- unknown function', $name), 0, __LINE__);
            } elseif (!is_callable($this->registry[$name]['callback'])) {
                $this->error(sprintf('"%s" -- unable to call function', $name), 0, __LINE__);
            } elseif (count($args) < $this->registry[$name]['args']['min']) {
                $this->error(sprintf('"%s" -- not enough arguments', $name), 0, __LINE__);
            } elseif (count($args) > $this->registry[$name]['args']['max']) {
                $this->error(sprintf('"%s" -- too many arguments', $name), 0, __LINE__);
            } else {
                return call_user_func_array($this->registry[$name]['callback'], $args);
            }
        }
        
        /****m* sandbox/error
         * SYNOPSIS
         */
        public function error($msg, $line = 0, $cline = __LINE__)
        /*
         * FUNCTION
         *      trigger an error and stop processing template
         * INPUTS
         *      * $msg (string) -- additional error message
         *      * $line (int) -- line in template the error occured (0, if it's in the class lib)
         *      * $cline (int) -- line in the class that triggered the error
         ****
         */
        {
            printf("\n** ERROR: sandbox(%d)**\n", $cline);
            printf("   line :    %d\n", $line);
            printf("   file:     %s\n", $this->filename);
            printf("   message:  %s\n", $msg);
            
            die();
        }
        
        /****m* sandbox/registerMethod
         * SYNOPSIS
         */
        public function registerMethod($name, $callback, array $args)
        /*
         * FUNCTION
         *      register a custom template method
         * INPUTS
         *      * $name (string) -- name of macro to register
         *      * $callback (mixed) -- callback to call when macro is executed
         *      * $args (array) -- for testing arguments
         ****
         */
        {
            $name = strtolower($name);
            
            $this->registry[$name] = array(
                'callback' => $callback,
                'args'     => array_merge(array('min' => 0, 'max' => 0), $args)
            );
        }
        
        /****m* tpl/setValues
         * SYNOPSIS
         */
        public function setValues($array)
        /*
         * FUNCTION
         *      set values wort multiple variables
         * INPUTS
         *      * $array (array) -- key/value array with values
         ****
         */
        {
            foreach ($array as $k => $v) $this->setValue($k, $v);
        }
        
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
        
        /****m* sandbox/gettext
         * SYNOPSIS
         */
        public function gettext($msg)
        /*
         * FUNCTION
         *      gettext
         * INPUTS
         *      * $msg (string) -- message to translate
         * OUTPUTS
         *      (string) -- translated message
         ****
         */
        {
            return $this->l10n->gettext($msg);
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
            $id = 'each:' . $id . ':' . crc32(serialize($array->getArrayCopy()));
            
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
            $id = 'loop:' . $id . ':' . crc32("$from:$to");
            
            if (!isset($this->meta[$id])) {
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
                $this->meta[$id]['step'] = $this->meta[$id]['from'] - $this->meta[$id]['incr'];
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
            $id = 'trigger:' . $id . ':' . crc32("$steps:$start");

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
        public function write($val = '', $auto_escape = true)
        /*
         * FUNCTION
         *      output a specified value
         * INPUTS
         *      * $val (string) -- (optional) value to output
         *      * $auto_escape (bool) -- (optional) flag whether to auto-escape value
         ****
         */
        {
            if ($auto_escape) {
                switch($this->context) {
                case self::T_CONTEXT_HTML:
                    $val = htmlspecialchars($val);
                    break;
                case self::T_CONTEXT_JAVASCRIPT:
                    break;
                case self::T_CONTEXT_TEXT:
                default:
                    break;
                }
            }
            
            print $val;
        }
        
        /****m* sandbox/dump
         * SYNOPSIS
         */
        public function dump($var)
        /*
         * FUNCTION
         *      dump contents of variable
         * INPUTS
         *      * $var (mixed) -- variable to dump
         ****
         */
        {
            return var_export(
                ((is_object($var) && 
                 ($var instanceof \org\octris\core\type\collection || 
                  $var instanceof \org\octris\core\type\collection\Iterator || 
                  $var instanceof \ArrayIterator)) 
                ? $var->getArrayCopy() 
                : $var), 
                true
            );
        }
    
        /****m* sandbox/includetpl
         * SYNOPSIS
         */
        public function includetpl($file)
        /*
         * FUNCTION
         *      read a file and return it as string
         * INPUTS
         *      * $file (string) -- file to include
         * OUTPUTS
         *      (string) -- file contents
         ****
         */
        {
            return (is_readable($file)
                    ? file_get_contents($file)
                    : '');
        }
    
        /****m* sandbox/render
         * SYNOPSIS
         */
        public function render($filename, $context)
        /*
         * FUNCTION
         *      render a template
         * INPUTS
         *      * $filename (string) -- filename of template to render
         *      * $context (string) -- context of files, required for auto-escaping
         ****
         */
        {
            $this->filename = $filename;
            $this->context  = $context;
            
            require($filename);
        }
    }
}
