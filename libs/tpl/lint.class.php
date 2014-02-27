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
    require_once('compiler.class.php');
    
    /**
     * Lint for templates.
     *
     * @octdoc      c:tpl/lint
     * @copyright   copyright (c) 2010-2011 by Harald Lapp
     * @author      Harald Lapp <harald@octris.org>
     */
    class lint extends \org\octris\core\tpl\compiler
    /**/
    {
        /**
         * File handle for error messages output.
         *
         * @octdoc  p:lint/$errout
         * @type    resource
         */
        protected $errout = 'php://stderr';
        /**/

        /**
         * Number of errors occured.
         *
         * @octdoc  p:lint/$errors
         * @type    int
         */
        protected $errors = 0;
        /**/

        /**
         * Set location for error output.
         *
         * @octdoc  m:lint/setErrorOutput
         * @param   string      $errout     Location for error output.
         */
        public function setErrorOutput($errout)
        /**/
        {
            $this->errout = $errout;
        }

        /**
         * Trigger an error.
         *
         * @octdoc  m:lint/error
         * @param   string      $type       Type of error to trigger.
         * @param   int         $cline      Line in compiler class error was triggered from.
         * @param   int         $line       Line in template the error was triggered for.
         * @param   int         $token      ID of token that triggered the error.
         * @param   mixed       $payload    Optional additional information. Either an array of expected token IDs or an additional message to output.
         */
        protected function error($type, $cline, $line, $token, $payload = NULL)
        /**/
        {
            if ($fp = fopen($this->errout, 'w+')) {
                fwrite($fp, sprintf("\n** ERROR: %s(%d) **\n", $type, $cline));
                fwrite($fp, sprintf("   line :    %d\n", $line));
                fwrite($fp, sprintf("   file :    %s\n", $this->filename));
                fwrite($fp, sprintf("   token:    %s\n", $this->getTokenName($token)));
            
                if (is_array($payload)) {
                    fwrite($fp, sprintf("   expected: %s\n", implode(', ', $this->getTokenNames(array_keys($payload)))));
                } elseif (isset($payload)) {
                    fwrite($fp, sprintf("   message:  %s\n", $payload));
                }
                
                fclose($fp);
            }
         
            ++$this->errors;
            
            if ($type == 'analyze' || $type == 'tokenize') throw new \Exception('syntax error');
        }
        
        /**
         * Execute compiler toolchain for a template snippet.
         *
         * @octdoc  m:lint/toolchain
         * @param   string      $snippet        Template snippet to process.
         * @param   int         $line           Line in template processed.
         * @param   array       $blocks         Block information required by analyzer / compiler.
         * @param   string      $escape         Escaping to use.
         * @return  string                      Processed / compiled snippet.
         */
        protected function toolchain($snippet, $line, array &$blocks, $escape)
        /**/
        {
            $tokens = $this->tokenize($snippet, $line);
            $code   = '';

            if (count($tokens) > 0) {
                try {
                    $this->analyze($tokens, $blocks);
                } catch(\Exception $e) {
                }
            }
            
            return $code;
        }
        
        /**
         * Process a template.
         *
         * @octdoc  m:lint/process
         * @param   string      $filename       Name of template file to lint.
         * @param   string      $escape         Escaping to use.
         * @param   string      $err            Destination for error reporting.
         * @return  bool                        Returns true if template is valid.
         */
        public function process($filename, $escape)
        /**/
        {
            $this->filename = $filename;
            $this->errors   = 0;

            $this->parse(\org\octris\core\tpl::T_ESC_NONE);
            
            return ($this->errors == 0);
        }
    }
}
