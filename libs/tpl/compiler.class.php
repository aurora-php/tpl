<?php

namespace org\octris\core\tpl {
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
        const T_START           = 1;
        const T_END             = 2;
    
        const T_BRACE_OPEN      = 10;
        const T_BRACE_CLOSE     = 11;
        const T_PSEPARATOR      = 12;
    
        const T_METHOD          = 20;
        const T_GETTEXT         = 21;
        const T_VARIABLE        = 22;
        const T_CONSTANT        = 23;
        const T_MACRO           = 24;
        const T_KEYWORD         = 25;
    
        const T_STRING          = 30;
        const T_NUMBER          = 31;
        const T_BOOL            = 32;
        
        const T_WHITESPACE      = 40;
        const T_NEWLINE         = 41;
    
        private static $tokens = array(
            self::T_BRACE_OPEN  => '\(',
            self::T_BRACE_CLOSE => '\)',
            self::T_PSEPARATOR  => '\,',

            self::T_KEYWORD     => '(end|else)',
            self::T_METHOD      => '[a-z_][a-z0-9_]+',
            self::T_GETTEXT     => '_',
            self::T_VARIABLE    => '\$[a-z_][a-z0-9_]*(:\$?[a-z_][a-z0-9_]*|)+',
            self::T_CONSTANT    => "/%[_a-z][_a-z0-9]+/",
            self::T_MACRO       => "/@[_a-z][_a-z0-9]+/",
        
            self::T_STRING      => "([\"']).*?(?!\\\\)\\2",
            self::T_NUMBER      => '[+-]?[0-9]+(\.[0-9]+|)',
            self::T_BOOL        => '(true|false)',
            
            self::T_WHITESPACE  => '\s+',
            self::T_NEWLINE     => '\n+',
        );
    
        private static $rules = array(
            // start : 
            self::T_START   => array(
                self::T_METHOD, self::T_MACRO, self::T_CONSTANT, self::T_VARIABLE, self::T_KEYWORD
            )
            
            // keywords : end/else
          , self::T_KEYWORD => array(
                self::T_END
            )
            
            // method : method(..., ...)
          , self::T_METHOD  => array(
                self::T_BRACE_OPEN  => array(
                    self::T_METHOD, self::T_CONSTANT, self::T_VARIABLE, self::T_STRING, self::T_NUMBER, self::T_BOOL, self::T_BRACE_CLOSE
                )
              , self::T_BRACE_CLOSE => array(
                    self::T_BRACE_CLOSE, self::T_END
                )
            )
        
            // gettext: _("..." [, ...])
          , self::T_GETTEXT => array(
                self::T_BRACE_OPEN  => array(
                    self::T_STRING
                )
              , self::T_STRING      => array(
                    self::T_PSEPARATOR, self::T_BRACE_CLOSE
                )
            )
        
            // macro : @macro(... [, ...])
          , self::T_MACRO   => array(
                self::T_BRACE_OPEN  => array(
                    self::T_CONSTANT, self::T_STRING, self::T_NUMBER, self::T_BOOL, self::T_BRACE_CLOSE
                )
              , self::T_CONSTANT    => array(
                    self::T_PSEPARATOR, self::T_BRACE_CLOSE
                )
              , self::T_STRING      => array(
                    self::T_PSEPARATOR, self::T_BRACE_CLOSE
                )
              , self::T_NUMBER      => array(
                    self::T_PSEPARATOR, self::T_BRACE_CLOSE
                )
              , self::T_BOOL        => array(
                    self::T_PSEPARATOR, self::T_BRACE_CLOSE
                )
              , self::T_PSEPARATOR  => array(
                    self::T_CONSTANT, self::T_STRING, self::T_NUMBER, self::T_BOOL, self::T_BRACE_CLOSE
                )
              , self::T_BRACE_CLOSE => array(
                    self::T_END
                )
            )
        );
        
        /****v* compiler/$filename
         * SYNOPSIS
         */
        protected $filename = '';
        /*
         * FUNCTION
         *      name of file currently compiled
         ****
         */
        
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
            $analyze = function($rules, $last_token) use (&$analyze, &$tokens) {
                while ($token = array_shift($tokens)) {
                    if (!isset($rules[$last_token])) {
                        // rule not in context
                        return false;
                    }

                    $rule = $rules[$last_token];

                    if (array_values($rule) === $rule) {
                        if (!in_array($token['token'], $rule)) {
                            print_r($token);
                            print_r($rule);
                            die('unexpected token!');
                        }
                    } else {
                        if (!isset($rule[$token['token']])) {
                            print_r($token);
                            die('unexpected token!');
                        }
                        
                        $analyze($rule[$token['token']], $token['token']);
                    }
                
                    $last_token = $token['token'];
                }
                
                return true;
            };
            
