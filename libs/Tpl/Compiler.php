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

use \Octris\Core\Tpl\Compiler\Grammar;

/**
 * Implementation of template compiler.
 *
 * @copyright   copyright (c) 2010-2014 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
class Compiler
{
    /**
     * Instance of parser class.
     *
     * @type    \octris\core\parser|null
     */
    protected static $parser = null;
    
    /**
     * File handle for error messages output.
     *
     * @type    resource
     */
    protected $errout = null;
    
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
     * Instance of locale class.
     *
     * @type    \octris\core\l10n
     */
    protected $l10n;
    
    /**
     * Constructor.
     *
     */
    public function __construct()
    {
        $this->errout = fopen('php://output', 'w');
    }

    /**
     * Set location for error output.
     *
     * @param   string|resource             $errout     Location for error output.
     */
    public function setErrorOutput($errout)
    {
        if (!is_resource($errout)) {
            throw new \Exception('Provided argument is not a resource "' . $errout . '"');
        }

        $this->errout = $errout;
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
        if (($pre = (php_sapi_name() != 'cli' && stream_get_meta_data($this->errout)['uri'] == 'php://output'))) {
            fputs($this->errout, "<pre>");

            $prepare = function ($str) {
                return htmlentities($str, ENT_QUOTES);
            };
        } else {
            $prepare = function ($str) {
                return $str;
            };
        }

        fputs($this->errout, sprintf("\n** ERROR: %s(%d) **\n", $ifile, $iline));
        fputs($this->errout, sprintf("   line :    %d\n", $line));
        fputs($this->errout, sprintf("   file :    %s\n", $prepare($this->filename)));
        fputs($this->errout, sprintf("   token:    %s\n", $prepare(self::$parser->getTokenName($token))));

        if (is_array($payload)) {
            fputs($this->errout, sprintf("   expected: %s\n", implode(', ', array_map(function ($token) use ($prepare) {
                return $prepare(self::$parser->getTokenName($token));
            }, $payload))));
        } elseif (isset($payload)) {
            fputs($this->errout, sprintf("   message:  %s\n", $prepare($payload)));
        }

        if ($pre) {
            fputs($this->errout, "</pre>");
        }

        throw new \Exception('Compiler error');
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
                case grammar::T_BLOCK_OPEN:
                    // replace/rewrite block call
                    $value = strtolower($value);

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
                case grammar::T_DGETTEXT:
                case grammar::T_GETTEXT:
                    $code    = array_reverse($code);
                    $_domain = ($token == grammar::T_DGETTEXT
                                ? array_shift($code)
                                : 'null');
                    $_msg    = array_shift($code);

                    $code = array(Compiler\Rewrite::gettext($this->l10n, $_domain, $_msg, $code));

                    if (($err = Compiler\Rewrite::getError()) != '') {
                        $this->error(__FILE__, __LINE__, $line, $token, $err);
                    }

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
                    $file  = substr($code[0], 1, -1);
                    $code  = array(
                        Compiler\Macro::execMacro(
                            $value,
                            array($file),
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
        $grammar = new \Octris\Core\Tpl\Compiler\Grammar();
        self::$parser = new \Octris\Core\Parser($grammar, [grammar::T_WHITESPACE]);

        $grammar->addEvent(grammar::T_IF_OPEN, function ($current) use (&$blocks) {
            $blocks['analyzer'][] = $current;
        });
        $grammar->addEvent(grammar::T_BLOCK_OPEN, function ($current) use (&$blocks) {
            $blocks['analyzer'][] = $current;
        });
        $grammar->addEvent(grammar::T_BLOCK_CLOSE, function ($current) use (&$blocks) {
            // closing block only allowed is a block is open
            if (!(array_pop($blocks['analyzer']))) {
                $this->error(__FILE__, __LINE__, $line, $token, 'there is no open block');
            }
        });
        $grammar->addEvent(grammar::T_IF_ELSE, function ($current) use (&$blocks) {
            if ((($cnt = count($blocks['analyzer'])) > 0 && $blocks['analyzer'][$cnt - 1]['token'] != grammar::T_IF_OPEN)) {
                $this->error(__FILE__, __LINE__, $line, $token, 'only allowed inside an "if" block');
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
        if (is_null(self::$parser)) {
            // initialize parser
            $this->setup($blocks);
        }

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
     * @param   string      $escape         Escaping to use.
     * @return  string                      Processed / compiled template.
     */
    protected function parse($escape)
    {
        $blocks = array('analyzer' => array(), 'compiler' => array());

        if ($escape == \Octris\Core\Tpl::T_ESC_HTML) {
            // parser for auto-escaping turned on
            $parser = new \Octris\Core\Tpl\Parser\Html($this->filename);
        } else {
            $parser = new \Octris\Core\Tpl\Parser($this->filename);
            $parser->setFilter(function ($command) use ($escape) {
                $command['escape'] = $escape;

                return $command;
            });
        }

        foreach ($parser as $command) {
            $snippet = $this->toolchain($command['snippet'], $command['line'], $blocks, $command['escape']);

            $parser->replaceSnippet($snippet);
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
     * Process a template.
     *
     * @param   string      $filename       Name of template file to process.
     * @param   string      $escape         Escaping to use.
     * @return  string                      Compiled template.
     */
    public function process($filename, $escape)
    {
        $this->filename = $filename;

        if ($escape == \Octris\Core\Tpl::T_ESC_AUTO) {
            // auto-escaping, try to determine escaping from file extension
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            if ($ext == 'html' || $ext == 'htm') {
                $escape = \Octris\Core\Tpl::T_ESC_HTML;
            } elseif ($ext == 'css') {
                $escape = \Octris\Core\Tpl::T_ESC_CSS;
            } elseif ($ext == 'js') {
                $escape = \Octris\Core\Tpl::T_ESC_JS;
            } else {
                $escape = \Octris\Core\Tpl::T_ESC_NONE;
            }
        }

        return $this->parse($escape);
    }
}
