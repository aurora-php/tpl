<?php

namespace org\octris\core\tpl {
    require_once('compiler/rewrite.class.php');
    require_once('compiler/macro.class.php');

    use \org\octris\core\tpl\compiler as compiler;
    
    /****c* tpl/compiler
     * NAME
     *      compiler
     * FUNCTION
     *      template compiler
     * COPYRIGHT
     *      copyright (c) 2010 by Harald Lapp
     * AUTHOR
     *      Harald Lapp <harald@octris.org>
     ****
     */

    class compiler {
        /****d* compiler/T_...
         * SYNOPSIS
         */
        const T_START           = 1;
        const T_END             = 2;
        const T_BLOCK_OPEN      = 3;
        const T_BLOCK_CLOSE     = 4;
        const T_IF_OPEN         = 5;
        const T_IF_ELSE         = 6;
    
        const T_BRACE_OPEN      = 10;
        const T_BRACE_CLOSE     = 11;
        const T_PSEPARATOR      = 12;
    
        const T_METHOD          = 20;
        const T_VARIABLE        = 22;
        const T_CONSTANT        = 23;
        const T_MACRO           = 24;
        const T_KEYWORD         = 25;
    
        const T_STRING          = 30;
        const T_NUMBER          = 31;
        const T_BOOL            = 32;
        
        const T_WHITESPACE      = 40;
        const T_NEWLINE         = 41;
        /*
         * FUNCTION
         *      tokens
         ****
         */

        /****v* compiler/$tokens
         * SYNOPSIS
         */
        private static $tokens = array(
            self::T_IF_OPEN     => '#if',
            self::T_IF_ELSE     => '#else',
            
            self::T_BLOCK_CLOSE => '#end',
            self::T_BLOCK_OPEN  => '#[a-z][a-z-0-9_]*',
            
            self::T_BRACE_OPEN  => '\(',
            self::T_BRACE_CLOSE => '\)',
            self::T_PSEPARATOR  => '\,',

            self::T_METHOD      => '[a-z_][a-z0-9_]*',
            self::T_VARIABLE    => '\$[a-z_][a-z0-9_]*(:\$?[a-z_][a-z0-9_]*|)+',
            self::T_CONSTANT    => "%[_a-z][_a-z0-9]*",
            self::T_MACRO       => "@[_a-z][_a-z0-9]*",
        
            self::T_STRING      => "([\"']).*?(?!\\\\)\\2",
            self::T_NUMBER      => '[+-]?[0-9]+(\.[0-9]+|)',
            self::T_BOOL        => '(true|false)',
            
            self::T_WHITESPACE  => '\s+',
            self::T_NEWLINE     => '\n+',
        );
        /*
         * FUNCTION
         *      token patterns for tokenizer
         ****
         */

