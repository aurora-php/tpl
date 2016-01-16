<?php

/*
 * This file is part of the 'octris/core' package.
 *
 * (c) Harald Lapp <harald@octris.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Octris\Core\Tpl;

/**
 * Sandbox to execute templates in.
 *
 * @copyright   copyright (c) 2010-2016 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
class Sandbox
{
    /**
     * Template data.
     *
     * @type    array
     */
    public $data = array();

    /**
     * Storage for sandbox internal data objects.
     *
     * @type    \Octris\Core\Tpl\Sandbox\Storage
     */
    protected $storage;

    /**
     * Internal storage for meta data required for block functions.
     *
     * @type    array
     */
    protected $meta = array();

    /**
     * Internal storage for cut/copied buffers.
     *
     * @type    array
     */
    protected $pastebin = array();

    /**
     * Function registry.
     *
     * @type    array
     */
    protected $registry = array();

    /**
     * Name of file that is rendered by the sandbox instance.
     *
     * @type    string
     */
    protected $filename = '';

    /**
     * Instance of locale class.
     *
     * @type    \Octris\Core\L10n
     */
    protected $l10n;

    /**
     * Instance of caching backend for template snippets.
     *
     * @type    \Octris\Core\Cache|null
     */
    protected $cache = null;

    /**
     * Escaper instance.
     *
     * @type    \Zend\Escaper\Escaper
     */
    protected $escaper;

    /**
     * Charset of template.
     *
     * @type    string
     */
    protected $charset;

    /**
     * Constructor
     *
     * @param   string                      $charset    Charset of template.
     */
    public function __construct($charset = 'utf-8')
    {
        $this->storage = \Octris\Core\Tpl\Sandbox\Storage::getInstance();
        $this->escaper = new \Zend\Escaper\Escaper($this->charset = $charset);
    }

    /**
     * Set l10n dependency.
     *
     * @param   \Octris\Core\L10n       $l10n       Instance of l10n class.
     */
    public function setL10n(\Octris\Core\L10n $l10n)
    {
        $this->l10n = $l10n;
    }

    /**
     * Determine line number an error occured.
     *
     * @return  int                     Determined line number.
     */
    public function getErrorLineNumber()
    {
        $trace = debug_backtrace();

        return $trace[2]['line'];
    }

    /**
     * Magic caller for registered template functions.
     *
     * @param   string      $name       Name of function to call.
     * @param   mixed       $args       Function arguments.
     * @return  mixed                   Return value of called function.
     */
    public function __call($name, $args)
    {
        if (!isset($this->registry[$name])) {
            $this->error(sprintf('"%s" -- unknown function', $name), $this->getErrorLineNumber(), __LINE__);
        } elseif (!is_callable($this->registry[$name]['callback'])) {
            $this->error(sprintf('"%s" -- unable to call function', $name), $this->getErrorLineNumber(), __LINE__);
        } elseif (count($args) < $this->registry[$name]['args']['min']) {
            $this->error(sprintf('"%s" -- not enough arguments', $name), $this->getErrorLineNumber(), __LINE__);
        } elseif (count($args) > $this->registry[$name]['args']['max']) {
            $this->error(sprintf('"%s" -- too many arguments', $name), $this->getErrorLineNumber(), __LINE__);
        } else {
            return call_user_func_array($this->registry[$name]['callback'], $args);
        }
    }

    /**
     * Trigger an error and stop processing template.
     *
     * @param   string      $msg        Additional error message.
     * @param   int         $line       Line in template the error occured (0, if it's in the class library).
     * @param   int         $cline      Line in the class that triggered the error.
     * @param   string      $filename   Optional filename.
     * @param   string      $trace      Optional trace.
     */
    public function error($msg, $line = 0, $cline = __LINE__, $filename = null, $trace = null)
    {
        \Octris\Core\Tpl\Error::write(
            'sandbox',
            $cline,
            [
                'line' => $line,
                'file' => (is_null($filename) ? $this->filename : $filename),
                'message' => $msg
            ],
            $trace
        );
    }

    /**
     * Register a custom template method.
     *
     * @param   string      $name       Name of template method to register.
     * @param   mixed       $callback   Callback to map to template method.
     * @param   array       $args       For specifying min/max number of arguments required for callback method.
     */
    public function registerMethod($name, $callback, array $args)
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
     * @param   array|\Traversable       $array      Key/value array with values.
     */
    public function setValues($array)
    {
        if (!is_array($array) && !($array instanceof \Traversable)) {
            throw new \InvalidArgumentException('Array or Traversable object expected');
        }

        foreach ($array as $k => $v) {
            $this->setValue($k, $v);
        }
    }

    /**
     * Set value for one template variable. Note, that resources are not allowed as values.
     * Values of type 'array' and 'object' will be casted to '\Octris\Core\Type\Collection\collection'
     * unless an 'object' implements the interface '\Traversable'. Traversable objects will
     * be used without casting.
     *
     * @param   string      $name       Name of template variable to set value of.
     * @param   mixed       $value      Value to set for template variable.
     */
    public function setValue($name, $value)
    {
        if (is_scalar($value) || (is_object($value) && $value instanceof \Traversable)) {
            $this->data[$name] = $value;
        } elseif (is_resource($value)) {
            throw new \InvalidArgumentException('Resource is not allowed');
        } else {
            $this->data[$name] = new \Octris\Core\Type\Collection($value);
        }
    }

    /**
     * Set cache for template snippets.
     *
     * @param   \Octris\Core\Cache      $cache          Caching instance.
     */
    public function setSnippetCache(\Octris\Core\Cache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Gettext implementation.
     *
     * @param   string      $msg        Message to translate.
     * @return  string                  Translated message.
     */
    public function gettext($msg)
    {
        return $this->l10n->gettext($msg);
    }

    /**
     * Implementation for '#foreach' block function. Iterates over an array and repeats an enclosed
     * template block.
     *
     * @param   \Octris\Core\Tpl\Sandbox\EachIterator   $iterator       Iterator to use.
     * @param   mixed                                       $ctrl           Control variable is overwritten and used by this method.
     * @param   array                                       $meta           Optional variable for meta information storage.
     * @return  bool                                                        Returns 'true' as long as iterator did not reach end of array.
     */
    public function each(\Octris\Core\Tpl\Sandbox\EachIterator $iterator, &$ctrl, &$meta = null)
    {
        if (($return = $iterator->valid())) {
            $ctrl = $iterator->current();
            $meta = $iterator->getMeta();

            $iterator->next();
        } else {
            $iterator->rewind();

            $ctrl = null;
            $meta = array(
                'key'       => null,
                'pos'       => null,
                'count'     => null,
                'is_first'  => false,
                'is_last'   => false
            );
        }

        return $return;
    }

    protected $chunks = array();

    /**
     *
     */
    public function chainStart($join, $prefix = '', $postfix = '')
    {
        $this->chains[] = [
            'join' => $join,
            'prefix' => $prefix,
            'postfix' => $postfix,
            'chunks' => []
        ];

        ob_start();
    }

    public function chainEnd()
    {
        ob_end_clean();

        $chain = array_pop($this->chains);

        if (count($chain['chunks']) > 0) {
            $out = $chain['prefix'] . implode($chain['join'], $chain['chunks']) . $chain['postfix'];

            if (count($this->chains) > 0) {
                $this->chains[count($this->chains) - 1]['chunks'][] = $out;
            } else {
                print $out;
            }
        }
    }

    public function chunkStart()
    {
        ob_start();
    }

    public function chunkEnd()
    {
        $this->chains[count($this->chains) - 1]['chunks'][] = ob_get_contents();

        ob_end_clean();
    }

    /**
     * Implementation for '#cut' and '#copy' block functions. Starts output buffer.
     *
     * @param   mixed       $ctrl       Control variable to store buffer data in.
     * @param   bool        $cut        Optional flag that indicates if buffer should be cut or copied.
     */
    public function bufferStart(&$ctrl, $cut = true)
    {
        array_push($this->pastebin, array(
            'buffer' => &$ctrl,
            'cut'    => $cut
        ));

        ob_start();
    }

    /**
     * Stop output buffer.
     */
    public function bufferEnd()
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
     * by specified key or generates cached content, if cache content is not available. An optional
     * escaping method may be specified.
     *
     * @param   string      $key            Cache-key to lookup.
     * @param   string      $escape         Optional escaping to use for output.
     * @return  bool                        Returns true, if key was available in cache.
     */
    public function cacheLookup($key, $escape = \Octris\Core\Tpl::ESC_NONE)
    {
        if (!($return = is_null($this->cache))) {
            if (($return = $this->cache->exists($key))) {
                $this->write($this->cache->fetch($key), $escape);
            }
        }

        return $return;
    }

    /**
     * Store date in the cache. A cache timeout is required. The cache timeout can have
     * one of the following values:
     *
     * - int: relative timeout in seconds.
     * - int: an absolute unix timestamp. Note, that if $timeout contains an integer that is bigger than
     *   the current timestamp, it's guessed to be not ment as a relative timeout but the absolute timestamp.
     * - string: a datetime string as absolute timeout.
     * - 0: no cache.
     * - -1: cache never expires.
     *
     * @param   string      $key            Key to use for storing buffer in cache.
     * @param   mixed       $data           Data to store in cache.
     * @param   int         $timeout        Cache timeout.
     */
    public function cacheStore($key, $data, $timeout)
    {
        if (!is_null($this->cache)) {
            $this->cache->save($key, $data, $timeout);
        }
    }

    /**
     * Implementation for '#cron' block function. Display block for a period of time.
     *
     * @param   mixed       $start          Start date/time as string or unix timestamp.
     * @param   mixed       $end            Optional end date/time as string or unix timestamp.
     * @return  bool                        Returns true if cron block creation succeeded.
     */
    public function cron($start, $end = 0)
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
     * Implementation for '#trigger' block function. The trigger can be used inside a block of type '#loop' or '#each'. An
     * internal counter will be increased for each loop cycle. The trigger will return 'true' for very $steps steps.
     *
     * @param   string      $id         Uniq identifier of trigger.
     * @param   int         $steps      Optional number of steps trigger should go until signal is raised.
     * @param   int         $start      Optional step to start trigger at.
     * @param   mixed       $reset      Optional trigger reset flag. The trigger is reset if value provided differs from stored reset value.
     * @return  bool                    Returns true if trigger is raised.
     */
    public function trigger($id, $steps = 2, $start = 0, $reset = 1)
    {
        $id = 'trigger:' . $id . ':' . crc32("$steps:$start");

        if (!isset($this->meta[$id])) {
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

            $this->meta[$id] = $get_generator();
        }

        $this->meta[$id]->send($reset);

        $return = $this->meta[$id]->current();
        $this->meta[$id]->next();

        return $return;
    }

    /**
     * Implementation for '#onchange' block function. Triggers an event if the contents of a variable changes.
     *
     * @param   string      $id         Uniq identifier of event.
     * @param   mixed       $value      Value of observed variable.
     * @return  bool                    Returns true if variable value change was detected.
     */
    public function onchange($id, $value)
    {
        $id = 'onchange:' . $id;

        if (!isset($this->meta[$id])) {
            $this->meta[$id] = null;
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
     * @param   string      $id         Uniq identifier for cycle.
     * @param   array       $array      List of elements to use for cycling.
     * @param   bool        $pingpong   Optional flag indicates whether to start with first element or moving pointer
     *                                  back and forth in case the pointer reached first (or last) element in the list.
     * @param   mixed       $reset      Optional reset flag. The cycle pointer is reset if value provided differs from stored
     *                                  reset value
     * @return  mixed                   Current list item.
     */
    public function cycle($id, $array, $pingpong = false, $reset = 1)
    {
        $id = 'cycle:' . $id;

        if (!isset($this->meta[$id])) {
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

            $this->meta[$id] = $get_generator();
        }

        $this->meta[$id]->send($reset);

        $return = $this->meta[$id]->current();
        $this->meta[$id]->next();

        return $return;
    }

    /**
     * Escape a value according to the specified escaping context.
     *
     * @param   string          $val            Value to escape.
     * @param   string          $escape         Escaping to use.
     */
    public function escape($val, $escape)
    {
        switch ($escape) {
            case \Octris\Core\Tpl::ESC_ATTR:
                $val = $this->escaper->escapeHtmlAttr($val);
                break;
            case \Octris\Core\Tpl::ESC_CSS:
                $val = $this->escaper->escapeCss($val);
                break;
            case \Octris\Core\Tpl::ESC_HTML:
                $val = $this->escaper->escapeHtml($val);
                break;
            case \Octris\Core\Tpl::ESC_JS:
                $val = $this->escaper->escapeJs($val);
                break;
            case \Octris\Core\Tpl::ESC_TAG:
                throw new \Exception('Escaping "ESC_TAG" is not implemented!');
                break;
            case \Octris\Core\Tpl::ESC_URI:
                throw new \Exception('Escaping "ESC_URI" is not implemented!');
                break;
        }

        return $val;
    }

    /**
     * Output specified value.
     *
     * @param   string          $val            Optional value to output.
     * @param   string          $escape         Optional escaping to use.
     */
    public function write($val = '', $escape = '')
    {
        if ($escape !== \Octris\Core\Tpl::ESC_NONE) {
            $val = $this->escape($val, $escape);
        }

        print $val;
    }

    /**
     * Read a file and return it as string.
     *
     * @param   string      $file       File to include.
     * @return  string                  File contents.
     */
    public function includetpl($file)
    {
        return (is_readable($file)
                ? file_get_contents($file)
                : '');
    }

    /**
     * Render a template and output rendered template to stdout.
     *
     * @param   string      $filename       Filename of template to render.
     */
    public function render($filename)
    {
        $this->filename = $filename;

        try {
            require($filename);
        } catch (\Exception $e) {
            $this->error($e->getMessage(), $e->getLine(), __LINE__, $e->getFile(), $e->getTraceAsString());
        }
    }

    /**
     * Render a template and return the output.
     *
     * @param   string      $filename       Filename of template to render.
     * @return  string                      Rendered template.
     */
    public function fetch($filename)
    {
        $this->filename = $filename;

        try {
            ob_start();

            require($filename);

            $content = ob_get_contents();
            ob_end_clean();
        } catch (\Exception $e) {
            $this->error($e->getMessage(), $e->getLine(), __LINE__, $e->getFile(), $e->getTraceAsString());
        }

        return $content;
    }
}
