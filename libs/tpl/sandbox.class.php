<?php

/*
 * This file is part of the 'org.octris.core' package.
 *
 * (c) Harald Lapp <harald@octris.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace org\octris\core\tpl {
    /**
     * Sandbox to execute templates in.
     *
     * @octdoc      c:tpl/sandbox
     * @copyright   copyright (c) 2010-2011 by Harald Lapp
     * @author      Harald Lapp <harald@octris.org>
     */
    class sandbox
    /**/
    {
        /**
         * Template contexts.
         *
         * @octdoc  d:sandbox/T_CONTEXT_HTML, T_CONTEXT_JAVASCRIPT, T_CONTEXT_TEXT, T_CONTEXT_XML
         */
        const T_CONTEXT_HTML       = 1;
        const T_CONTEXT_JAVASCRIPT = 2;
        const T_CONTEXT_TEXT       = 3;
        const T_CONTEXT_XML        = 4;
        /**/

        /**
         * Template data.
         *
         * @octdoc  p:sandbox/$data
         * @var     array
         */
        public $data = array();
        /**/

        /**
         * Internal storage for meta data required for block functions.
         *
         * @octdoc  p:sandbox/$meta
         * @var     array
         */
        protected $meta = array();
        /**/

        /**
         * Internal storage for cut/copied buffers.
         *
         * @octdoc  p:sandbox/$pastebin
         * @var     array
         */
        protected $pastebin = array();
        /**/

        /**
         * Function registry.
         *
         * @octdoc  p:sandbox/$registry
         * @var     array
         */
        protected $registry = array();
        /**/

        /**
         * Context to use for autoescaping.
         *
         * @octdoc  p:sandbox/$context
         * @var     int
         */
        protected $context;
        /**/

        /**
         * Name of file that is rendered by the sandbox instance.
         *
         * @octdoc  p:sandbox/$filename
         * @var     string
         */
        protected $filename = '';
        /**/

        /**
         * Instance of locale class.
         *
         * @octdoc  p:compiler/$l10n
         * @var     \org\octris\core\l10n
         */
        protected $l10n;
        /**/

        /**
         * Constructor
         *
         * @octdoc  m:sandbox/__construct
         */
        public function __construct()
        /**/
        {
        }

        /**
         * Set l10n dependency.
         *
         * @octdoc  m:compiler/setL10n
         * @param   \org\octris\core\l10n       $l10n       Instance of l10n class.
         */
        public function setL10n(\org\octris\core\l10n $l10n)
        /**/
        {
            $this->l10n = $l10n;
        }

        /**
         * Magic caller for registered template functions.
         *
         * @octdoc  m:sandbox/__call
         * @param   string      $name       Name of function to call.
         * @param   mixed       $args       Function arguments.
         * @return  mixed                   Return value of called function.
         */
        public function __call($name, $args)
        /**/
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

        /**
         * Trigger an error and stop processing template.
         *
         * @octdoc  m:sandbox/error
         * @param   string      $msg        Additional error message.
         * @param   int         $line       Line in template the error occured (0, if it's in the class library).
         * @param   int         $cline      Line in the class that triggered the error.
         */
        public function error($msg, $line = 0, $cline = __LINE__)
        /**/
        {
            printf("\n** ERROR: sandbox(%d)**\n", $cline);
            printf("   line :    %d\n", $line);
            printf("   file:     %s\n", $this->filename);
            printf("   message:  %s\n", $msg);

            die();
        }

        /**
         * Register a custom template method.
         *
         * @octdoc  m:sandbox/registerMethod
         * @param   string      $name       Name of template method to register.
         * @param   mixed       $callback   Callback to map to template method.
         * @param   array       $args       For specifying min/max number of arguments required for callback method.
         */
        public function registerMethod($name, $callback, array $args)
        /**/
        {
            $name = strtolower($name);

            $this->registry[$name] = array(
                'callback' => $callback,
                'args'     => array_merge(array('min' => 0, 'max' => 0), $args)
            );
        }

        /**
         * Set values for multiple template variables.
         *
         * @octdoc  m:tpl/setValues
         * @param   array       $array      Key/value array with values.
         */
        public function setValues($array)
        /**/
        {
            foreach ($array as $k => $v) $this->setValue($k, $v);
        }

        /**
         * Set value for one template variable. Note, that resources are not allowed as values.
         * Values of type 'array' and 'object' will be casted to '\org\octris\core\type\collection'
         * unless an 'object' implements the interface '\Traversable'. Traversable objects will
         * be used without casting.
         *
         * @octdoc  m:tpl/setValue
         * @param   string      $name       Name of template variable to set value of.
         * @param   mixed       $value      Value to set for template variable.
         */
        public function setValue($name, $value)
        /**/
        {
            if (is_scalar($value) || (is_object($value) && $value instanceof \Traversable)) {
                $this->data[$name] = $value;
            } elseif (is_resource($value)) {
                $this->error(sprintf('"%s" -- type resource is not allowed', $name), 0, __LINE__);
            } else {
                $this->data[$name] = new \org\octris\core\type\collection($value);
            }
        }

        /**
         * Gettext implementation.
         *
         * @octdoc  m:sandbox/gettext
         * @param   string      $msg        Message to translate.
         * @return  string                  Translated message.
         */
        public function gettext($msg)
        /**/
        {
            return $this->l10n->gettext($msg);
        }

        /**
         * Implementation for '#foreach' block function. Iterates over an array and repeats an enclosed
         * template block.
         *
         * @octdoc  m:sandbox/each
         * @param   string                              $id             uniq identifier for loop.
         * @param   mixed                               $ctrl           Control variable is overwritten and used by this method.
         * @param   \Traversable                        $object         Object to traverse.
         * @param   array                               $meta           Optional control variable for meta information storage.
         * @return  bool                                                Returns 'true' as long as iterator did not reach end of array.
         */
        public function each($id, &$ctrl, \Traversable $object, &$meta = null)
        /**/
        {
            $id = 'each:' . $id;

            $getMeta = function($reset = false) use ($id) {
                $meta =& $this->meta[$id];

                if ($reset) {
                    $meta['key']      = null;
                    $meta['pos']      = null;
                    $meta['count']    = null;
                    $meta['is_first'] = false;
                    $meta['is_last']  = false;
                } elseif (is_null($meta['pos'])) {
                    $meta['pos']      = 0;
                    $meta['is_first'] = true;
                    $meta['is_last']  = false;

                    if ($meta['object'] instanceof \Countable) {
                        $meta['count'] = count($meta['object']);
                    }
                } else {
                    ++$meta['pos'];
                    $meta['is_first'] = false;

                    if (!is_null($meta['count']) && $meta['pos'] == ($meta['count'] - 1)) {
                        $meta['is_last'] = true;
                    }
                }

                return array(
                    'key'       => $meta['iterator']->key(),
                    'pos'       => $meta['pos'],
                    'count'     => $meta['count'],
                    'is_first'  => $meta['is_first'],
                    'is_last'   => $meta['is_last']
                );
            };

            if (!isset($this->meta[$id])) {
                $this->meta[$id] = array(
                    'iterator' => ($object instanceof \IteratorAggregate
                                    ? $object->getIterator()
                                    : $object),
                    'object'   => $object,
                    'key'      => null,
                    'pos'      => null,
                    'count'    => null,
                    'is_first' => false,
                    'is_last'  => false
                );
            }

            if (($return = $this->meta[$id]['iterator']->valid())) {
                $ctrl = $this->meta[$id]['iterator']->current();
                $meta = $getMeta();

                $this->meta[$id]['iterator']->next();
            } else {
                $this->meta[$id]['iterator']->rewind();

                if ($this->meta[$id]['iterator']->valid()) {
                    $ctrl = $this->meta[$id]['iterator']->current();
                    $meta = $getMeta(true);
                } else {
                    $ctrl = null;
                    $meta = array(
                        'key'       => null,
                        'pos'       => null,
                        'count'     => 0,
                        'is_first'  => false,
                        'is_last'   => false
                    );
                }
            }

            if (!is_scalar($ctrl) && !(is_object($ctrl) && $ctrl instanceof \Traversable)) {
                $ctrl = new \org\octris\core\type\collection($ctrl);
            }

            return $return;
        }

        /**
         * Implementation for '#cut' and '#copy' block functions. Starts output buffer.
         *
         * @octdoc  m:sandbox/bufferStart
         * @param   mixed       $ctrl       Control variable to store buffer data in.
         * @param   bool        $cut        Optional flag that indicates if buffer should be cut or copied.
         */
        public function bufferStart(&$ctrl, $cut = true)
        /**/
        {
            array_push($this->pastebin, array(
                'buffer' => &$ctrl,
                'cut'    => $cut
            ));

            ob_start();
        }

        /**
         * Stop output buffer.
         *
         * @octdoc  m:sandbox/bufferEnd
         */
        public function bufferEnd()
        /**/
        {
            $buffer = array_pop($this->pastebin);
            $buffer['buffer'] = ob_get_contents();

            if ($buffer['cut']) {
                ob_end_clean();
            } else {
                ob_end_flush();
            }
        }

        /**
         * Implementation for '#cache' block function. Starts a cache buffer. Returns cache contents by
         * by specified key or generates cached content, if cache content is not available. As second
         * a cache timeout is required. The cache timeout can have one of the following values:
         *
         * - int: relative timeout in seconds.
         * - int: an absolute unix timestamp. Note, that if $timeout contains an integer that is bigger than
         *   the current timestamp, it's guessed to be not ment as a relative timeout but the absolute timestamp.
         * - string: a datetime string as absolute timeout.
         * - 0: no cache.
         * - -1: cache never expires.
         *
         * @octdoc  m:sandbox/cacheStart
         * @param   string      $key            Cache-key to use for buffer.
         * @param   mixed       $timeout        Cache timeout.
         * @return  bool                        Returns true if caching succeeded.
         */
        public function cacheStart($key, $timeout)
        /**/
        {
            // TODO
        }

        /**
         * Stop caching output buffer.
         *
         * @octdoc  m:sandbox/cacheEnd
         */
        public function cacheEnd()
        /**/
        {
            // TODO
        }

        /**
         * Implementation for '#cron' block function. Display block for a period of time.
         *
         * @octdoc  m:sandbox/cron
         * @param   mixed       $start          Start date/time as string or unix timestamp.
         * @param   mixed       $end            Optional end date/time as string or unix timestamp.
         * @return  bool                        Returns true if cron block creation succeeded.
         */
        public function cron($start, $end = 0)
        /**/
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

        /**
         * Implementation for '#loop' block function.
         *
         * @octdoc  m:sandbox/loop
         * @param   string      $id         Uniq identifier for loop.
         * @param   mixed       $ctrl       Control variable for loop.
         * @param   int         $from       Value to start loop at.
         * @param   int         $to         Value to end loop at.
         * @param   array       $meta       Optional control value for meta data.
         * @return  bool                    Returns true as long as loop did not reach the end.
         */
        public function loop($id, &$ctrl, $from, $to, &$meta = null)
        /**/
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

        /**
         * Implementation for '#trigger' block function. The trigger can be used inside a block of type '#loop' or '#each'. An
         * internal counter will be increased for each loop cycle. The trigger will return 'true' for very $steps steps.
         *
         * @octdoc  m:sandbox/trigger
         * @param   string      $id         Uniq identifier of trigger.
         * @param   int         $steps      Optional number of steps trigger should go until signal is raised.
         * @param   int         $start      Optional step to start trigger at.
         * @param   mixed       $reset      Optional trigger reset flag. The trigger is reset if value provided differs from stored reset value.
         * @return  bool                    Returns true if trigger is raised.
         */
        public function trigger($id, $steps = 2, $start = 0, $reset = 1)
        /**/
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

        /**
         * Implementation for '#onchange' block function. Triggers an event if the contents of a variable changes.
         *
         * @octdoc  m:sandbox/onchange
         * @param   string      $id         Uniq identifier of event.
         * @param   mixed       $value      Value of observed variable.
         * @return  bool                    Returns true if variable value change was detected.
         */
        public function onchange($id, $value)
        /**/
        {
            $id = 'onchange:' . $id;

            if (!isset($this->meta[$id])) {
                $this->meta[$id] = NULL;
            }

            $return = ($this->meta[$id] !== $value);

            $this->meta[$id] = $value;

            return $return;
        }

        /**
         * Implementation for 'cycle' function. Cycle can be used inside a block of type '#loop' or '#each'. An
         * internal counter will be increased for each loop cycle. Cycle will return an element of a specified list
         * according to the internal pointer position.
         *
         * @octdoc  m:sandbox/cycle
         * @param   string      $id         Uniq identifier for cycle.
         * @param   array       $array      List of elements to use for cycling.
         * @param   bool        $pingpong   Optional flag indicates whether to start with first element or moving pointer
         *                                  back and forth in case the pointer reached first (or last) element in the list.
         * @param   mixed       $reset      Optional reset flag. The cycle pointer is reset if value provided differs from stored
         *                                  reset value
         * @return  mixed                   Current list item.
         */
        public function cycle($id, $array, $pingpong = false, $reset = 1)
        /**/
        {
            $id = 'cycle:' . $id;

            if (!isset($this->meta[$id])) {
                $this->meta[$id] = array(
                    'iterator'    => $array,
                    'direction'   => 1,
                    'pingpong'    => !!$pingpong,
                    'reset_value' => $reset
                );

                $this->meta[$id]['iterator']->rewind();
            } elseif ($this->meta[$id]['reset_value'] !== $reset) {
                $this->meta[$id]['reset_value'] = $reset;
                $this->meta[$id]['direction']   = 1;

                $this->meta[$id]['iterator']->rewind();
            }

            $return = '';

            if (!$this->meta[$id]['iterator']->valid()) {
                if ($this->meta[$id]['pingpong']) {
                    if ($this->meta[$id]['direction'] == 1) {
                        $this->meta[$id]['direction'] = -1;
                        $this->meta[$id]['iterator']->prev();
                    } else {
                        $this->meta[$id]['direction'] = 1;
                        $this->meta[$id]['iterator']->rewind();
                    }
                } else {
                    $this->meta[$id]['iterator']->rewind();
                }
            }

            if ($this->meta[$id]['iterator']->valid()) {
                $return = $this->meta[$id]['iterator']->current()->item;

                if ($this->meta[$id]['direction'] == 1) {
                    $this->meta[$id]['iterator']->next();
                } else {
                    $this->meta[$id]['iterator']->prev();
                }
            }

            return $return;
        }

        /**
         * Output specified value.
         *
         * @octdoc  m:sandbox/write
         * @param   string      $val            Optional value to output.
         * @param   bool        $auto_escape    Optional flag whether to auto-escape value.
         */
        public function write($val = '', $auto_escape = true)
        /**/
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

        /**
         * Dump contents of variable and return it as string.
         *
         * @octdoc  m:sandbox/dump
         * @param   mixed       $var            Variable to dump.
         * @return  string                      Dumped variable contents as string.
         */
        public function dump($var)
        /**/
        {
            return var_export(
                ((is_object($var) &&
                 ($var instanceof \ArrayIterator || $var instanceof \ArrayObject))
                ? (array)$var
                : $var),
                true
            );
        }

        /**
         * Read a file and return it as string.
         *
         * @octdoc  m:sandbox/includetpl
         * @param   string      $file       File to include.
         * @return  string                  File contents.
         */
        public function includetpl($file)
        /**/
        {
            return (is_readable($file)
                    ? file_get_contents($file)
                    : '');
        }

        /**
         * Render a template and output rendered template to stdout.
         *
         * @octdoc  m:sandbox/render
         * @param   string      $filename       Filename of template to render.
         * @param   int         $context        Context of files. Context is required for auto-escaping.
         */
        public function render($filename, $context)
        /**/
        {
            $this->filename = $filename;
            $this->context  = $context;

            require($filename);
        }
    }
}