        /****v* compiler/$rules
         * SYNOPSIS
         */
        private static $rules = array(
            self::T_START   => array(
                self::T_END     => true,
            
                /* T_BLOCK_OPEN */
                self::T_BLOCK_OPEN  => array(
                    self::T_BRACE_OPEN  => array(
                        self::T_METHOD      => NULL, 
                        self::T_VARIABLE    => array(
                            self::T_PSEPARATOR  => array(
                                self::T_METHOD      => NULL,
                                self::T_VARIABLE    => NULL,
                                self::T_CONSTANT    => NULL, 
                                self::T_STRING      => NULL, 
                                self::T_NUMBER      => NULL,
                                self::T_BOOL        => NULL,
                            ), 
                            self::T_BRACE_CLOSE => NULL
                        ), 
                        self::T_CONSTANT    => array(
                            self::T_PSEPARATOR  => array(
                                self::T_METHOD      => NULL,
                                self::T_VARIABLE    => NULL,
                                self::T_CONSTANT    => NULL, 
                                self::T_STRING      => NULL, 
                                self::T_NUMBER      => NULL,
                                self::T_BOOL        => NULL,
                            ), 
                            self::T_BRACE_CLOSE => NULL
                        ),
                        self::T_STRING      => array(
                            self::T_PSEPARATOR  => array(
                                self::T_METHOD      => NULL,
                                self::T_VARIABLE    => NULL,
                                self::T_CONSTANT    => NULL, 
                                self::T_STRING      => NULL, 
                                self::T_NUMBER      => NULL,
                                self::T_BOOL        => NULL,
                            ), 
                            self::T_BRACE_CLOSE => NULL
                        ),
                        self::T_NUMBER      => array(
                            self::T_PSEPARATOR  => array(
                                self::T_METHOD      => NULL,
                                self::T_VARIABLE    => NULL,
                                self::T_CONSTANT    => NULL, 
                                self::T_STRING      => NULL, 
                                self::T_NUMBER      => NULL,
                                self::T_BOOL        => NULL,
                            ), 
                            self::T_BRACE_CLOSE => NULL
                        ),
                        self::T_BOOL        => array(
                            self::T_PSEPARATOR  => array(
                                self::T_METHOD      => NULL,
                                self::T_VARIABLE    => NULL,
                                self::T_CONSTANT    => NULL, 
                                self::T_STRING      => NULL, 
                                self::T_NUMBER      => NULL,
                                self::T_BOOL        => NULL,
                            ), 
                            self::T_BRACE_CLOSE => NULL
                        ),
                        self::T_BRACE_CLOSE => array(
                            self::T_BRACE_CLOSE => NULL, 
                            self::T_PSEPARATOR  => NULL, 
                            self::T_END         => NULL
                        )
                    )
                ),

                /* T_IF_OPEN */
                self::T_IF_OPEN  => array(
                    self::T_BRACE_OPEN  => array(
                        self::T_METHOD      => NULL, 
                        self::T_VARIABLE    => array(
                            self::T_PSEPARATOR  => array(
                                self::T_METHOD      => NULL,
                                self::T_VARIABLE    => NULL,
                                self::T_CONSTANT    => NULL, 
                                self::T_STRING      => NULL, 
                                self::T_NUMBER      => NULL,
                                self::T_BOOL        => NULL,
                            ), 
                            self::T_BRACE_CLOSE => NULL
                        ), 
                        self::T_CONSTANT    => array(
                            self::T_PSEPARATOR  => array(
                                self::T_METHOD      => NULL,
                                self::T_VARIABLE    => NULL,
                                self::T_CONSTANT    => NULL, 
                                self::T_STRING      => NULL, 
                                self::T_NUMBER      => NULL,
                                self::T_BOOL        => NULL,
                            ), 
                            self::T_BRACE_CLOSE => NULL
                        ),
                        self::T_STRING      => array(
                            self::T_PSEPARATOR  => array(
                                self::T_METHOD      => NULL,
                                self::T_VARIABLE    => NULL,
                                self::T_CONSTANT    => NULL, 
                                self::T_STRING      => NULL, 
                                self::T_NUMBER      => NULL,
                                self::T_BOOL        => NULL,
                            ), 
                            self::T_BRACE_CLOSE => NULL
                        ),
                        self::T_NUMBER      => array(
                            self::T_PSEPARATOR  => array(
                                self::T_METHOD      => NULL,
                                self::T_VARIABLE    => NULL,
                                self::T_CONSTANT    => NULL, 
                                self::T_STRING      => NULL, 
                                self::T_NUMBER      => NULL,
                                self::T_BOOL        => NULL,
                            ), 
                            self::T_BRACE_CLOSE => NULL
                        ),
                        self::T_BOOL        => array(
                            self::T_PSEPARATOR  => array(
                                self::T_METHOD      => NULL,
                                self::T_VARIABLE    => NULL,
                                self::T_CONSTANT    => NULL, 
                                self::T_STRING      => NULL, 
                                self::T_NUMBER      => NULL,
                                self::T_BOOL        => NULL,
                            ), 
                            self::T_BRACE_CLOSE => NULL
                        ),
                        self::T_BRACE_CLOSE => array(
                            self::T_BRACE_CLOSE => NULL, 
                            self::T_PSEPARATOR  => NULL, 
                            self::T_END         => NULL
                        )
                    )
                ),
            
                // T_BLOCK_CLOSE, T_IF_ELSE, T_VARIABLE, T_CONSTANT, T_STRING, T_NUMBER, T_BOOL
                self::T_BLOCK_CLOSE => array(self::T_END => NULL),
                self::T_IF_ELSE     => array(self::T_END => NULL),
                self::T_VARIABLE    => array(self::T_END => NULL),
                self::T_CONSTANT    => array(self::T_END => NULL),
            
                // method : method(... [, ...])
                self::T_METHOD  => array(
                    self::T_BRACE_OPEN  => array(
                        self::T_METHOD      => NULL, 
                        self::T_VARIABLE    => array(
                            self::T_PSEPARATOR  => array(
                                self::T_METHOD      => NULL,
                                self::T_VARIABLE    => NULL,
                                self::T_CONSTANT    => NULL, 
                                self::T_STRING      => NULL, 
                                self::T_NUMBER      => NULL,
                                self::T_BOOL        => NULL,
                            ), 
                            self::T_BRACE_CLOSE => NULL
                        ), 
                        self::T_CONSTANT    => array(
                            self::T_PSEPARATOR  => array(
                                self::T_METHOD      => NULL,
                                self::T_VARIABLE    => NULL,
                                self::T_CONSTANT    => NULL, 
                                self::T_STRING      => NULL, 
                                self::T_NUMBER      => NULL,
                                self::T_BOOL        => NULL,
                            ), 
                            self::T_BRACE_CLOSE => NULL
                        ),
                        self::T_STRING      => array(
                            self::T_PSEPARATOR  => array(
                                self::T_METHOD      => NULL,
                                self::T_VARIABLE    => NULL,
                                self::T_CONSTANT    => NULL, 
                                self::T_STRING      => NULL, 
                                self::T_NUMBER      => NULL,
                                self::T_BOOL        => NULL,
                            ), 
                            self::T_BRACE_CLOSE => NULL
                        ),
                        self::T_NUMBER      => array(
                            self::T_PSEPARATOR  => array(
                                self::T_METHOD      => NULL,
                                self::T_VARIABLE    => NULL,
                                self::T_CONSTANT    => NULL, 
                                self::T_STRING      => NULL, 
                                self::T_NUMBER      => NULL,
                                self::T_BOOL        => NULL,
                            ), 
                            self::T_BRACE_CLOSE => NULL
                        ),
                        self::T_BOOL        => array(
                            self::T_PSEPARATOR  => array(
                                self::T_METHOD      => NULL,
                                self::T_VARIABLE    => NULL,
                                self::T_CONSTANT    => NULL, 
                                self::T_STRING      => NULL, 
                                self::T_NUMBER      => NULL,
                                self::T_BOOL        => NULL,
                            ), 
                            self::T_BRACE_CLOSE => NULL
                        ),
                        self::T_BRACE_CLOSE => array(
                            self::T_BRACE_CLOSE => NULL, 
                            self::T_PSEPARATOR  => array(
                                self::T_METHOD      => NULL,
                                self::T_VARIABLE    => NULL,
                                self::T_CONSTANT    => NULL, 
                                self::T_STRING      => NULL, 
                                self::T_NUMBER      => NULL,
                                self::T_BOOL        => NULL,
                            ), 
                            self::T_END         => NULL
                        )
                    )
                ),
        
                // macro : @macro(... [, ...])
                self::T_MACRO   => array(
                    self::T_BRACE_OPEN  => array(
                        self::T_CONSTANT    => array(
                            self::T_PSEPARATOR  => array(
                                self::T_CONSTANT => NULL, 
                                self::T_STRING   => NULL, 
                                self::T_NUMBER   => NULL, 
                                self::T_BOOL     => NULL
                            ), 
                            self::T_BRACE_CLOSE => NULL
                        ), 
                        self::T_STRING      => array(
                            self::T_PSEPARATOR  => array(
                                self::T_CONSTANT => NULL, 
                                self::T_STRING   => NULL, 
                                self::T_NUMBER   => NULL, 
                                self::T_BOOL     => NULL
                            ), 
                            self::T_BRACE_CLOSE => NULL
                        ), 
                        self::T_NUMBER      => array(
                            self::T_PSEPARATOR  => array(
                                self::T_CONSTANT => NULL, 
                                self::T_STRING   => NULL, 
                                self::T_NUMBER   => NULL, 
                                self::T_BOOL     => NULL
                            ), 
                            self::T_BRACE_CLOSE => NULL
                        ), 
                        self::T_BOOL        => array(
                            self::T_PSEPARATOR  => array(
                                self::T_CONSTANT => NULL, 
                                self::T_STRING   => NULL, 
                                self::T_NUMBER   => NULL, 
                                self::T_BOOL     => NULL
                            ), 
                            self::T_BRACE_CLOSE => NULL
                        ), 
                        self::T_BRACE_CLOSE => array(
                            self::T_END => NULL
                        )
                    )
                )
            )
        );
        /*
         * FUNCTION
         *      analyzer rules
         ****
         */
        