            $result = $analyze(self::$rules, self::T_START);
            print (int)$result;
        }

        /****m* compiler/compile
         * SYNOPSIS
         */
        protected function compile($tokens, $brace_level = 0)
        /*
         * FUNCTION
         *      syntactical analyze and compile tokens to php code
         * INPUTS
         *      * $tokens (array) -- array of tokens to compile
         *      * $brace_level (int) -- number of open braces
         * OUTPUTS
         *      (string) -- generated php code
         ****
         */
        {
            static $last_token = array(self::T_START);

            if ($brace_level == 0) {
                // initialisation for new compiler run
                $last_token = array(self::T_START);
            }

            // initialize code constructors
            $code = array();
            $level_tokens = array();

            $prefix  = '';
            $postfix = '';

            $gettext = false;

            while ($token = array_shift($tokens)) {
                $expect = $this->getRules($last_token);
                $tmp    = $token;

                extract($token);

                $last_token[] = $token;

                if (!in_array($token, $expect)) {
                    $tmp_expect = '';

                    for ($i = 0; $i < count($expect); $i++) {
                        $tmp_expect .= ', ' . $this->getTokenName($expect[$i]);
                    }

                    $this->error(
                        self::E_UNEXPECTED_TOKEN,
                        array('got' => $this->getTokenName($token), 'expected' => substr($tmp_expect, 2)),
                        $tmp['file'],
                        $tmp['line']
                    );
                } elseif ($token != self::T_BRACE_CLOSE) {
                    $level_tokens[] = $token;
                }

                switch ($token) {
                case self::T_ASSIGN:
                    array_push($code, ' = ');
                    break;
                case self::T_BLOCK_OPEN:
                    switch (strtoupper($value)) {
                    case '#CACHE':
                        $prefix = 'if (!$this->cache';
                        $postfix = ') {';
                        break;
                    case '#COPY':
                        $prefix = '$this->copy_start';
                        break;
                    case '#CRON':
                        $prefix = 'if ($this->cron';
                        $postfix = ') {';
                        break;
                    case '#CUT':
                        $prefix = '$this->cut_start';
                        break;
                    case '#EACH':
                        $prefix = 'while ($this->each';
                        $postfix = ') {';
                        break;
                    case '#IF':
                        $prefix = 'if ';
                        $postfix = ' {';
                        break;
                    case '#IFNOT':
                        $prefix = 'if (!';
                        $postfix = ') {';
                        break;
                    case '#LOOP':
                        $prefix = 'while ($this->loop';
                        $postfix = ') {';
                        break;
                    case '#ONCHANGE':
                        $prefix = 'if ($this->onchange';
                        $postfix = ') {';
                        break;
                    case '#TRIGGER':
                        $prefix = 'if ($this->trigger';
                        $postfix = ') {';
                        break;
                    default:
                        print '<pre>** ERROR: unknown block type **'."\n";
                        print '   block: ' . $value . "</pre>\n";
                        die;
                    }

                    array_push($symbols, strtoupper($value));
                    break;
                case self::T_BLOCK_CLOSE:
                    $smbl = array_pop($symbols);

                    switch ($smbl) {
                    case '#CACHE':
                        array_push($code, '$this->cache_end(); ');
                        array_push($code, '}');
                        break;
                    case '#COPY':
                        array_push($code, '$this->copy_end(); ');
                        break;
                    case '#CUT':
                        array_push($code, '$this->cut_end(); ');
                        break;
                    default:
                        array_push($code, '}');
                        break;
                    }

                    break;
                case self::T_BRACE_OPEN:
                    if ($brace_level == 0 && count($code) <= 0 && $prefix == '') {
                        array_push($code, 'print ');
                    }

                    $return = $this->compile($tokens, $brace_level + 1);

                    if ($gettext) {
                        $inline = $this->compileGettext($return);
                    } else {
                        $inline = '(' . join('', $return['code']) . ')';
                    }

                    array_push($code, $prefix . $inline . $postfix);

                    if ($brace_level == 0) {
                        $gettext = false;
                    }
                    break;
                case self::T_BRACE_CLOSE:
                    return array('code' => $code, 'tokens' => $level_tokens);
                    break;
                case self::T_DECIMAL:
                    array_push($code, $value);
                    break;
                case self::T_END:
                    $lcnt = count($code);
                    $lchar = ($lcnt > 0 ? substr($code[$lcnt - 1], -1) : '');

                    array_push($code, ($lchar != '{' && $lchar != '}' ? ";\n" : "\n"));

                    return join('', $code);
                    break;
                case self::T_FALSE:
                    array_push($code, 'false');
                    break;
                case self::T_GETTEXT:
                    if ($brace_level == 0 && count($code) <= 0) {
                        array_push($code, 'print ');
                    }

                    $gettext = true;
                    break;
                case self::T_INCLUDE:
                    array_push($code, '$this->includeSub');
                    break;
                case self::T_DUMP:
                    array_push($code, 'print $this->dump');
                    break;
                case self::T_IMPORT:
                    $value = 'importFile';
                    /* fall thru */
                case self::T_METHOD:
                    if ($brace_level == 0 && count($code) <= 0) {
                        array_push($code, 'print ');
                    }

                    if (in_array(strtolower($value), self::$forbidden)) {
                        $this->error(
                            self::E_FORBIDDEN_METHOD,
                            array(
                                'at'    => $this->getTokenName($token),
                                'value' => $value,
                                'trace' => $this->dump(array_reverse($last_token))
                            ),
                            $token['file'],
                            $token['line']
                        );
                        die;
                    } elseif (in_array(strtolower($value), self::$allowedphp)) {
                        array_push($code, $value);
                    } else {
                        array_push($code, '$this->' . $value);
                    }
                    break;
                case self::T_NULL:
                    array_push($code, 'NULL');
                    break;
                case self::T_PSEPARATOR:
                    array_push($code, ', ');
                    break;
                case self::T_CONCAT:
                case self::T_MATH:
                case self::T_STRING:
                    array_push($code, $value);
                    break;
                case self::T_TRUE:
                    array_push($code, 'true');
                    break;
                case self::T_VARIABLE:
                    if ($brace_level == 0 && count($code) == 0) {
                        array_push($code, 'print ');
                    }

                    $value = substr($value, 1);

    /*                array_push(
                        $code,
                        '$this->__data__' . implode('',
                            preg_replace(
                                '/^(.*)$/e',
                                "(substr('\\1', 0, 1) == '$' ? '[\$this->__data__[\'' . substr('\\1', 1) . '\']]' : '[\'\\1\']')",
                                explode(':', $value)
                            )
                        )
                    ); */

                    array_push($code, '$this->__data__[\'' . join('\'][\'', explode(':', $value)) . '\']');
                    break;
                case self::T_SYMBOL:
                    array_push($code, $this->getValue($value));
                    break;
                default:
                    $this->error(
                        self::E_UNDEFINED_TOKEN,
                        array('at' => $this->getTokenName($token), 'trace' => $this->dump(array_reverse($last_token))),
                        $token['file'],
                        $token['line']
                    );
                    die;
                    break;
                }
            }

            $this->error(
                self::E_UNEXPECTED_TOKEN,
                array('at' => $this->getTokenName($token), 'trace' => $this->dump(array_reverse($last_token))),
                $token['file'],
                $token['line']
            );
            die;
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
            $tokens = $this->tokenize($snippet, $line);
            $code   = '';

            $this->analyze($tokens);

            print_r($tokens);
            die;

            if (count($tokens) > 0) {
                $code = '<?php ' . trim($this->compile($tokens)) . ' ?>';
            }

            return $code;
        }
        
        /****m* compiler/parse
         * SYNOPSIS
         */
        public function parse($tpl)
        /*
         * FUNCTION
         *      template parser -- find all enclosed template
         *      functionality
         * INPUTS
         *      * $tpl (string) -- template to parse
         ****
         */
        {
            $this->blocks = array();

            $pattern = '/(\{\{(.*?)\}\})/s';
            $offset  = 0;

            while (preg_match($pattern, $tpl, $m, PREG_OFFSET_CAPTURE, $offset)) {
                $crc = crc32($tpl);
                $ofs = $offset;

                $line = substr_count(substr($tpl, 0, $m[2][1]), "\n") + 1;
                $tpl = substr($tpl, 0, $m[1][1]) . $this->process(trim($m[2][0]), $line) . substr($tpl, $m[1][1] + strlen($m[1][0]));

                if (($crc == crc32($tpl))&&($ofs == $offset)) {
                    die('infinity loop');
                }
            }

            if (count($this->blocks) > 0) {
                die('missing T_BLOCK_END');
            }
        }
    }
    

    $tpl = <<<TPL
{{\$test}}
TPL;

    $test = new compiler();
    $test->parse($tpl);

    $tpl = <<<TPL
    {{foreach($item, $array)}}
    {{end}}

    {{if(...)}}
    {{elseif(...)}}
    {{end}}

    {{loop()}}
    {{end}}

    {{trigger()}}
    {{end}}

    {{$item}}

    {{@import("...")}}

    {{%constant}}

TPL;

}
