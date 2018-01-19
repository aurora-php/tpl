<?php

/*
 * This file is part of the 'octris/tpl' package.
 *
 * (c) Harald Lapp <harald@octris.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Octris\Tpl;

/**
 * Sandbox to execute templates in.
 *
 * @copyright   copyright (c) 2010-2018 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
class Sandbox
{
    /**
     * Template data.
     *
     * @type    \Octris\Core\Type\Collection;
     */
    public $data;

    /**
     * Storage for sandbox internal data objects.
     *
     * @type    \Octris\Tpl\Sandbox\Storage
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
     * Extension library.
     *
     * @type    \Octris\Tpl\Library
     */
    protected $library;

    /**
     * Name of file that is rendered by the sandbox instance.
     *
     * @type    string
     */
    protected $filename = '';

    /**
     * Character encoding of template.
     *
     * @type    string
     */
    protected $encoding;

    /**
     * Constructor
     *
     * @param   string                      $encoding       Character encoding of template.
     */
    public function __construct(\Octris\Tpl\Library $library, $encoding = 'utf-8')
    {
        $this->storage = \Octris\Tpl\Sandbox\Storage::getInstance();
        $this->data = []; //new \Octris\Core\Type\Collection();
        $this->encoding = $encoding;
        $this->library = $library;
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
        \Octris\Debug::getInstance()->error(
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
     * Set extension library.
     *
     * @param   \Octris\Tpl\Library                     $library            Instance of extension library.
     */
    public function setLibrary(\Octris\Tpl\Library $library) {
        $this->library = $library;
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
        if (is_resource($value)) {
            throw new \InvalidArgumentException('Value of type "resource" is not allowed');
        } else {
            $this->data[$name] = $value;
        }
    }

    /**
     * Output specified value.
     *
     * @param   string          $val            Optional value to output.
     * @param   string          $escape         Optional escaping to use.
     */
    public function write($val = '', $escape = '')
    {
        if ($escape !== \Octris\Tpl::ESC_NONE) {
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
     * @param   string      $filename       Filename of template to render for error reporting.
     * @param   string      $content        Template contents to render.
     */
    public function render($filename, $content)
    {
        $this->filename = $filename;

        try {
            eval('?>' . $content);
        } catch (\Exception $e) {
            $this->error($e->getMessage(), $e->getLine(), __LINE__, $e->getFile(), $e->getTraceAsString());
        }
    }

    /**
     * Render a template and return the output.
     *
     * @param   string      $filename       Filename of template to render for error reporting.
     * @param   string      $content        Template contents to render.
     * @return  string                      Rendered template.
     */
    public function fetch($filename, $content)
    {
        $this->filename = $filename;

        try {
            ob_start();

            eval('?>' . $content);

            $content = ob_get_contents();
            ob_end_clean();
        } catch (\Exception $e) {
            $this->error($e->getMessage(), $e->getLine(), __LINE__, $e->getFile(), $e->getTraceAsString());
        }

        return $content;
    }
}
