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
