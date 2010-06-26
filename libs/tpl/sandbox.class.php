<?php

namespace org\octris\core\tpl {
    /****c* tpl/sandbox
     * NAME
     *      sandbox
     * FUNCTION
     *      sandbox to execute templates in
     * COPYRIGHT
     *      copyright (c) 2006-2010 by Harald Lapp
     * AUTHOR
     *      Harald Lapp <harald@octris.org>
     ****
     */

    class sandbox {
        /****v* sandbox/$__tpl__
         * SYNOPSIS
         */
        private $__tpl__;
        /*
         * FUNCTION
         *      stores instance to main template class
         ****
         */

        /****v* sandbox/$__data__
         * SYNOPSIS
         */
        private $__data__ = array();
        /*
         * FUNCTION
         *      stores contents of values of variables registered for template
         ****
         */

        /****v* sandbox/$__caches__
         * SYNOPSIS
         */
        private $__cache__ = array();
        /*
         * FUNCTION
         *      various registered caches
         ****
         */

        /****v* sandbox/$__methods__
         * SYNOPSIS
         */
        private $__methods__ = array();
        /*
         * FUNCTION
         *      additional methods registered to the template
         ****
         */

        /****v* sandbox/$__pastebin__
         * SYNOPSIS
         */
        private $__pastebin__ = array();
        /*
         * FUNCTION
         *      copy/paste buffer for template snippets
         ****
         */

        /****m* tpl/$context
         * SYNOPSIS
         */
        protected $context;
        /*
         * FUNCTION
         *      template context
         ****
         */

        /****m* tpl/$charset
         * SYNOPSIS
         */
        protected $charset;
        /*
         * FUNCTION
         *      charset to use for template
         ****
         */

        /****m* sandbox/__construct
         * SYNOPSIS
         */
        function __construct(lima_tpl $tpl) 
        /*
         * FUNCTION
         *      constructor
         * INPUtS
         *      * $tpl (object) -- instance of template class
         ****
         */
        {
            $this->__tpl__ = $tpl;

            $this->context = $tpl->getContext();
            $this->charset = $tpl->getCharset();
        }

        /****m* sandbox/setValue
         * SYNOPSIS
         */
        function setValue($name, $value) 
        /*
         * FUNCTION
         *      set value of a variable available to a template
         * INPUTS
         *      * $name (string) -- name of variable to set
         *      * $value (mixed) -- value to set
         ****
         */
        {
            $this->__data__[$name] = $value;
        }

        /****m* sandbox/setValueByRef
         * SYNOPSIS
         */
        function setValueByRef($name, &$value) 
        /*
         * FUNCTION
         *      set value of a variable available to a template by reference
         * INPUTS
         *      * $name (string) -- name of variable to set
         *      * $value (mixed) -- value to set
         ****
         */
        {
            $this->__data__[$name] =& $value;
        }

        /****m* sandbox/setValues
         * SYNOPSIS
         */
        function setValues($data) 
        /*
         * FUNCTION
         *      set array of value for variables available to a template
         * INPUTS
         *      * $data (array) -- array with key => value pairs to set
         ****
         */
        {
            $this->__data__ = array_merge($this->__data__, $data);
        } 

        /****m* sandbox/unsetValue
         * SYNOPSIS
         */
        function unsetValue($name)
        /*
         * FUNCTION
         *      unset a template variable
         * INPUTS
         *      * $name (string) -- name of variable to unset
         ****
         */
        {
            unset($this->__data__[$name]);
        }

        /****m* sandbox/unsetValues
         * SYNOPSIS
         */
        function unsetValues($names)
        /*
         * FUNCTION
         *      unset template variables
         * INPUTS
         *      * $names (array) -- array of variable names to unset
         ****
         */
        {
            foreach ($names as $name) {
                unset($this->__data__[$name]);
            }
        }

        /****m* sandbox/getValue
         * SYNOPSIS
         */
        function getValue($name, $source = NULL) 
        /*
         * FUNCTION
         *      get value of a template variable
         * INPUTS
         *      * $name (string) -- name of variable to return value of
         *      * $source (array) -- optional source to get value of
         ****
         */
        {
            return (!is_null($source) ? $source[$name] : $this->__data__[$name]);
        }

        /****m* sandbox/getValues
         * SYNOPSIS
         */
        function getValues() 
        /*
         * FUNCTION
         *      get values of all template variables
         ****
         */
        {
            return $this->__data__;
        }

        /****m* sandbox/registerMethod
         * SYNOPSIS
         */
        function registerMethod($object, $method, $intern = NULL) 
        /*
         * FUNCTION
         *      register external method as a valid template method
         * INPUTS
         *      * $object (object) -- object the method is bound to
         *      * $method (string) -- name of method to register
         *      * $intern (string) -- (optional) internal name of method to use instead of real method name, can be used
         *        for example to avoid duplicate method names
         ****
         */
        {
            if (is_null($intern)) {
                $intern = $method;
            }

            $this->__methods__[$intern] = array(
                'method' => $method,
                'object' => $object
            );
        }

