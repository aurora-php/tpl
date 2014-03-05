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
         * Known parser tokens.
         *
         * @octdoc  d:compiler/T_...
         * @type    string
         */
        const T_START           = '<start>';
        const T_TYPE            = '<type>';
        const T_PARAMETER       = '<parameter>';
        
        const T_BLOCK_OPEN      = '<block-open>';
        const T_BLOCK_CLOSE     = '<block-close>';
        const T_IF_OPEN         = '<if-open>';
        const T_IF_ELSE         = '<if-else>';
        const T_BRACE_OPEN      = '(';
        const T_BRACE_CLOSE     = ')';
        const T_PSEPARATOR      = ',';
    
        const T_METHOD          = '<method>';
        const T_LET             = '<let>';
        const T_VARIABLE        = '<variable>';
        const T_CONSTANT        = '<constant>';
        const T_MACRO           = '<macro>';
        const T_GETTEXT         = '<gettext>';
        const T_ESCAPE          = '<escape>';
    
        const T_STRING          = '<string>';
        const T_NUMBER          = '<number>';
        const T_BOOL            = '<bool>';
        const T_NULL            = '<null>';
        
        const T_WHITESPACE      = '<whitespace>';
        /**/
                
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
         * @param   string      $type       Type of error to trigger.
         * @param   int         $cline      Line in compiler class error was triggered from.
         * @param   int         $line       Line in template the error was triggered for.
         * @param   int         $token      ID of token that triggered the error.
         * @param   mixed       $payload    Optional additional information. Either an array of expected token IDs or an additional message to output.
         */
        protected function error($type, $cline, $line, $token, $payload = NULL)
        /**/
        {
            if (php_sapi_name() != 'cli') {
                print "<pre>";

                $payload = htmlentities($payload, ENT_QUOTES);
            }
            
            printf("\n** ERROR: %s(%d) **\n", $type, $cline);
            printf("   line :    %d\n", $line);
            printf("   file :    %s\n", $this->filename);
            printf("   token:    %s\n", $this->getTokenName($token));
            
            if (is_array($payload)) {
                printf("   expected: %s\n", implode(', ', $this->getTokenNames(array_keys($payload))));
            } elseif (isset($payload)) {
                printf("   message:  %s\n", $payload);
            }
         
            if (php_sapi_name() != 'cli') {
                print "</pre>";
            }

            die();
        }

        /**
         * Token analyzer. The analyzer applies rulesets to tokens and checks if
         * the rules are fulfilled.
         *
         * @octdoc  m:compiler/analyze
         * @param   array       $tokens     Tokens to analyze.
         * @param   array       $blocks     Block information required by analyzer / compiler.
         * @return  bool                    Returns true if token analysis succeeded.
         */
        protected function analyze(array $tokens, array &$blocks)
        /**/
        {
            $braces  = 0;               // brace level
            $current = null;            // current token
            
            $rule    = self::$rules;
            $stack   = array();
            
            /*
             * retrieve next rule
             */
            $get_next_rule = function($rule, $token) use (&$stack) {
                $return = false;
                
                if (is_array($rule) && array_key_exists($token, $rule)) {
                    // valid token, because it's in current ruleset
                    if (is_array($rule[$token])) {
                        // push current rule on stack and get child rule
                        $stack[] = $rule;
                        $return  = $rule[$token];
                    } elseif (is_null($rule[$token])) {
                        // ruleset is null -> try to get it from parent rules
                        while (($return = array_pop($stack)) && !isset($return[$token]));

                        if (is_array($return)) {
                            $stack[] = $return;
                            $return  = $return[$token];
                        }
                    }
                }

                return $return;
            };
            
            foreach ($tokens as $current) {
                extract($current);
                
                switch ($token) {
                case self::T_IF_OPEN:
                    // opening if
                    
                    /** FALL THRU **/
                case self::T_BLOCK_OPEN:
                    // opening block
                    $blocks['analyzer'][] = $current;
                    break;
                case self::T_BLOCK_CLOSE:
                    // closing block only allowed is a block is open
                    if (!($block = array_pop($blocks['analyzer']))) {
                        $this->error(__FUNCTION__, __LINE__, $line, $token, 'there is no open block');
                    }
                    break;
                case self::T_IF_ELSE:
                    // else is only allowed within an 'if' block
                    if ((($cnt = count($blocks['analyzer'])) > 0 && $blocks['analyzer'][$cnt - 1]['token'] != self::T_IF_OPEN)) {
                        $this->error(__FUNCTION__, __LINE__, $line, $token, 'only allowed inside an "if" block');
                    } else {
                        $blocks['analyzer'][$cnt - 1]['token'] = self::T_IF_ELSE;
                    }
                    break;
                }

                if (!($tmp = $get_next_rule($rule, $token))) {
                    $this->error(__FUNCTION__, __LINE__, $line, $token, $rule);
                }
                
                $rule = $tmp;
            }

            return true;
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
                case self::T_IF_OPEN:
                case self::T_BLOCK_OPEN:
                    // replace/rewrite block call
                    $value = strtolower($value);
                    
                    list($_start, $_end) = compiler\rewrite::$value(array_reverse($code));

                    $code = array($_start);
                    $blocks['compiler'][] = $_end;
                
                    if (($err = compiler\rewrite::getError()) != '') {
                        $this->error(__FUNCTION__, __LINE__, $line, $token, $err);
                    }
                    break;
                case self::T_IF_ELSE:
                    $code[] = '} else {';
                    break;
                case self::T_BLOCK_CLOSE:
                    $code[] = array_pop($blocks['compiler']);
                    break;
                case self::T_BRACE_CLOSE:
                    array_push($stack, $code);
                    $code = array();
                    break;
                case self::T_GETTEXT:
                    // gettext handling
                    $code = array($this->gettext(array_reverse($code)));
                    break;
                case self::T_ESCAPE:
                case self::T_LET:
                case self::T_METHOD:
                    // replace/rewrite method call
                    $value = strtolower($value);
                    $code  = array(compiler\rewrite::$value(array_reverse($code)));
                    
                    if (($err = compiler\rewrite::getError()) != '') {
                        $this->error(__FUNCTION__, __LINE__, $line, $token, $err);
                    }
                    
                    if (($tmp = array_pop($stack))) $code = array_merge($tmp, $code);
                    break;
                case self::T_MACRO:
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
                        $this->error(__FUNCTION__, __LINE__, $line, $token, $err);
                    }
                    
                    $code[] = implode(', ', array_pop($stack));
                    break;
                case self::T_CONSTANT:
                    $value = strtoupper(substr($value, 1));
                    $tmp   = compiler\constant::getConstant($value);
                
                    if (($err = compiler\constant::getError()) != '') {
                        $this->error(__FUNCTION__, __LINE__, $line, $token, $err);
                    }
                
                    $code[] = (is_string($tmp) ? '"' . $tmp . '"' : (int)$tmp);
                    break;
                case self::T_VARIABLE:
                    $tmp = sprintf(
                        '$this->data["%s"]', 
                        implode('"]["', explode(':', strtolower(substr($value, 1))))
                    );
                    
                    // $code[] = sprintf('(is_callable(%1$s) ? %1$s() : %1$s)', $tmp);
                    $code[] = $tmp;
                    break;
                case self::T_BOOL:
                case self::T_STRING:
                case self::T_NUMBER:
                    $code[] = $value;
                    break;
                case self::T_START:
                    /*
                     * NOTE: Regarding newlines behind PHP closing tag '?>'. this is because PHP 'eats' newslines
                     *       after PHP closing tag. For details refer to:
                     *      
                     *      http://shiflett.org/blog/2005/oct/php-stripping-newlines
                     */
                    $last_token = $getLastToken($last_tokens, -2);
                    
                    if ($last_token == self::T_LET) {
                        $code = array('<?php ' . implode('', $code) . '; ?>'."\n");
                    } elseif (in_array($last_token, array(self::T_CONSTANT, self::T_MACRO))) {
                        $code = array(implode('', $code));
                    } elseif (!in_array($last_token, array(self::T_BLOCK_OPEN, self::T_BLOCK_CLOSE, self::T_IF_OPEN, self::T_IF_ELSE))) {
                        if ($last_token == self::T_ESCAPE) {
                            // no additional escaping, when 'escape' method was used
                            $code = array('<?php $this->write(' . implode('', $code) . '); ?>'."\n");
                        } else {
                            $code = array('<?php $this->write(' . implode('', $code) . ', "' . $escape . '"); ?>'."\n");
                        }
                    } else {
                        $code = array('<?php ' . implode('', $code) . ' ?>'."\n");
                    }
                    break;
                case self::T_PSEPARATOR:
                case self::T_BRACE_OPEN:
                case self::T_END:
                    // nothing to do for these tokens
                    break;
                default:
                    $this->error(__FUNCTION__, __LINE__, $line, $token, 'unknown token');
                    break;
                }
            }
            
            return $code;
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
                $grammar = new \org\octris\core\tpl\compiler\grammar();
                self::$parser = new \org\octris\core\parser($grammar, [self::T_WHITESPACE]);
                
                $grammar->addEvent(self::T_IF_OPEN, function($current) use (&$blocks) {
                    $blocks['analyzer'][] = $current;
                });
                $grammar->addEvent(self::T_IF_OPEN, function($current) use (&$blocks) {
                    $blocks['analyzer'][] = $current;
                });
                $grammar->addEvent(self::T_BLOCK_CLOSE, function($current) use (&$blocks) {
                    // closing block only allowed is a block is open
                    if (!($block = array_pop($blocks['analyzer']))) {
                        $this->error(__FUNCTION__, __LINE__, $line, $token, 'there is no open block');
                    }
                });
                $grammar->addEvent(self::T_IF_ELSE, function($current) use (&$blocks) {
                    if ((($cnt = count($blocks['analyzer'])) > 0 && $blocks['analyzer'][$cnt - 1]['token'] != self::T_IF_OPEN)) {
                        $this->error(__FUNCTION__, __LINE__, $line, $token, 'only allowed inside an "if" block');
                    } else {
                        $blocks['analyzer'][$cnt - 1]['token'] = self::T_IF_ELSE;
                    }
                });
            }

            $tokens = self::$parser->tokenize($snippet, $line);
            $code   = '';

            if (count($tokens) > 0) {
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
                $this->error(__FUNCTION__, __LINE__, $parser->getTotalLines(), 0, sprintf('missing %s for %s',
                    $this->getTokenName(self::T_BLOCK_CLOSE),
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
