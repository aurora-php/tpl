<?php

namespace org\octris\core\tpl\compiler {
    /****c* compiler/macro
     * NAME
     *      macro
     * FUNCTION
     *      Library for handling template macros. This is a static class.
     * COPYRIGHT
     *      copyright (c) 2010 by Harald Lapp
     * AUTHOR
     *      Harald Lapp <harald@octris.org>
     ****
     */

    class macro {
        /****v* macro/$registry
         * SYNOPSIS
         */
        protected static $registry = array();
        /*
         * FUNCTION
         *      macro registry
         ****
         */
        
        /****v* macro/$last_error
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
        
        /****m* macro/getError
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
        
        /****m* macro/setError
         * SYNOPSIS
         */
        protected static function setError($name, $msg)
        /*
         * FUNCTION
         *      set error
         * INPUTS
         *      * $name (string) -- name of macro the error occured for
         *      * $msg (string) -- additional error message
         ****
         */
        {
            self::$last_error = sprintf('"%s" -- %s', $name, $msg);
        }
        
        /****m* macro/registerMacro
         * SYNOPSIS
         */
        public static function registerMacro($name, $callback, array $args)
        /*
         * FUNCTION
         *      register a macro
         * INPUTS
         *      * $name (string) -- name of macro to register
         *      * $callback (mixed) -- callback to call when macro is executed
         *      * $args (array) -- for testing arguments
         ****
         */
        {
            self::$registry[strtolower($name)] = array(
                'callback' => $callback,
                'args'     => array_merge(array('min' => 0, 'max' => 0), $args)
            );
        }
        
        /****m* macro/execMacro
         * SYNOPSIS
         */
        public static function execMacro($name, $args, array $options = array())
        /*
         * FUNCTION
         *      execute specified macro with specified arguments
         * INPUTS
         *      * $name (string) -- name of macro to execute
         *      * $args (array) -- arguments for macro
         *      * $options (array) -- additional options for macro
         * OUTPUTS
         *      (mixed) -- output of macro
         ****
         */
        {
            self::$last_error = '';
            
            $name = strtolower($name);
            
            if (!isset(self::$registry[$name])) {
                self::setError($name, 'unknown macro');
            } elseif (!is_callable(self::$registry[$name]['callback'])) {
                self::setError($name, 'unable to execute macro');
            } elseif (count($args) < self::$registry[$name]['args']['min']) {
                self::setError($name, 'not enough arguments');
            } elseif (count($args) > self::$registry[$name]['args']['max']) {
                self::setError($name, 'too many arguments');
            } else {
                list($ret, $err) = call_user_func_array(self::$registry[$name]['callback'], array($args, $options));
                
                if ($err) {
                    self::setError($name, $err);
                }
                
                return $ret;
            }
        }
    }

    /*
     * register "import" macro
     */
    macro::registerMacro(
        'import',
        function($args, array $options = array()) {
            $ret = '';
            $err = '';
            
            $c = new \org\octris\core\tpl\compiler();
            $c->addSearchPath($options['searchpath']);
                
            if (($file = $c->findFile($args[0])) !== false) {
                $ret = $c->process($file);
            } else {
                $err = sprintf(
                    'unable to locate file "%s" in "%s"', 
                    $args[0],
                    implode(':', \org\octris\core\tpl\compiler\searchpath::getPath())
                );
            }
            
            return array($ret, $err);
        },
        array('min' => 1, 'max' => 1)
    );
}
