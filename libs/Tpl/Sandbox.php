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
     * @type    array
     */
    protected $data;

    /**
     * Function registry.
     *
     * @type    array
     */
    protected $registry = [];

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
     * @param   array       $args       Function arguments.
     * @return  mixed                   Return value of called function.
     */
    public function __call($name, array $args)
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
            $this->registry[$name]['callback'](...$args);
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
    protected function error($msg, $line = 0, $cline = __LINE__, $filename = null, $trace = null)
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
     * @param   iterable        $array      Key/value array with values.
     */
    public function setValues(iterable $array)
    {
        foreach ($array as $k => $v) {
            $this->setValue($k, $v);
        }
    }

    /**
     * Set value for one template variable. Note, that resources are not allowed as values.
     *
     * @param   string      $name       Name of template variable to set value of.
     * @param   mixed       $value      Value to set for template variable.
     */
    public function setValue($name, $value)
    {
        $this->data[$name] = $value;
    }

    /**
     * Register a custom template function.
     *
     * @param   string      $name       Name of template function to register.
     * @param   callable    $fn         Callback to map to template function.
     */
    public function registerFunction($name, callable $fn)
    {
        $this->registry[strtolower($name)] = [
            'callback' => $fn,
            'args' => \Octris\Tpl\Extension::getNumberOfParameters($fn)
        ];
    }

    /**
     * Create generator for iterating iterable data.
     *
     * @param   iterable    $data       Iterable data.
     * @return  \Generator              A generator instance.
     */
    protected function createForeach(iterable $data)
    {
        $generator = function(iterable $data) {
            if (is_array($data) || $data instanceof \Countable) {
                $cnt = count($data);
                $pos = 0;

                foreach ($data as $k => $v) {
                    $meta = [
                        'is_first' => ($pos == 0),
                        'is_last' => (($pos + 1) == $cnt),
                        'count' => $cnt,
                        'pos' => $pos++,
                        'key' => $k
                    ];

                    yield [$v, $meta];
                }
            } else {
                $cnt = null;
                $pos = 0;

                $data->rewind();
                while (($key = $data->key()) !== null) {
                    $meta = [
                        'is_first' => ($pos == 0),
                        'is_last' => false,
                        'count' => $cnt,
                        'pos' => $pos++,
                        'key' => $key
                    ];

                    $data->next();

                    $meta['is_last'] = ($data->key() === null);

                    yield [$data->current(), $meta];
                }
            }
        };

        return $generator($data);
    }

    /**
     * Create generator for iterating for(;;) loop.
     *
     * @param   int         $start              Start position.
     * @param   int         $end                End position.
     * @param   int         $stap               Iterator steps.
     * @return  array|\Generator
     */
    protected function createFor($start, $end, $step) {
        if ($start == $end) {
            $ret = [];
        } else {
            $ret = (function($start, $end, $step) {
                $step = abs($step == 0 ? 1 : $step);
                $i = $start;

                if ($start < $end) {
                    $a =& $i; $b = $end;
                } else {
                    $a = $end; $b =& $i; $step = -$step;
                }

                $cnt = floor($b - $a) / abs($step);
                $pos = 0;

                for (; $a < $b; $i += $step) {
                    $meta = [
                        'is_first' => ($pos == 0),
                        'is_last' => (($pos + 1) == $cnt),
                        'count' => $cnt,
                        'pos' => $pos++,
                        'key' => $i
                    ];

                    yield [$i, $meta];
                }
            })($start, $end, $step);
        }

        return $ret;
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
            case \Octris\Tpl::ESC_ATTR:
                $val = $this->escaper->escapeHtmlAttr($val);
                break;
            case \Octris\Tpl::ESC_CSS:
                $val = $this->escaper->escapeCss($val);
                break;
            case \Octris\Tpl::ESC_HTML:
                $val = $this->escaper->escapeHtml($val);
                break;
            case \Octris\Tpl::ESC_JS:
                $val = $this->escaper->escapeJs($val);
                break;
            case \Octris\Tpl::ESC_TAG:
                throw new \Exception('Escaping "ESC_TAG" is not implemented!');
                break;
            case \Octris\Tpl::ESC_URI:
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
     * @param   string      $savename       Filename to save output to.
     * @param   bool|int                    Returns number of bytes written or false in case of an error.
     */
    public function save($savename)
    {
        return file_put_contents($savename, $this->fetch());
    }
}