        /****v* compiler/$tokennames
         * SYNOPSIS
         */
        private static $tokennames = NULL;
        /*
         * FUNCTION
         *      names of tokens to be filled by constructor
         ****
         */
        
        /****v* compiler/$filename
         * SYNOPSIS
         */
        protected $filename = '';
        /*
         * FUNCTION
         *      name of file currently compiled
         ****
         */
        
        /****v* compiler/$last_tokens
         * SYNOPSIS
         */
        protected $last_tokens = array();
        /*
         * FUNCTION
         *      stores compiled tokens
         ****
         */
        
        /****v* compiler/$blocks
         * SYNOPSIS
         */
        protected $data;
        /*
         * FUNCTION
         *      common storage for data needed during compile time
         ****
         */
        
        /****m* compiler/__construct
         * SYNOPSIS
         */
        public function __construct()
        /*
         * FUNCTION
         *      constructor
         ****
         */
        {
            if (is_null(self::$tokennames)) {
                $class = new \ReflectionClass($this);
                self::$tokennames = array_flip($class->getConstants());
            }
        }
        
        /****m* compiler/getConstant
         * SYNOPSIS
         */
        protected function getConstant($name)
        /*
         * FUNCTION
         *      lookup value of a template constant
         * INPUTS
         *      * $name (string) -- name of template constant to lookup
         * OUTPUTS
         *      (string) -- template constant
         ****
         */
        {
            // TODO
            return $name;
        }
        
