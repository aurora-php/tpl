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
            self::T_CONSTANT    => "%[_a-z][_a-z0-9]+",
            self::T_MACRO       => "@[_a-z][_a-z0-9]+",
        
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
            
            // constant
          , self::T_CONSTANT => array(
                self::T_END
            )

            // variable 
          , self::T_VARIABLE => array(
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
            $analyze = function($rules, &$last_token) use (&$analyze, &$tokens) {
                while ($token = array_shift($tokens)) {
                    if (!isset($rules[$last_token])) {
                        // rule not in context
                        return false;
                    }

                    extract($token);

                    $rule = $rules[$last_token];

                    if (array_values($rule) === $rule) {
                        if (!in_array($token, $rule)) {
                            print_r(array($token, $line));
                            print_r($rule);
                            die('unexpected token!');
                        }
                    } else {
                        if (!isset($rule[$token])) {
                            print_r(array($token, $line));
                            print_r($rule);
                            die('unexpected token!');
                        }
                        
                        $analyze($rule[$token], $token);
                    }
                
                    $last_token = $token;
                }
                
                return true;
            };
            
            return $analyze(self::$rules, $token = self::T_START);
        }

        /****m* compiler/compile
         * SYNOPSIS
         */
        protected function compile($tokens)
        /*
         * FUNCTION
         *      compile tokens to php code
         * INPUTS
         *      * $tokens (array) -- array of tokens to compile
         *      * $brace_level (int) -- number of open braces
         * OUTPUTS
         *      (string) -- generated php code
         ****
         */
        {
            $code   = '%s';
            $braces = 0;
            
            while ($token = array_shift($tokens)) {
                print_r($token);

                extract($token);
            
                switch ($token) {
                case self::T_METHOD:
                    switch ($value) {
                    case 'foreach':
                        $tmp = 'foreach (%s) {';
                        break;
                    case 'if':
                        $tmp = 'if (%s) {';
                        break;
                    case 'elseif':
                        $tmp = '} elseif (%s) {';
                        break;
                    default:
                        $tmp = sprintf('$this->callFunc(%s, array(%%s));', $value);
                        print "$tmp";
                        break;
                    }
                    break;
                case self::T_BRACE_OPEN:
                    ++$braces;
                    break;
                case self::T_BRACE_CLOSE:
                    --$braces;
                    break;
                case self::T_MACRO:
                    // TODO: macro
                    $tmp = $this->callMacro(substr($value, 1));
                    break;
                case self::T_CONSTANT:
                    $tmp = $this->getConstant(substr($value, 1));
                    break;
                case self::T_VARIABLE:
                    $tmp = sprintf('$this->get("%s")', substr($value, 1));
                    break;
                case self::T_END:
                    break;
                default:
                    die('**unknwon token**');
                }
                
                $code = sprintf($code, $tmp);
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
            $tokens = $this->tokenize($snippet, $line);
            $code   = '';

            if (count($tokens) > 0) {
                if ($this->analyze($tokens) !== false) {
                    switch ($tokens[0]['token']) {
                    case self::T_CONSTANT:
                    case self::T_MACRO:
                        $code = '%s';
                        break;
                    default:
                        $code = '<?php %s ?>';
                        break;
                    }
                    
                    $code = sprintf($code, trim($this->compile($tokens)));
                }
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
            
            print "$tpl";
        }
    }
    

    $tpl = <<<TPL
{{\$test}}

{{func("test")}}

{{%constant}}
TPL;

    $test = new compiler();
    $test->parse($tpl);

    die;

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
