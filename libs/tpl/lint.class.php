<?php

namespace org\octris\core\tpl {
    require_once('compiler.class.php');
    
    /****c* tpl/lint
     * NAME
     *      lint
     * FUNCTION
     *      lint for templates
     * COPYRIGHT
     *      copyright (c) 2010 by Harald Lapp
     * AUTHOR
     *      Harald Lapp <harald@octris.org>
     ****
     */

    class lint extends \org\octris\core\tpl\compiler {
        /****v* lint/$errout
         * SYNOPSIS
         */
        protected $errout;
        /*
         * FUNCTION
         *      output for error messages
         ****
         */
        
        /****v* lint/$errors = 0
         * SYNOPSIS
         */
        protected $errors = 0;
        /*
         * FUNCTION
         *      number of errors occured
         ****
         */

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
            if ($fp = fopen($this->errout, 'w+')) {
                fwrite($fp, sprintf("\n** ERROR: %s(%d) **\n", $type, $cline));
                fwrite($fp, sprintf("   line :    %d\n", $line));
                fwrite($fp, sprintf("   file:     %s\n", $this->filename));
                fwrite($fp, sprintf("   token:    %s\n", $this->getTokenName($token)));
            
                if (is_array($payload)) {
                    fwrite($fp, sprintf("   expected: %s\n", implode(', ', $this->getTokenNames(array_keys($payload)))));
                } elseif (isset($payload)) {
                    fwrite($fp, sprintf("   message:  %s\n", $payload));
                }
                
                fclose($fp);
            }
         
            ++$this->errors;
            
            if ($type == 'analyze') throw new \Exception('syntax error');
        }
        
        /****m* lint/toolchain
         * SYNOPSIS
         */
        protected function toolchain($snippet, $line)
        /*
         * FUNCTION
         *      execute compiler toolchain for a template snippet
         * INPUTS
         *      * $snippet (string) -- template snippet to process
         *      * $line (int) -- line template to process
         * OUTPUTS
         *      (string) -- processed / compiled snippet
         ****
         */
        {
            $tokens = $this->tokenize($snippet, $line);
            $code   = '';

            if (count($tokens) > 0) {
                try {
                    $this->analyze($tokens);
                } catch(\Exception $e) {
                }
            }
            
            return $code;
        }
        
        /****m* lint/process
         * SYNOPSIS
         */
        public function process($filename, $errout = 'php://stdout')
        /*
         * FUNCTION
         *      lint -- only tokenize and analyze file
         * INPUTS
         *      * $filename (string) -- file to lint
         *      * $err (string) -- destination for error reporting
         * OUTPUTS
         *      (bool) -- returns true if template is valid, otherwise false
         ****
         */
        {
            $this->filename = $filename;
            $this->errors   = 0;
            $this->errout   = $errout;

            $this->parse($filename, true);
            
            return ($this->errors == 0);
        }
    }
}