        /****m* compiler/getTokenName
         * SYNOPSIS
         */
        protected function getTokenName($token)
        /*
         * FUNCTION
         *      return name of token
         * INPUTS
         *      * $token (int) -- ID of token
         * OUTPUTS
         *      (string) -- name of token
         ****
         */
        {
            return (isset(self::$tokennames[$token])
                    ? self::$tokennames[$token]
                    : 'T_UNKNOWN');
        }
        
        /****m* compiler/getTokenName
         * SYNOPSIS
         */
        protected function getTokenNames(array $tokens)
        /*
         * FUNCTION
         *      return names for tokens
         * INPUTS
         *      * $tokens (array) -- array of tokens
         * OUTPUTS
         *      (string) -- name of token
         ****
         */
        {
            $return = array();
            
            foreach ($tokens as $token) $return[] = $this->getTokenName($token);
            
            return $return;
        }
        
        /****m* compiler/error
         * SYNOPSIS
         */
        protected function error($type, $cline, $line, $token, $payload = NULL)
        /*
         * FUNCTION
         *      trigger an error
         * INPUTS
         *      * $type (string) -- type of error to trigger
         *      * $cline (int) -- error occurred in this line of compiler class
         *      * $line (int) -- error occurred in this line of the template
         *      * $token (int) -- ID of token, that triggered the error
         *      * $payload (mixed) -- (optional) additional information -- either an array of expected token IDs, or an additional 
         *        message
         ****
         */
        {
            printf("\n** ERROR: %s(%d) **\n", $type, $cline);
            printf("   line :    %d\n", $line);
            printf("   file:     %s\n", $this->filename);
            printf("   token:    %s\n", $this->getTokenName($token));
            
            if (is_array($payload)) {
                printf("   expected: %s\n", implode(', ', $this->getTokenNames(array_keys($payload))));
            } elseif (isset($payload)) {
                printf("   message:  %s\n", $payload);
            }
            
            die();
        }
        
