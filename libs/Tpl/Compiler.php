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

use \Octris\Tpl\Compiler\Grammar;

/**
 * Implementation of template compiler.
 *
 * @copyright   copyright (c) 2010-2018 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
class Compiler
{
    /**
     * Instance of parser class.
     *
     * @type    \Octris\Parser|null
     */
    protected static $parser = null;

    /**
     * Name of file currently compiled.
     *
     * @type    string
     */
    protected $filename = '';

    /**
     * Stores pathes to look into when searching for template to load.
     *
     * @type    array
     */
    protected $searchpath = array();

    /**
     * Constructor.
     */
    public function __construct()
    {
    }

    /**
     * Register pathname for looking up templates in.
     *
     * @param   mixed       $pathname       Name of path to register.
     */
    public function addSearchPath($pathname)
    {
        if (is_array($pathname)) {
            foreach ($pathname as $path) {
                $this->addSearchPath($path);
            }
        } else {
            if (!in_array($pathname, $this->searchpath)) {
                $this->searchpath[] = $pathname;
            }
        }
    }

    /**
     * Return list of search pathes.
     *
     * @return  array                       Search pathes.
     */
    public function getSearchPath()
    {
        return $this->searchpath;
    }

    /**
     * Lookup a template file in the configured searchpathes.
     *
     * @param   string      $filename       Name of file to lookup.
     */
    public function findFile($filename)
    {
        $return = false;

        foreach ($this->searchpath as $path) {
            $test = $path . '/' . $filename;

            if (file_exists($test) && is_readable($test)) {
                if (($dir = dirname($filename)) !== '') {
                    // add complete path of file for future relativ path lookups
                    $this->addSearchPath($path . '/' . $dir);
                }

                $return = $test;
                break;
            }
        }

        return $return;
    }

    /**
     * Trigger an error and halt execution.
     *
     * @param   string      $ifile      Internal filename the error occured in.
     * @param   int         $iline      Internal line number the error occured in.
     * @param   int         $line       Line in template the error was triggered for.
     * @param   mixed       $token      Token that triggered the error.
     * @param   mixed       $payload    Optional additional information. Either an array of expected token IDs or an additional message to output.
     */
    protected function error($ifile, $iline, $line, $token, $payload = null)
    {
        $info = [
            'line' => $line,
            'file' => $this->filename,
            'token' => self::$parser->getTokenName($token)
        ];

        if (is_array($payload)) {
            $info['expected'] = implode(', ', array_map(function ($token) {
                return self::$parser->getTokenName($token);
            }, $payload));
        } elseif (isset($payload)) {
            $info['message'] = $payload;
        }

        \Octris\Debug::getInstance()->error($ifile, $iline, $info, '\Octris\Tpl\CompilerException');
    }

    /**
     * Compile tokens to PHP code.
     *
     * @param   array       $tokens     Array of tokens to compile.
     * @param   array       $blocks     Block information required by analyzer / compiler.
     * @param   string      $escape     Escaping to use.
     * @return  string                  Generated PHP code.
     */
    protected function compile(&$tokens, &$blocks, $escape)
    {
        $stack = array();
        $code  = array();

        $last_tokens = array();

        $getNextToken = function (&$tokens) use (&$last_tokens) {
            if (($current = array_shift($tokens))) {
                $last_tokens[] = $current['token'];
            }

            return $current;
        };
        $getLastToken = function ($tokens, $idx) {
            if (($tmp = array_slice($tokens, $idx, 1))) {
                $return = array_pop($tmp);
            } else {
                $return = 0;
            }

            return $return;
        };

        while (($current = $getNextToken($tokens))) {
            extract($current);

            switch ($token) {
                case grammar::T_IF_OPEN:
                case grammar::T_FOREACH_OPEN:
                case grammar::T_FOR_OPEN:
                case grammar::T_BLOCK_OPEN:
                    // replace/rewrite block call
                    $value = strtolower($value);
                    var_dump($value);

                    list($_start, $_end) = Compiler\Rewrite::$value(array_reverse($code));

                    $code = array($_start);
                    $blocks['compiler'][] = $_end;

                    if (($err = Compiler\Rewrite::getError()) != '') {
                        $this->error(__FILE__, __LINE__, $line, $token, $err);
                    }
                    break;
                case grammar::T_IF_ELSE:
                    $code[] = '} else {';
                    break;
                case grammar::T_BLOCK_CLOSE:
                    $code[] = array_pop($blocks['compiler']);
                    break;
                case grammar::T_ARRAY_CLOSE:
                case grammar::T_BRACE_CLOSE:
                    array_push($stack, $code);
                    $code = array();
                    break;
                case grammar::T_ARRAY_OPEN:
                    $code = array('[' . array_reduce(array_reverse($code), function ($code, $snippet) {
                        static $last = '';

                        if ($code != '') {
                            $code .= (($last == '=>' || $snippet == '=>') ? '' : ', ');
                        }

                        $code .= $last = $snippet;

                        return $code;
                    }, '') . ']');

                    if (($tmp = array_pop($stack))) {
                        $code = array_merge($tmp, $code);
                    }
                    break;
                case grammar::T_DDUMP:
                case grammar::T_DPRINT:
                case grammar::T_ESCAPE:
                case grammar::T_LET:
                case grammar::T_METHOD:
                    // replace/rewrite method call
                    $value = strtolower($value);

                    if ($token == grammar::T_DDUMP || $token == grammar::T_DPRINT) {
                        // ddump and dprint need to be treated a little different from other method calls,
                        // because we include template-filename and template-linenumber in arguments
                        $code = array(Compiler\Rewrite::$value(
                            array_merge(
                                array('"' . $file . '"', (int)$line),
                                array_reverse($code)
                            )
                        ));
                    } else {
                        $code = array(Compiler\Rewrite::$value(array_reverse($code)));
                    }

                    if (($err = Compiler\Rewrite::getError()) != '') {
                        $this->error(__FILE__, __LINE__, $line, $token, $err);
                    }

                    if (($tmp = array_pop($stack))) {
                        $code = array_merge($tmp, $code);
                    }
                    break;
                case grammar::T_ARRAY_OPEN:
                    $code[] = '[';
                    break;
                case grammar::T_MACRO:
                    // resolve macro
                    $value = strtolower(substr($value, 1));

                    array_walk($code, function (&$value) {
                        // normalize values for macro argument
                        if (preg_match('/^("|\')(.*)\1$/', $value, $match)) {
                            $value = $match[2];
                        } elseif ($value == 'true') {
                            $value = true;
                        } elseif ($value == 'false') {
                            $value = false;
                        } elseif ($value == 'null') {
                            $value = null;
                        } elseif (is_numeric($value)) {
                            if (strpos($value, '.') !== false) {
                                $value = (double)$value;
                            } else {
                                $value = (int)$value;
                            }
                        }
                    });

                    $code  = array(
                        Compiler\Macro::execMacro(
                            $value,
                            array_reverse($code),
                            array('compiler' => $this, 'escape' => $escape)
                        )
                    );

                    if (($err = Compiler\Macro::getError()) != '') {
                        $this->error(__FILE__, __LINE__, $line, $token, $err);
                    }

                    $code[] = implode(', ', array_pop($stack));
                    break;
                case grammar::T_CONSTANT:
                    $value = strtoupper($value);
                    $tmp   = Compiler\Constant::getConstant($value);

                    if (($err = Compiler\Constant::getError()) != '') {
                        $this->error(__FILE__, __LINE__, $line, $token, $err);
                    }

                    $code[] = (is_string($tmp) ? '"' . $tmp . '"' : (int)$tmp);
                    break;
                case grammar::T_VARIABLE:
                    $tmp = sprintf(
                        '$this->data["%s"]',
                        implode('"]["', explode(':', strtolower(substr($value, 1))))
                    );

                    // $code[] = sprintf('(is_callable(%1$s) ? %1$s() : %1$s)', $tmp);
                    $code[] = $tmp;
                    break;
                case grammar::T_BOOL:
                case grammar::T_STRING:
                case grammar::T_NUMBER:
                case grammar::T_ARRAY_KEY:
                    $code[] = $value;
                    break;
                case grammar::T_PUNCT:
                case grammar::T_BRACE_OPEN:
                    // nothing to do for these tokens
                    break;
                default:
                    $this->error(__FILE__, __LINE__, $line, $token, 'unknown token');
                    break;
            }
        }

        /*
         * NOTE: Regarding newlines behind PHP closing tag '?>'. this is because PHP 'eats' newslines
         *       after PHP closing tag. For details refer to:
         *
         *      http://shiflett.org/blog/2005/oct/php-stripping-newlines
         */
        $last_token = $getLastToken($last_tokens, -1);

        if (in_array($last_token, array(grammar::T_LET, grammar::T_DDUMP, grammar::T_DPRINT))) {
            $code = array('<?php ' . implode('', $code) . '; ?>'."\n");
        } elseif (in_array($last_token, array(grammar::T_CONSTANT, grammar::T_MACRO))) {
            $code = array(implode('', $code));
        } elseif (!in_array($last_token, array(grammar::T_BLOCK_OPEN, grammar::T_BLOCK_CLOSE, grammar::T_IF_OPEN, grammar::T_IF_ELSE))) {
            if ($last_token == grammar::T_ESCAPE) {
                // no additional escaping, when 'escape' method was used
                $code = array('<?php $this->write(' . implode('', $code) . '); ?>'."\n");
            } else {
                $code = array('<?php $this->write(' . implode('', $code) . ', "' . $escape . '"); ?>'."\n");
            }
        } else {
            $code = array('<?php ' . implode('', $code) . ' ?>'."\n");
        }

        return $code;
    }

    /**
     * Setup toolchain.
     *
     * @param   array       $blocks         Block information required by analyzer / compiler.
     */
    protected function setup(array &$blocks)
    {
        $grammar = new \Octris\Tpl\Compiler\Grammar();
        self::$parser = new \Octris\Parser($grammar, [grammar::T_WHITESPACE]);

        $chain = 0;

        $grammar->addEvent(grammar::T_IF_OPEN, function ($current) use (&$blocks) {
            $blocks['analyzer'][] = $current;
        });
        $grammar->addEvent(grammar::T_BLOCK_OPEN, function ($current) use (&$blocks, &$chain) {
            switch ($current['value']) {
                case '#chain':
                    ++$chain;
                    break;
                case '#chunk':
                    if ($chain > 0) {
                        break;
                    }

                    $this->error(__FILE__, __LINE__, $current['line'], $current['value'], '"only allowed inside a "chain" block');
                    break;
            }

            $blocks['analyzer'][] = $current;
        });
        $grammar->addEvent(grammar::T_BLOCK_CLOSE, function ($current) use (&$blocks, &$chain) {
            // closing block only allowed if a block is open
            if (!($block = array_pop($blocks['analyzer']))) {
                $this->error(__FILE__, __LINE__, $current['line'], $current['value'], 'there is no open block');
            } elseif ($block['value'] == '#chain') {
                --$chain;
            }
        });
        $grammar->addEvent(grammar::T_IF_ELSE, function ($current) use (&$blocks) {
            if ((($cnt = count($blocks['analyzer'])) > 0 && $blocks['analyzer'][$cnt - 1]['token'] != grammar::T_IF_OPEN)) {
                $this->error(__FILE__, __LINE__, $current['line'], $current['value'], 'only allowed inside an "if" block');
            } else {
                $blocks['analyzer'][$cnt - 1]['token'] = grammar::T_IF_ELSE;
            }
        });
    }

    /**
     * Execute compiler toolchain for a template snippet.
     *
     * @param   string      $snippet        Template snippet to process.
     * @param   int         $line           Line in template processed.
     * @param   array       $blocks         Block information required by analyzer / compiler.
     * @param   string      $escape         Escaping to use.
     * @return  string                      Processed / compiled snippet.
     */
    protected function toolchain($snippet, $line, array &$blocks, $escape)
    {
        $code = '';

        if (($tokens = self::$parser->tokenize($snippet, $line, $this->filename)) === false) {
            $error = self::$parser->getLastError();

            $this->error($error['ifile'], $error['iline'], $error['line'], $error['token'], $error['payload']);
        } elseif (count($tokens) > 0) {
            if (self::$parser->analyze($tokens) === false) {
                $error = self::$parser->getLastError();

                $this->error($error['ifile'], $error['iline'], $error['line'], $error['token'], $error['payload']);
            } else {
                $tokens = array_reverse($tokens);
                $code   = implode('', $this->compile($tokens, $blocks, $escape));
            }
        }

        return $code;
    }

    /**
     * Parse template and extract all template functionality to compile.
     *
     * @param   \Octris\Tpl\Parser     $parser         Parser instance.
     * @param   string                      $tpl            Optional template string.
     * @return  string                                      Processed / compiled template.
     */
    protected function parse(\Octris\Tpl\Parser $parser)
    {
        $blocks = array('analyzer' => array(), 'compiler' => array());

        if (is_null(self::$parser)) {
            // initialize parser
            $this->setup($blocks);
        }

        foreach ($parser as $command) {
            $snippet = $this->toolchain($command['snippet'], $command['line'], $blocks, $command['escape']);

            $parser->replaceSnippet($snippet, true);
        }

        if (count($blocks['analyzer']) > 0) {
            // all block-commands in a template have to be closed
            $this->error(
                __FILE__,
                __LINE__,
                $parser->getTotalLines(),
                0,
                sprintf(
                    'missing %s for %s',
                    grammar::T_BLOCK_CLOSE,
                    implode(
                        ', ',
                        array_map(
                            function ($v) {
                                return $v['value'];
                            },
                            array_reverse($blocks['analyzer'])
                        )
                    )
                )
            );
        }

        $tpl = $parser->getTemplate();

        return $tpl;
    }

    /**
     * Process a template string.
     *
     * @param   string      $tpl            Template string to process.
     * @param   string      $escape         Escaping to use.
     * @return  string                      Compiled template.
     */
    public function processString($tpl, $escape)
    {
        $this->filename = null;

        if ($escape == \Octris\Tpl::ESC_HTML) {
            // parser for auto-escaping turned on
            $parser = \Octris\Tpl\Parser\Html::fromString($tpl);
        } else {
            if ($escape == \Octris\Tpl::ESC_AUTO) {
                $escape = \Octris\Tpl::ESC_NONE;
            }

            $parser = \Octris\Tpl\Parser::fromString($tpl);
            $parser->setFilter(function ($command) use ($escape) {
                $command['escape'] = $escape;

                return $command;
            });
        }

        return $this->parse($parser);
    }

    /**
     * Process a template file.
     *
     * @param   string      $filename       Name of template file to process.
     * @param   string      $escape         Escaping to use.
     * @return  string                      Compiled template.
     */
    public function process($filename, $escape)
    {
        $this->filename = $filename;

        if ($escape == \Octris\Tpl::ESC_AUTO) {
            // auto-escaping, try to determine escaping from file extension
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            if ($ext == 'html' || $ext == 'htm') {
                $escape = \Octris\Tpl::ESC_HTML;
            } elseif ($ext == 'css') {
                $escape = \Octris\Tpl::ESC_CSS;
            } elseif ($ext == 'js') {
                $escape = \Octris\Tpl::ESC_JS;
            } else {
                $escape = \Octris\Tpl::ESC_NONE;
            }
        }

        if ($escape == \Octris\Tpl::ESC_HTML) {
            // parser for auto-escaping turned on
            $parser = \Octris\Tpl\Parser\Html::fromFile($filename);
        } else {
            $parser = \Octris\Tpl\Parser::fromFile($filename);
            $parser->setFilter(function ($command) use ($escape) {
                $command['escape'] = $escape;

                return $command;
            });
        }

        return $this->parse($parser);
    }
}
