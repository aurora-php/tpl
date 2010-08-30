<?php

namespace org\octris\core\tpl {
    require_once('type/collection.class.php');
    
    /****c* tpl/sandbox
     * NAME
     *      sandbox
     * FUNCTION
     *      sandbox to execute templates in
     * COPYRIGHT
     *      copyright (c) 2010 by Harald Lapp
     * AUTHOR
     *      Harald Lapp <harald@octris.org>
     ****
     */

    class sandbox {
        /****v* sandbox/$data
         * SYNOPSIS
         */
        public $data = array();
        /*
         * FUNCTION
         *      template data
         ****
         */

        /****v* sandbox/$pastebin
         * SYNOPSIS
         */
        protected $pastebin = array();
        /*
         * FUNCTION
         *      pastebin for cut/copied buffers
         ****
         */
        
        /****m* sandbox/setValue
         * SYNOPSIS
         */
        public function setValue($name, $value)
        /*
         * FUNCTION
         *      set value for sandbox
         * INPUTS
         *      * $name (string) -- name of variable to set
         *      * $value (mixed) -- value of variable
         ****
         */
        {
            if (is_scalar($value)) {
                $this->data[$name] = $value;
            } else {
                $this->data[$name] = new type\collection($value);
            }
        }
        
        /****m* sandbox/bufferStart
         * SYNOPSIS
         */
        public function bufferStart(&$ctrl, $cut = true)
        /*
         * FUNCTION
         *      start output buffer
         * INPUTS
         *      * $ctrl (mixed) -- control variable to store buffer data in
         *      * $cut (bool) -- (optional) whether to cut or to copy to buffer
         ****
         */
        {
            array_push($this->pastebin, array(
                'buffer' => &$ctrl,
                'cut'    => $cut
            ));

            ob_start();
        }

        /****m* sandbox/bufferEnd
         * SYNOPSIS
         */
        public function bufferEnd()
        /*
         * FUNCTION
         *      stop output buffer
         ****
         */
        {
            $buffer = array_pop($this->pastebin);
            $buffer['buffer'] = ob_get_contents();
            
            if ($buffer['cut']) {
                ob_end_clean();
            } else {
                ob_end_flush();
            }
        }
        
        /****m* sandbox/
         * SYNOPSIS
         */
        public function write($val, $auto_escape = true)
        /*
         * FUNCTION
         *      output a specified value
         * INPUTS
         *      * $val (string) -- value to output
         *      * $auto_escape (bool) -- (optional) flag whether to auto-escape value
         ****
         */
        {
            print $val;
        }
    }
}