        /****m* compiler/tokenize
         * SYNOPSIS
         */
        protected function tokenize($in, $line)
        /*
         * FUNCTION
         *      tokenizer converts template snippet to tokens
         * INPUTS
         *      * $in (string) -- template snippet to tokenize
         *      * $line (int) -- line number of snippet in template 
         * OUTPUTS
         *      (array) -- tokens
         ****
         */
        {
            $out = array();
            $in  = stripslashes($in);

            while (strlen($in) > 0) {
                foreach (self::$tokens as $token => $regexp) {
                    if (preg_match('/^(' . $regexp . ')/i', $in, $m)) {
                        if ($token != self::T_WHITESPACE) {
                            // spaces between tokens are ignored
                            $out[] = array( 'token' => $token,
                                            'value' => $m[1],
                                            'file'  => $this->filename,
                                            'line'  => $line);
                        }

                        $in = substr($in, strlen($m[1]));
                        break;
                    }
                }
            }

            if (count($out) > 0) {
                array_unshift($out, array(
                    'token' => self::T_START,
                    'value' => '',
                    'file'  => $this->filename,
                    'line'  => $line
                ));
                array_push($out, array(
                    'token' => self::T_END,
                    'value' => '',
                    'file'  => $this->filename,
                    'line'  => $line
                ));
            }

            return $out;
        }