        /****m* sandbox/__call
         * SYNOPSIS
         */
        function __call($method, $args) 
        /*
         * FUNCTION
         *      magic method to call a registered method
         * INPUTS
         *      * $method (string) -- name of method to call
         *      * 5args (mixed) -- arguments to use for method call
         * OUTPUTS
         *      (mixed) -- return value of called method
         ****
         */
        {
            if (!isset($this->__methods__[$method])) {
                throw new Exception('method ' . $method . ' is not registered!');
            } elseif (is_callable($this->__methods__[$method]['object'])) {
                // callable object (eg.: closure)
                return call_user_func_array($this->__methods__[$method]['object'], $args);
            } elseif (!method_exists($this->__methods__[$method]['object'], $this->__methods__[$method]['method'])) {
                // unknown method
                throw new Exception('unknown method ' . $method);
            } else {
                return call_user_func_array(
                    array(
                        $this->__methods__[$method]['object'], 
                        $this->__methods__[$method]['method']
                    ), 
                    $args
                );
            }
        }

        /****m* sandbox/copy_start
         * SYNOPSIS
         */
        function copy_start($name) 
        /*
         * FUNCTION
         *      handles #COPY start tag -- opens a buffer to store template snippet in. the buffer expects a name as
         *      parameter. after closing the buffer, the buffer contents will be available as teplate
         *      variable with that name.
         * INPUTS
         *      * $name (string) -- name of buffer to open
         ****
         */
        {
            array_push($this->__pastebin__, $name);

            ob_start();
        }

        /****m* sandbox/copy_end
         * SYNOPSIS
         */
        function copy_end() 
        /*
         * FUNCTION
         *      handle /COPY end tag -- closes a buffer -- see sandbox/copy_start for details
         * SEE ALSO
         *      sandbox/copy_start
         ****
         */
        {
            $name = array_pop($this->__pastebin__);

            $this->__data__[$name] = ob_get_contents();
            ob_end_flush();
        }

        /****m* sandbox/cut_start
         * SYNOPSIS
         */
        function cut_start($name) 
        /*
         * FUNCTION
         *      handles #CUT start tag -- opens a buffer to store template snippet in. the buffer expects a name as
         *      parameter. after closing the buffer, the buffer contents will be available as template. in opposition
         *      to #COPY the buffer will be cleared instead of print
         * INPUTS
         *      * $name (string) -- name of buffer to open
         ****
         */
        {
            array_push($this->__pastebin__, $name);

            ob_start();
        }

        /****m* sandbox/cut_end
         * SYNOPSIS
         */
        function cut_end() 
        /*
         * FUNCTION
         *      handle /COPY end tag -- closes a buffer -- see sandbox/copy_start for details
         * SEE ALSO
         *      sandbox/copy_start
         ****
         */
        {
            $name = array_pop($this->__pastebin__);

            $this->__data__[$name] = ob_get_contents();
            ob_end_clean();
        }

        /****m* sandbox/cache
         * SYNOPSIS
         */
        function cache($key, $timeout) 
        /*
         * FUNCTION
         *      handles #CACHE start tag -- starts a cache buffer. returns cache contents by specified key or generates
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
            $cache = $this->__tpl__->getCacheInstance();
            $ret   = $cache->fetch($key);

            if (!$ret) {
                if (!ctype_digit($timeout)) {
                    $timeout = (int)strtotime($timeout);
                }

                $this->__cache__[] = array('timeout' => $timeout, 'key' => $key);

                ob_start();
            }

            return $ret;
        }

        /****m* sandbox/cache_end
         * SYNOPSIS
         */
        function cache_end() 
        /*
         * FUNCTION
         *      handles /CACHE end tag -- closes a cache buffer -- see sandbox/cache for details
         * SEE ALSO
         *      sandbox/cache
         ****
         */
        {
            $content = ob_get_contents();
            ob_end_clean();

            if (count($this->__cache__) > 0) {
                $tmp = array_pop($this->__cache__);

                $cache = $this->__tpl__->getCacheInstance();
                $cache->store($tmp['key'], $tmp['timeout']);
            } 
        }

