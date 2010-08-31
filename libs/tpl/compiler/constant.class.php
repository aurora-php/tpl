<?php

namespace org\octris\core\tpl\compiler {
    /****c* compiler/constant
     * NAME
     *      constant
     * FUNCTION
     *      Library for handling template constants. This is a static class.
     * COPYRIGHT
     *      copyright (c) 2010 by Harald Lapp
     * AUTHOR
     *      Harald Lapp <harald@octris.org>
     ****
     */

    class constant {
        /****v* constant/$registry
         * SYNOPSIS
         */
        protected static $registry = array();
        /*
         * FUNCTION
         *      constant registry
         ****
         */
        
        /****v* constant/$last_error
         * SYNOPSIS
         */
        protected static $last_error = '';
        /*
         * FUNCTION
         *      last occured error
         ****
         */

        /*
         * static class cannot be instantiated
         */
        protected function __construct() {}
        protected function __clone() {}
        
        /****m* constant/getError
         * SYNOPSIS
         */
        public static function getError()
        /*
         * FUNCTION
         *      return last occured error
         * OUTPUTS
         *      (string) -- last occured error
         ****
         */
        {
            return self::$last_error;
        }
        
        /****m* constant/setError
         * SYNOPSIS
         */
        protected static function setError($name, $msg)
        /*
         * FUNCTION
         *      set error
         * INPUTS
         *      * $name (string) -- name of constant the error occured for
         *      * $msg (string) -- additional error message
         ****
         */
        {
            self::$last_error = sprintf('"%s" -- %s', $name, $msg);
        }
        
        /****m* constant/setConstant
         * SYNOPSIS
         */
        public static function setConstant($name, $value)
        /*
         * FUNCTION
         *      set a constant
         * INPUTS
         *      * $name (string) -- name of constant to register
         *      * $value (mixed) -- value of constant
         ****
         */
        {
            self::$registry[$name] = $value;
        }
        
        /****m* constant/setConstants
         * SYNOPSIS
         */
        public static function setConstants($array)
        /*
         * FUNCTION
         *      set multiple constants
         * INPUTS
         *      * $array (array) -- multiple constants to set
         ****
         */
        {
            self::$registry[$name] = array_merge(self::$registry[$name], $array);
        }
        
        /****m* constant/getConstant
         * SYNOPSIS
         */
        public static function getConstant($name)
        /*
         * FUNCTION
         *      return value of a constant
         * INPUTS
         *      * $name (string) -- name of constant to return value of
         * OUTPUTS
         *      (mixed) -- value of constant
         ****
         */
        {
            self::$last_error = '';
            
            if (!isset(self::$registry[$name])) {
                self::setError($name, 'unknown constant');
            } else {
                return self::$registry[$name];
            }
        }
    }
}
