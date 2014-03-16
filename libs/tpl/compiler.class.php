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
    use \org\octris\core\tpl\compiler\grammar;
    
    /**
     * Implementation of template compiler.
     *
     * @octdoc      c:tpl/compiler
     * @copyright   copyright (c) 2010-2014 by Harald Lapp
     * @author      Harald Lapp <harald@octris.org>
     */
    class compiler
    /**/
    {
        /**
         * Instance of parser class.
         *
         * @octdoc  p:compiler/$parser
         * @type    \org\octris\core\parser|null
         */
        protected static $parser = null;
        /**/
        
        /**
         * Name of file currently compiled.
         *
         * @octdoc  p:compiler/$filename
         * @type    string
         */
        protected $filename = '';
        /**/

        /**
         * Stores pathes to look into when searching for template to load.
         *
         * @octdoc  p:compiler/$searchpath
         * @type    array
         */
        protected $searchpath = array();
        /**/

        /**
         * Instance of locale class.
         *
         * @octdoc  p:compiler/$l10n
         * @type    \org\octris\core\l10n
         */
        protected $l10n;
        /**/

        /**
         * Constructor.
         *
         * @octdoc  m:compiler/__construct
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
         * Register pathname for looking up templates in.
         *
         * @octdoc  m:compiler/addSearchPath
         * @param   mixed       $pathname       Name of path to register.
         */
        public function addSearchPath($pathname)
        /**/
        {
            if (is_array($pathname)) {
                foreach ($pathname as $path) $this->addSearchPath($path);
            } else {
                if (!in_array($pathname, $this->searchpath)) {
                    $this->searchpath[] = $pathname;
                }
            }
        }
        
        /**
         * Lookup a template file in the configured searchpathes.
         *
         * @octdoc  m:compiler/findFile
         * @param   string      $filename       Name of file to lookup.
         */
        public function findFile($filename)
        /**/
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
         * @octdoc  m:compiler/error
         * @param   string      $ifile      Internal filename the error occured in.
         * @param   int         $iline      Internal line number the error occured in.
         * @param   int         $line       Line in template the error was triggered for.
         * @param   mixed       $token      Token that triggered the error.
         * @param   mixed       $payload    Optional additional information. Either an array of expected token IDs or an additional message to output.
         */
        protected function error($ifile, $iline, $line, $token, $payload = NULL)
        /**/
        {
            if (php_sapi_name() != 'cli') {
                print "<pre>";

                $payload = htmlentities($payload, ENT_QUOTES);
            }
            
            printf("\n** ERROR: %s(%d) **\n", $ifile, $iline);
            printf("   line :    %d\n", $line);
            printf("   file :    %s\n", $this->filename);
            printf("   token:    %s\n", $token);
            
            if (is_array($payload)) {
                printf("   expected: %s\n", implode(', ', $payload));
            } elseif (isset($payload)) {
                printf("   message:  %s\n", $payload);
            }
         
            if (php_sapi_name() != 'cli') {
                print "</pre>";
            }

            die();
        }

        /**
         * Implementation of gettext compiler.
         *
         * @octdoc  m:compiler/gettext
         * @param   array       $args       Arguments for gettext.
         * @return  string                  Compiled code for gettext.
         */
        protected function gettext($args)
        /**/
        {
            if (preg_match('/^(["\'])(.*?)\1$/', $args[0], $match)) {
                $pattern = '/\[(?:(_\d+)|(?:([^,]+))(?:,(.*?))?(?<!\\\))\]/s';

                $chr = $match[1];                           // quotation character
                $txt = $this->l10n->lookup($match[2]);      // get translated text
                
                $txt = $chr . addcslashes($txt, ($chr == '"' ? '"' : "'")) . $chr;
                
                array_shift($args);
                
                if (count($args) > 0) {
                    $txt = preg_replace_callback($pattern, function($m) use ($args, $chr) {
                        $cmd = (isset($m[2]) ? $m[2] : '');
                        $tmp = preg_split('/(?<!\\\),/', array_pop($m));
                        $par = array();

                        foreach ($tmp as $t) {
                            $par[] = (($t = trim($t)) && preg_match('/^_(\d+)$/', $t, $m)
                                        ? $args[($m[1] - 1)]
                                        : '\'' . $t . '\'');
                        }

                        $code = ($cmd != '' 
                                 ? $chr . ' . $this->' . $cmd . '(' . implode(',', $par) . ') . ' . $chr
                                 : $chr . ' . ' . array_shift($par) . ' . ' . $chr);

                        return $code;
                    }, $txt, -1, $cnt = 0);
                }
                
                $return = $txt;
            } else {
                $return = '$this->_(' . implode('', $args) . ')';
            }
            
            return $return;
        }
        
        /**
         * Compile tokens to PHP code.
         *
         * @octdoc  m:compiler/compile
         * @param   array       $tokens     Array of tokens to compile.
         * @param   array       $blocks     Block information required by analyzer / compiler.
         * @param   string      $escape     Escaping to use.
         * @return  string                  Generated PHP code.
         */
        protected function compile(&$tokens, &$blocks, $escape)
        /**/
        {
            $stack = array();
            $code  = array();
            
            $last_tokens = array();
            
            $getNextToken = function(&$tokens) use (&$last_tokens) {
                if (($current = array_shift($tokens))) {
                    $last_tokens[] = $current['token'];
                }

                return $current;
            };
            $getLastToken = function($tokens, $idx) {
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
                    
                    list($_start, $_end) = compiler\rewrite::$value(array_reverse($code));

                    $code = array($_start);
                    $blocks['compiler'][] = $_end;
                
                    if (($err = compiler\rewrite::getError()) != '') {
                        $this->error(__FILE__, __LINE__, $line, $token, $err);
                    }
                    break;
                case grammar::T_IF_ELSE:
                    $code[] = '} else {';
                    break;
                case grammar::T_BLOCK_CLOSE:
                    $code[] = array_pop($blocks['compiler']);
                    break;
                case grammar::T_BRACE_CLOSE:
                    array_push($stack, $code);
                    $code = array();
                    break;
                case grammar::T_GETTEXT:
                    // gettext handling
                    $code = array($this->gettext(array_reverse($code)));
                    break;
                case grammar::T_ESCAPE:
                case grammar::T_LET:
                case grammar::T_METHOD:
                    // replace/rewrite method call
                    $value = strtolower($value);
                    $code  = array(compiler\rewrite::$value(array_reverse($code)));
                    
                    if (($err = compiler\rewrite::getError()) != '') {
                        $this->error(__FILE__, __LINE__, $line, $token, $err);
                    }
                    
                    if (($tmp = array_pop($stack))) $code = array_merge($tmp, $code);
                    break;
                case grammar::T_MACRO:
                    // resolve macro
                    $value = strtolower(substr($value, 1));
                    $file  = substr($code[0], 1, -1);
                    $code  = array(
                        compiler\macro::execMacro(
                            $value, 
                            array($file), 
                            array('compiler' => $this, 'escape' => $escape)
                        )
                    );

                    if (($err = compiler\macro::getError()) != '') {
                        $this->error(__FILE__, __LINE__, $line, $token, $err);
                    }
                    
                    $code[] = implode(', ', array_pop($stack));
                    break;
                case grammar::T_CONSTANT:
                    $value = strtoupper(substr($value, 1));
                    $tmp   = compiler\constant::getConstant($value);
                
                    if (($err = compiler\constant::getError()) != '') {
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
                    $code[] = $value;
                    break;
                case grammar::T_START:
                    /*
                     * NOTE: Regarding newlines behind PHP closing tag '?>'. this is because PHP 'eats' newslines
                     *       after PHP closing tag. For details refer to:
                     *      
                     *      http://shiflett.org/blog/2005/oct/php-stripping-newlines
                     */
                    $last_token = $getLastToken($last_tokens, -2);
                    
                    if ($last_token == grammar::T_LET) {
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
                    break;
                case grammar::T_PSEPARATOR:
                case grammar::T_BRACE_OPEN:
                case grammar::T_END:
                    // nothing to do for these tokens
                    break;
                default:
                    $this->error(__FILE__, __LINE__, $line, $token, 'unknown token');
                    break;
                }
            }
            
            return $code;
        }
        
        /**
         * Setup toolchain.
         *
         * @octdoc  m:compiler/setup
         * @param   array       $blocks         Block information required by analyzer / compiler.
         */
        protected function setup(array &$blocks)
        /**/
        {
            $grammar = new \org\octris\core\tpl\compiler\grammar();
            self::$parser = new \org\octris\core\parser($grammar, [grammar::T_WHITESPACE]);
            
            $grammar->addEvent(grammar::T_IF_OPEN, function($current) use (&$blocks) {
                $blocks['analyzer'][] = $current;
            });
            $grammar->addEvent(grammar::T_BLOCK_OPEN, function($current) use (&$blocks) {
                $blocks['analyzer'][] = $current;
            });
            $grammar->addEvent(grammar::T_BLOCK_CLOSE, function($current) use (&$blocks) {
                // closing block only allowed is a block is open
                if (!($block = array_pop($blocks['analyzer']))) {
                    $this->error(__FILE__, __LINE__, $line, $token, 'there is no open block');
                }
            });
            $grammar->addEvent(grammar::T_IF_ELSE, function($current) use (&$blocks) {
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
         * @octdoc  m:compiler/toolchain
         * @param   string      $snippet        Template snippet to process.
         * @param   int         $line           Line in template processed.
         * @param   array       $blocks         Block information required by analyzer / compiler.
         * @param   string      $escape         Escaping to use.
         * @return  string                      Processed / compiled snippet.
         */
        protected function toolchain($snippet, $line, array &$blocks, $escape)
        /**/
        {
            if (is_null(self::$parser)) {
                // initialize parser
                $this->setup($blocks);
            }

            $code   = '';

            if (($tokens = self::$parser->tokenize($snippet, $line)) === false) {
                $error = self::$parser->getLastError();

                $this->error($error['iline'], $error['iline'], $error['line'], $error['token'], $error['payload']);
            } elseif (count($tokens) > 0) {
                if (self::$parser->getGrammar()->analyze($tokens) !== false) {
                    $tokens = array_reverse($tokens);
                    $code   = implode('', $this->compile($tokens, $blocks, $escape));
                }
            }
            
            return $code;
        }
        
        /**
         * Parse template and extract all template functionality to compile.
         *
         * @octdoc  m:compiler/parse
         * @param   string      $escape         Escaping to use.
         * @return  string                      Processed / compiled template.
         */
        protected function parse($escape)
        /**/
        {
            $blocks = array('analyzer' => array(), 'compiler' => array());

            if ($escape == \org\octris\core\tpl::T_ESC_HTML) {
                // parser for auto-escaping turned on
                $parser = new \org\octris\core\tpl\parser\html($this->filename);
            } else {
                $parser = new \org\octris\core\tpl\parser($this->filename);
                $parser->setFilter(function($command) use ($escape) {
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
                $this->error(__FILE__, __LINE__, $parser->getTotalLines(), 0, sprintf('missing %s for %s',
                    $this->getTokenName(grammar::T_BLOCK_CLOSE),
                    implode(', ', array_map(function($v) {
                        return $v['value'];
                    }, array_reverse($blocks['analyzer'])))
                ));
            }

            $tpl = $parser->getTemplate();
            
            return $tpl;
        }
        
        /**
         * Process a template.
         *
         * @octdoc  m:compiler/process
         * @param   string      $filename       Name of template file to process.
         * @param   string      $escape         Escaping to use.
         * @return  string                      Compiled template.
         */
        public function process($filename, $escape)
        /**/
        {
            $this->filename = $filename;

            if ($escape == \org\octris\core\tpl::T_ESC_AUTO) {
                // auto-escaping, try to determine escaping from file extension
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

                if ($ext == 'html' || $ext == 'htm') {
                    $escape = \org\octris\core\tpl::T_ESC_HTML;
                } elseif ($ext == 'css') {
                    $escape = \org\octris\core\tpl::T_ESC_CSS;
                } elseif ($ext == 'js') {
                    $escape = \org\octris\core\tpl::T_ESC_JS;
                } else {
                    $escape = \org\octris\core\tpl::T_ESC_NONE;
                }
            }

            return $this->parse($escape);
        }
    }
}