        /****m* sandbox/cron
         * SYNOPSIS
         */
        function cron($start, $end) 
        /*
         * FUNCTION
         *      handles #CRON start tag -- display block for a period of time
         * INPUTS
         *      * $start (mixed) -- start date/time as string or unix timestamp
         *      * $end (mixed) -- end date/time as string or unix timestamp
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

            if (($start > $end)&&($end > 0)) {
                $tmp   = $end;
                $end   = $start;
                $start = $tmp;
            }

            $current = time();

            return (($start <= $current)&&(($end >= $current)||($end == 0)));
        }

        /****m* sandbox/loop
         * SYNOPSIS
         */
        function loop($name, $from, $to, $step = 1) 
        /*
         * FUNCTION
         *      handles #LOOP start tag -- creates something like a for loop
         * INPUTS
         *      * $name (string) -- name of loop to create
         *      * $from (int) -- value to start loop at
         *      * $to (int) -- value to end loop at
         *      * $stept (int) -- (optional) step to increase/decrease value for each cycle
         * OUTPUTS
         *      (bool) -- returns true as long as loop did not reach the end
         ****
         */
        {
            static $loops = array();

            if (!array_key_exists($name, $loops)) {
                $step = abs($step);

                if ($from > $to) {
                    $step *= -1;
                }

                $loops[$name] = array(
                    'name'  => $name,
                    'from'  => $from,
                    'to'    => $to,
                    'incr'  => $step,
                    'step'  => $from
                );
            } else {
                $loops[$name]['step'] += $loops[$name]['incr'];
            }

            if ($from > $to) {
                $ret = ($loops[$name]['step'] > $to);
            } else {
                $ret = ($loops[$name]['step'] < $to);
            }

            if (!$ret) {
                $this->__data__[$name]['step'] = $to;
                $loops[$name]['step'] = $loops[$name]['from'];
            } else {
                $this->__data__[$name]['step'] = $loops[$name]['step'];
            }

            $this->__data__[$name]['__is_first__'] = ($this->__data__[$name]['step'] == $from);
            $this->__data__[$name]['__is_last__'] = ($this->__data__[$name]['step'] == $to);

            return $ret;
        }

        /****m* sandbox/each
         * SYNOPSIS
         */
        function each($name, $array) 
        /*
         * FUNCTION
         *      handles #EACH start tag - iterates over an array and repeats an enclosed template block
         * INPUTS
         *      * $name (string) -- name to use for block. the array contents of each cycle will be made available
         *        in a template variable with that name
         *      * $array (array) -- array to use for iterating
         * OUTPUTS
         *      (bool) -- returns true as long as each is running and end was not reached
         ****
         */
        {
            static $each = array();

            $key = crc32($name . serialize($array));

            if (!isset($each[$key])) {
                $each[$key] = new lima_tpl_sandbox_array((array)$array);
            }

            if ($return = $each[$key]->valid()) {
                $value = $each[$key]->current();
                $each[$key]->next();
            } else {
                $value = '';
                $each[$key]->rewind();
            }

            $this->__data__[$name] = $value;

            return $return;
        }

        /****m* sandbox/trigger
         * SYNOPSIS
         */
        function trigger($name, $steps = 2, $start = 0, $reset = 1) 
        /*
         * FUNCTION
         *      handles #TRIGGER start tag -- the trigger can be used inside a loop- or each-block. an internal 
         *      counter will be increased for each loop cycle. the trigger will return true for every $steps steps
         * INPUTS
         *      * $name (string) -- name of trigger
         *      * $steps (int) -- (optional) number of steps trigger should go until signal is raised
         *      * $start (int) -- (optional) step to start trigger at
         *      * $reset (mixed) -- reset trigger. reset's trigger, if value provided differs from stored reset value
         * OUTPUTS
         *      (bool) -- returns true, if trigger raised
         ****
         */
        {
            static $triggers = array();

            if (!isset($triggers[$name]) || $triggers[$name]['reset_value'] !== $reset) {
                $triggers[$name] = array(
                    'current_step'  => $start,
                    'total_steps'   => $steps,
                    'reset_value'   => $reset
                );
            } else {
                $triggers[$name]['current_step']++;
            }

            $ret = ($triggers[$name]['current_step'] % $triggers[$name]['total_steps']);

            $this->__data__[$name]['step'] = $ret;          

            return ($ret == ($triggers[$name]['total_steps'] - 1));
        }

        /****m* sandbox/onchange
         * SYNOPSIS
         */
        function onchange($name, $value)
        /*
         * FUNCTION
         *      handles #ONCHANGE start tag -- triggers an event, if the contents of a variable changes. 
         * INPUTS
         *      * $name (string) -- name of onchange event
         *      * $value (mixed) -- value of observed variable
         * OUTPUTS
         *      (bool) -- returns true, if change was detected
         ****
         */
        {
            static $events = array();

            if (!isset($events[$name])) {
                $events[$name] = NULL;
            }

            $return = ($events[$name] !== $value);

            $events[$name] = $value;

            return $return;
        }