        /****m* compiler/analyze
         * SYNOPSIS
         */
        protected function analyze(array $tokens)
        /*
         * FUNCTION
         *      token analyzer -- applies rulesets to tokens and check if the
         *      rules are fulfilled
         * INPUTS
         *      * $tokens (array) -- tokens to analyz
         * OUTPUTS
         *      (array) -- errors
         ****
         */
        {
            $valid   = true;            // code is valid
            $braces  = 0;               // brace level
            $current = null;            // current token
            
            $rule    = self::$rules;
            $stack   = array();
            
            /*
             * retrieve next rule
             */
            $get_next_rule = function($rule, $token) use (&$stack) {
                $return = false;
                
                if (array_key_exists($token, $rule)) {
                    // valid token, because it's in current ruleset
                    if (is_array($rule[$token])) {
                        // push rule on stack and get child rule
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
                case self::T_BRACE_OPEN:
                    // opening '(' brace
                    ++$braces;
                    break;
                case self::T_BRACE_CLOSE:
                    // closing ')' brace -- only allowed, if a brace was opened previously
                    if ($braces == 0) {
                        $this->error(__FUNCTION__, __LINE__, $line, $token);
                    } else {
                        --$braces;
                    }
                    break;
                case self::T_PSEPARATOR:
                    // ',' is only allowed to separate arguments
                    if ($braces == 0) $this->error(__FUNCTION__, __LINE__, $line, $token);
                    break;
                case self::T_IF_OPEN:
                    // opening if
                    
                    /** FALL THRU **/
                case self::T_BLOCK_OPEN:
                    // opening block
                    $this->blocks[] = $current;
                    break;
                case self::T_BLOCK_CLOSE:
                    // closing block only allowed is a block is open
                    if (!($block = array_pop($this->blocks))) {
                        $this->error(__FUNCTION__, __LINE__, $line, $token, 'there is no open block');
                    }
                    break;
                case self::T_IF_ELSE:
                    // else is only allowed within an 'if' block
                    if ((($cnt = count($this->blocks)) > 0 && $this->blocks[$cnt - 1]['token'] == self::T_IF_OPEN) || $cnt == 0) {
                        $this->error(__FUNCTION__, __LINE__, $line, $token, 'only allowed inside an "if" block');
                    } else {
                        $this->blocks[$cnt - 1]['token'] = self::T_IF_ELSE;
                    }
                    break;
                }
                
                printf("%s(%d)->", $this->getTokenName($token), count($stack));
                if (!($tmp = $get_next_rule($rule, $token))) {
                    $this->error(__FUNCTION__, __LINE__, $line, $token, $rule);
                }
                
                $rule = $tmp;
            }
            
            return $valid;
        }

        /****m* compiler/getNextToken
         * SYNOPSIS
         */
        protected function getNextToken(&$tokens)
        /*
         * FUNCTION
         *      return next token and store last token
         * INPUTS
         *      * $tokens (array) -- tokens to use
         * OUTPUTS
         *      (int) -- next token
         ****
         */
        {
            if (($current = array_shift($tokens))) {
                $this->last_tokens[] = $current['token'];
            }
            
            return $current;
        }

        /****m* compiler/getLastToken
         * SYNOPSIS
         */
        public function getLastToken($tokens, $idx)
        /*
         * FUNCTION
         *      return previously processed token
         * INPUTS
         *      * $tokens (array) -- tokens
         *      * $idx (int) -- index to return token of, according to PHP array_slice
         * OUTPUTS
         *      (int) -- ID of token
         ****
         */
        {
            if (($tmp = array_slice($tokens, $idx, 1))) {
                $return = array_pop($tmp);
            } else {
                $return = 0;
            }
            
            return $return;
        }

        /****m* compiler/compile
         * SYNOPSIS
         */
        protected function compile(&$tokens)
        /*
         * FUNCTION
         *      compile tokens to php code
         * INPUTS
         *      * $tokens (array) -- array of tokens to compile
         * OUTPUTS
         *      (string) -- generated php code
         ****
         */
        {
            $code = array();
            
            while (($current = $this->getNextToken($tokens))) {
                extract($current);
            
                switch ($token) {
                case self::T_IF_OPEN:
                case self::T_BLOCK_OPEN:
                    // replace/rewrite block call
                    $value = strtolower($value);
                    
                    list($_start, $_end) = compiler\rewrite::$value(array_reverse($code));

                    $code = array($_start);
                    $this->data['compiler']['blocks'][] = $_end;
                
                    if (($err = compiler\rewrite::getError()) != '') {
                        $this->error(__FUNCTION__, __LINE__, $line, $token, $err);
                    }
                    break;
                case self::T_IF_ELSE:
                    $code[] = '} else {';
                    break;
                case self::T_BLOCK_CLOSE:
                    $code[] = array_pop($this->data['compiler']['blocks']);
                    break;
                case self::T_BRACE_CLOSE:
                    $code[] = implode(', ', ($this->compile($tokens)));
                    break;
                case self::T_METHOD:
                    // replace/rewrite method call
                    $value = strtolower($value);
                    $code  = array(compiler\rewrite::$value(array_reverse($code)));
                    
                    if (($err = compiler\rewrite::getError()) != '') {
                        $this->error(__FUNCTION__, __LINE__, $line, $token, $err);
                    }
                    break 2;
                case self::T_MACRO:
                    // resolve macro
                    $value = strtolower(substr($value, 1));
                    $file  = substr($code[0], 1, -1);
                    $code  = array(compiler\macro::execMacro($value, array($file), array('path' => dirname($this->filename))));

                    if (($err = compiler\macro::getError()) != '') {
                        $this->error(__FUNCTION__, __LINE__, $line, $token, $err);
                    }
                    break 2;
                case self::T_CONSTANT:
                    $tmp = $this->getConstant(substr($value, 1));
                
                    $code[] = (is_string($tmp) ? '"' . $tmp . '"' : (int)$tmp);
                    break;
                case self::T_VARIABLE:
                    $code[] = sprintf(
                        '$this->data["%s"]', 
                        implode('"]["', explode(':', strtolower(substr($value, 1))))
                    );
                    break;
                case self::T_STRING:
                case self::T_NUMBER:
                    $code[] = $value;
                    break;
                case self::T_START:
                    $last_token = $this->getLastToken($this->last_tokens, -2);
                    
                    if (in_array($last_token, array(self::T_CONSTANT, self::T_MACRO))) {
                        $code = array(implode('', $code));
                    } elseif (!in_array($last_token, array(self::T_BLOCK_OPEN, self::T_BLOCK_CLOSE, self::T_IF_OPEN, self::T_IF_ELSE))) {
                        $code = array('<?php $this->write(' . implode('', $code) . '); >');
                    } else {
                        $code = array('<?php ' . implode('', $code) . ' >');
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
        
        /****m* compiler/process
         * SYNOPSIS
         */
        protected function process($snippet, $line)
        /*
         * FUNCTION
         *      process template snippet - starts tokenizer and than compiler
         * INPUTS
         *      * $snippet (string) -- template snippet to compile
         *      * $line (int) -- line in template the snippet occured
         * OUTPUTS
         *      (string) -- generated php code
         ****
         */
        {
            $this->last_tokens = array();
            
            $tokens = $this->tokenize($snippet, $line);
            $code   = '';

            if (count($tokens) > 0) {
                if ($this->analyze($tokens) !== false) {
                    $tokens = array_reverse($tokens);
                    $code   = implode('', $this->compile($tokens));
                }
            }

            return $code;
        }
        
        /****m* compiler/parse
         * SYNOPSIS
         */
        public function parse($file)
        /*
         * FUNCTION
         *      template parser -- find all enclosed template
         *      functionality
         * INPUTS
         *      * $filename (string) -- file containing template to parse
         ****
         */
        {
            $this->filename = $file;
            
            $tpl = file_get_contents($file);
            
            $this->data = array(
                'analyzer'  => array(),
                'compiler'  => array(
                    'blocks'    => array()
                )
            );
            
            $this->blocks = array();

            $pattern = '/(\{\{(.*?)\}\})/s';
            $offset  = 0;

            while (preg_match($pattern, $tpl, $m, PREG_OFFSET_CAPTURE, $offset)) {
                $crc = crc32($tpl);
                $ofs = $offset;

                $line = substr_count(substr($tpl, 0, $m[2][1]), "\n") + 1;
                $tpl = substr($tpl, 0, $m[1][1]) . $this->process(trim($m[2][0]), $line) . substr($tpl, $m[1][1] + strlen($m[1][0]));

                if (($crc == crc32($tpl)) && $ofs == $offset) {
                    $this->error(__FUNCTION__, __LINE__, $line, 0, 'endless loop detected');
                }
            }

            if (count($this->blocks) > 0) {
                $this->error(__FUNCTION__, __LINE__, $line, 0, sprintf('missing %s for %s',
                    $this->getTokenName(self::T_BLOCK_CLOSE),
                    implode(', ', $this->getTokenNames(array_reverse($this->blocks)))
                ));
            }
            
            return $tpl;
        }
    }
    

    $tpl = <<<TPL
{#{\$test}}

{#{concat("hallo", " ", "world", "!")}}

{#{func("test")}}

{{mul(add(\$a, \$b), 2)}}

{#{#foreach(\$test, \$item)}}
{#{#end}}

{{@include("test.html")}}


{#{%constant}}
TPL;

    $test = new compiler();
    print $test->parse(dirname(__FILE__) . '/../../tests/tpl/compiler/tpl1.html');

    die;

    $tpl = <<<TPL
    {{#foreach($item, $array)}}
    {{#end}}

    {{#if(...)}}
    {{#elseif(...)}}
    {{#else}}
    {{#end}}

    {{#loop()}}
    {{#end}}

    {{#trigger()}}
    {{#end}}

    {{$item}}

    {{@import("...")}}

    {{%constant}}

TPL;

}
