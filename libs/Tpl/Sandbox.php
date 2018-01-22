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
    protected $data;

    /**
     * Extension library.
     *
     * @type    \Octris\Tpl\Library
     */
    protected $library;

    /**
     * Character encoding of template.
     *
     * @type    string
     */
    protected $encoding;

    /**
     * Name of file that is rendered by the sandbox instance.
     *
     * @type    string
     */
    protected $filename;

    /**
     * Template content.
     *
     * @type    string
     */
    protected $content;

    /**
     * Constructor
     *
     * @param   \Octris\Tpl\Library $library        Language statement library.
     * @param   string              $encoding       Character encoding of template.
     * @param   string              $filename       Filename of template to render for error reporting.
     * @param   string              $content        Template contents to render.
     * @param   array               $data           Initial template data.
     */
    public function __construct(\Octris\Tpl\Library $library, $encoding, $filename, $content, array $data)
    {
        $this->encoding = $encoding;
        $this->library = $library;
        $this->filename = $filename;
        $this->content = $content;
        $this->data = $data;

        $this->escaper = new \Zend\Escaper\Escaper($this->encoding);
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
     * Escape a value according to the specified escaping context.
     *
     * @param   string          $val            Value to escape.
     * @param   string          $escape         Escaping to use.
     */
    protected function escape($val, $escape)
    {
        if (is_null($val)) {
            return '';
        }

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
    protected function write($val = '', $escape = '')
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
     */
    public function render()
    {
        try {
            eval('?>' . $this->content);
        } catch (\Exception $e) {
            $this->error($e->getMessage(), $e->getLine(), __LINE__, $e->getFile(), $e->getTraceAsString());
        }
    }

    /**
     * Render a template and return the output.
     *
     * @return  string                      Rendered template.
     */
    public function fetch()
    {
        try {
            ob_start();

            eval('?>' . $this->content);

            $content = ob_get_contents();
            ob_end_clean();
        } catch (\Exception $e) {
            $this->error($e->getMessage(), $e->getLine(), __LINE__, $e->getFile(), $e->getTraceAsString());
        }

        return $content;
    }

    /**
     * Render a template and save output to a file.
     *
     * @param   string      $filename       Filename of template to render.
     * @param   string      $savename       Filename to save output to.
     * @param   string      $escape         Optional escaping to use.
     * @param   bool|int                    Returns number of bytes written or false in case of an error.
     */
    public function save($savename)
    {
        return file_put_contents($savename, $this->fetch());
    }
}
