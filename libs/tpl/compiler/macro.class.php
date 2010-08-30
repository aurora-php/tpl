<?php

namespace org\octris\core\tpl\compiler {
    /****c* compiler/macro
     * NAME
     *      macro
     * FUNCTION
     *      Library for handling template macros.
     * COPYRIGHT
     *      copyright (c) 2010 by Harald Lapp
     * AUTHOR
     *      Harald Lapp <harald@octris.org>
     ****
     */

    class macro {
        /****v* macro/$instance
         * SYNOPSIS
         */
        protected static $instance = NULL;
        /*
         * FUNCTION
         *      instance of macro object
         ****
         */
        
        /****v* macro/$registry
         * SYNOPSIS
         */
        protected $registry = array();
        /*
         * FUNCTION
         *      macro registry
         ****
         */
        
        /****v* macro/$last_error
         * SYNOPSIS
         */
        protected $last_error = '';
        /*
         * FUNCTION
         *      last occured error
         ****
         */
        
        /****m* macro/__construct
         * SYNOPSIS
         */
        protected function __construct()
        /*
         * FUNCTION
         *      constructor
         ****
         */
        {
        }

        // prevent from cloning
        protected function __clone() {}
        
        /****m* macro/getInstance
         * SYNOPSIS
         */
        public static function getInstance()
        /*
         * FUNCTION
         *      implements singleton for macro class
         * OUTPUTS
         *      (macro) -- instance of macro class
         ****
         */
        {
            if (is_null(self::$instance)) {
                self::$instance = new static();
                self::$instance->registerMacro(
                    'import', 
                    array(self::$instance, 'import'),
                    array('min' => 1, 'max' => 1)
                );
            }
            
            return self::$instance;
        }
        
        /****m* macro/getError
         * SYNOPSIS
         */
        public function getError()
        /*
         * FUNCTION
         *      return last occured error
         * OUTPUTS
         *      (string) -- last occured error
         ****
         */
        {
            return $this->last_error;
        }
        
        /****m* macro/setError
         * SYNOPSIS
         */
        protected function setError($name, $msg)
        /*
         * FUNCTION
         *      set error
         * INPUTS
         *      * $name (string) -- name of macro the error occured for
         *      * $msg (string) -- additional error message
         ****
         */
        {
            $this->last_error = sprintf('"%s" -- %s', $name, $msg);
        }
        
        /****m* macro/registerMacro
         * SYNOPSIS
         */
        public function registerMacro($name, $callback, array $args)
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
            $this->registry[$name] = array(
                'callback' => $callback,
                'args'     => array_merge(array('min' => 1, 'max' => 1), $args)
            );
        }
        
        /****m* macro/execMacro
         * SYNOPSIS
         */
        public function execMacro($name, $args, array $options = array())
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
            $this->last_error = '';
            
            if (!isset($this->registry[$name])) {
                $this->setError($name, 'unknown macro');
            } elseif (!is_callable($this->registry[$name]['callback'])) {
                $this->setError($name, 'unable to execute macro');
            } elseif (count($args) < $this->registry[$name]['args']['min']) {
                $this->setError($name, 'not enough arguments');
            } elseif (count($args) > $this->registry[$name]['args']['max']) {
                $this->setError($name, 'too many arguments');
            } else {
                return call_user_func_array($this->registry[$name]['callback'], array($args, $options));
            }
        }
        
        /****m* macro/import
         * SYNOPSIS
         */
        protected function import($args, $options)
        /*
         * FUNCTION
         *      macro for importing subtemplates
         * INPUTS
         *      * $args (array) -- arguments
         *      * $options (array) -- additional options coming from compiler
         * OUTPUTS
         *      (string) -- processed template
         ****
         */
        {
            $tpl = new \org\octris\core\tpl\compiler();
            
            return $tpl->parse($options['path'] . '/' . $args[0]);
        }
    }
}