        /****m* sandbox/dump
         * SYNOPSIS
         */
        function dump($varname = '', $context = 'html') 
        /*
         * FUNCTION
         *      dump content of a variable
         * INPUTS
         *      * $varname (string) -- (optional) name of variable to dump. if not set, all variables will be dumped
         *      * $context (string) -- (optional) context for dumping
         * OUTPUTS
         *      (string) -- returns dumped variables as string
         ****
         */
        {
            if ($varname == '') {
                $ret = var_export($this->__data__, true);
            } elseif (is_array($varname)) {
                $ret = var_export($varname, true);
            } else {
                $ret = var_export($this->__data__[$varname], true);
            }

            switch ($context) {
            case 'html':
                $ret = '<pre>' . htmlspecialchars($ret) . '</pre>';
                break;
            }

            return $ret;
        }

        /****m* sandbox/hash2list
         * SYNOPSIS
         */
        function hash2list($a, $b = NULL) 
        /*
         * FUNCTION
         *      convert a key/value hash to a index based arrey with n child hashes, where 
         *      the key of the source array is stored in the 'key' property of the child
         *      array and the value pf the source array is stored in the 'value' property of
         *      the child array.
         * EXAMPLE
         *      example of source array:
         *      array('DE' => 'Deutschland', 'FR' => 'Frankreich')
         *
         *      exaple of destination array:
         *      array(array('key' => 'DE', 'value' => 'Deutschland'), array('key' => 'FR', ...))
         ****
         */
        {
            if (is_null($b)) {
                return array_map(array($this, 'hash2list'), array_keys($a), array_values($a));
            } else {
                return array('key' => $a, 'value' => $b);
            }
        }

        /****m* sandbox/htmltrim
         * SYNOPSIS
         */
        function htmltrim($str) 
        /*
         * FUNCTION
         *      replacement for php's trim(), also replaces &nbsp; to ''
         * INPUTS
         *      * $str (string) -- string to trim
         ****
         */
        {
            return trim(str_replace('&nbsp;', '', $str));
        }

        /****m* sandbox/cmp
         * SYNOPSIS
         */
        function cmp($val1, $val2, $op = 'eq') 
        /*
         * FUNCTION
         *      compare value1 with value2
         * INPUTS
         *      * $val1 (mixed) -- first value to use for comparison
         *      * $val2 (mixed) -- second value to use for comparison
         *      * $op (string) -- optional operator for comparison
         * OUTPUTS
         *      (bool) -- returns comparison result
         ****
         */
        {
            $val1 = (string)$val1;
            $val2 = (string)$val2;

            switch (strtolower($op)) {
            case 'lt':
            case '<':
                $return = ($val1 < $val2);
                break;
            case 'gt':
            case '>':
                $return = ($val1 > $val2);
                break;
            case '<=':
                $return = ($val1 <= $val2);
                break;
            case '>=':
                $return = ($val1 >= $val2);
                break;
            case 'eq':
            case '==':
            case '=':
            default:
                $return = ($val1 == $val2);
            }

            return $return;
        }

        /****m* sandbox/importFile
         * SYNOPSIS
         */
        function importFile($filename)
        /*
         * FUNCTION
         *
         ****
         */
        {
            $return = '';

            $path = str_replace('/../', '', $filename);
            $path = preg_replace('#^(../|./|/){1,}#', '', $filename);
            $base = lima_config::getInstance()->getPath(lima_config::T_PATH_DATA);

            $path = $base . '/' . $path;

            if (is_file($path)) {
                $return = file_get_contents($path);
            }

            return $return;
        }

        /****m* sandbox/includeSub
         * SYNOPSIS
         */
        function includeSub($filename) 
        /*
         * FUNCTION
         *      include subtemplate
         * INPUTS
         *      * $filename (string) -- filename of subtemplate to include
         ****
         */
        {
            if (!$this->__tpl__->getCacheUsage()) {
                $this->__tpl__->render($filename);
            } else {
                $this->render($filename);
            }
        }

        /****m* sandbox/render
         * SYNOPSIS
         */
        function render($filename, $locale = '') 
        /*
         * FUNCTION
         *      render a template
         * INPUTS
         *      * $filename (string) -- filename of template to render
         *      * $locale (string) -- (optional) locale
         ****
         */
        {
            static $lc = '';

            if ($locale != '') {
                $lc = $locale;
            }

            $cfg = lima_config::getInstance();

            if (!$this->__tpl__->getCacheUsage()) {
                $name = realpath($filename);
            } else {
                $path = realpath($this->__tpl__->getCachePath()) . ($lc != '' ? '/' . $lc : '') . '/';

                $filename = substr($filename, 0, 1) . str_replace('/', '-', substr($filename, 1));
                $filename = $filename . '.php';

                $name = $path . $filename;

                if (!file_exists($name)) {
                    throw new lima_exception_critical('can\'t find template file "' . $filename . '" in path "' . $path . '"');
                }
            }

            require($name);
        }
    }
}
