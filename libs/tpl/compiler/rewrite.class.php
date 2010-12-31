<?php

namespace org\octris\core\tpl\compiler {
    /****c* compiler/rewrite
     * NAME
     *      rewrite
     * FUNCTION
     *      Rewrite template code. Rewrite inline function calls, and rewrite
     *      function calls according to if they are allowed php function calls
     *      or calls to functions that have to be registered to sandbox on
     *      template rendering. This is a static class.
     * COPYRIGHT
     *      copyright (c) 2010 by Harald Lapp
     * AUTHOR
     *      Harald Lapp <harald@octris.org>
     ****
     */

    class rewrite {
        /****v* rewrite/$inline
         * SYNOPSIS
         */
        protected static $inline = array(
            // blocks
            '#if'       => array('min' => 1, 'max' => 1),
            '#foreach'  => array('min' => 2, 'max' => 3),
            '#cache'    => array('min' => 2, 'max' => 2),
            '#copy'     => array('min' => 1, 'max' => 1),
            '#cron'     => array('min' => 1, 'max' => 2),
            '#cut'      => array('min' => 1, 'max' => 1),
            '#loop'     => array('min' => 3, 'max' => 4),
            '#onchange' => array('min' => 1, 'max' => 1),
            '#trigger'  => array('min' => 0, 'max' => 3),
            
            // functions
            '_'     => array('min' => 1),               // gettext
            'if'    => array('min' => 2, 'max' => 3),   // (... ? ... : ...)
            
            'mul'   => array('min' => 2),               // ... * ...
            'div'   => array('min' => 2),               // ... / ...
            'mod'   => array('min' => 2, 'max' => 2),   // ... % ...
            'add'   => array('min' => 2),               // ... + ...
            'sub'   => array('min' => 2),               // ... - ...
            'incr'  => array('min' => 1, 'max' => 2),   // ++ / +=
            'decr'  => array('min' => 1, 'max' => 2),   // -- / -=
            'neg'   => array('min' => 1, 'max' => 1),   // -...
            
            'and'   => array('min' => 2),               // ... && ...
            'or'    => array('min' => 2),               // ... || ...
            'xor'   => array('min' => 2, 'max' => 2),   // ... xor ...
            'not'   => array('min' => 1, 'max' => 1),   // !...
            
            'lt'    => array('min' => 2, 'max' => 2),   // ... < ...
            'gt'    => array('min' => 2, 'max' => 2),   // ... > ...
            'eq'    => array('min' => 2, 'max' => 2),   // ... == ...
            'le'    => array('min' => 2, 'max' => 2),   // ... <= ...
            'ge'    => array('min' => 2, 'max' => 2),   // ... >= ...
            'ne'    => array('min' => 2, 'max' => 2),   // ... != ...

            'bool'   => array('min' => 1, 'max' => 1),  // (bool)...
            'int'    => array('min' => 1, 'max' => 1),  // (int)...
            'float'  => array('min' => 1, 'max' => 1),  // (float)...
            'string' => array('min' => 1, 'max' => 1),  // (string)...

            'now'       => array('min' => 0, 'max' => 0),
            'uniqid'    => array('min' => 0, 'max' => 0),
            'let'       => array('min' => 2, 'max' => 2),
            'dump'      => array('min' => 1, 'max' => 1),
            'error'     => array('min' => 1, 'max' => 1),
            
            'include'   => array('min' => 1, 'max' => 1)
        );
        /*
         * FUNCTION
         *      inline method rewrite
         ****
         */
        
        /****v* rewrite/$phpfunc
         * SYNOPSIS
         */
        protected static $phpfunc = array(
            'substr'    => array('min' => 2, 'max' => 3),
            'sprintf'   => array('min' => 1),
        );
        /*
         * FUNCTION
         *      allowed php functions
         ****
         */
        
        /****v* rewrite/$forbidden
         * SYNOPSIS
         */
        protected static $forbidden = array(
            'setvalue', 'setvalues', 'each', 'bufferstart', 'bufferend', 'cache', 'cron', 'loop', 'onchange', 'trigger',
            '__construct', '__call', 'registermethod', 'render'
        );
        /*
         * FUNCTION
         *      forbidden function names
         ****
         */
        
        /****v* rewrite/$last_error
         * SYNOPSIS
         */
        protected static $last_error = '';
        /*
         * FUNCTION
         *      last error message
         ****
         */
        
        /*
         * static class cannot be instantiated
         */
        protected function __construct() {}
        protected function __clone() {}
        
        /****m* rewrite/__callStatic
         * SYNOPSIS
         */
        public static function __callStatic($name, $args)
        /*
         * FUNCTION
         *      wrapper for methods that can be optimized
         * INPUTS
         *      * $name (string) -- name of method
         *      * $args (array) -- arguments for method
         * OUTPUTS
         *      
         ****
         */
        {
            self::$last_error = '';
           
            $name = strtolower($name);
            $args = $args[0];
            
            if (in_array($name, self::$forbidden)) {
                self::setError($name, 'access denied');
            } elseif (($is_php = isset(self::$phpfunc[$name])) || isset(self::$inline[$name])) {
                // known method
                $cnt = count($args);
                
                if (isset(self::$inline[$name]['min'])) {
                    if ($cnt < self::$inline[$name]['min']) {
                        self::setError($name, 'not enough arguments');
                    }
                }
                if (isset(self::$inline[$name]['max'])) {
                    if ($cnt > self::$inline[$name]['max']) {
                        self::setError($name, 'to many arguments');
                    }
                }
                
                if ($is_php) {
                    // call to allowed PHP function
                    return $name . '(' . implode(', ', $args) . ')';
                } else {
                    // inline function rewrite
                    $name = '_' . str_replace('#', '_', $name);

                    return self::$name($args);
                }
            } elseif (substr($name, 0, 1) == '#') {
                // unknown block function
                self::setError($name, 'unknown block type');
            } else {
                return sprintf(
                    '$this->%s(%s)',
                    $name,
                    implode(', ', $args)
                );
            }
        }
        
        /****m* rewrite/getError
         * SYNOPSIS
         */
        public static function getError()
        /*
         * FUNCTION
         *      return last occured error message
         * OUTPUTS
         *      (string) -- error message
         ****
         */
        {
            return self::$last_error;
        }

        /****m* rewrite/setError
         * SYNOPSIS
         */
        protected static function setError($func, $msg)
        /*
         * FUNCTION
         *      set an error message
         * INPUTS
         *      * $func (string) -- name of function the error occured for
         *      * $msg (string) -- additional error message
         ****
         */
        {
            self::$last_error = sprintf('"%s" -- %s', $func, $msg);
        }

        /****m* rewrite/getUniqId
         * SYNOPSIS
         */
        protected static function getUniqId()
        /*
         * FUNCTION
         *      uniq identifier generator
         * OUTPUTS
         *      (string) -- uniq identifier
         ****
         */
        {
            return md5(uniqid());
        }

        /*
         * inline block functions, that can be converted directly
         */
        protected static function __if($args) {
            return array(
                'if (' . implode('', $args) . ') {',
                '}'
            );
        }

        protected static function __foreach($args) {
            return array(
                'while ($this->each("' . self::getUniqId() . '", ' . implode(', ', $args) . ')) {', 
                '}'
            );
        }
        
        protected static function __cache($args) {
            return array(
                'if ($this->cache(' . implode(', ', $args) . ')) {', 
                '}'
            );
        }
        
        protected static function __copy($args) {
            return array(
                '$this->bufferStart(' . implode(', ', $args) . ', false);', 
                '$this->bufferEnd(' . implode(', ', $args) . ');'
            );
        }
        
        protected static function __cron($args) {
            return array(
                'if ($this->cron(' . implode(', ', $args) . ')) {',
                '}'
            );
        }
        
        protected static function __cut($args) {
            return array(
                '$this->bufferStart(' . implode(', ', $args) . ', true);', 
                '$this->bufferEnd(' . implode(', ', $args) . ');'
            );
        }
        
        protected static function __loop($args) {
            return array(
                'while ($this->loop("' . self::getUniqId() . '", ' . implode(', ', $args) . ')) {',
                '}'
            );
        }

        protected static function __onchange($args) {
            return array(
                'if ($this->onchange("' . self::getUniqId() . '", ' . implode(', ', $args) . ')) {',
                '}'
            );
        }
        
        protected static function __trigger($args) {
            return array(
                'if ($this->trigger("' . self::getUniqId() . '", ' . implode(', ', $args) . ')) {',
                '}'
            );
        }

        protected static function _if($args) {
            return sprintf(
                '(%s ? %s : %s)', 
                $args[0], 
                $args[1], 
                (count($args) > 2 ? $args[3] : $args[1])
            );
        }
        
        protected static function _neg($args) {
            return '(-' . $args[0] . ')';
        }
        
        protected static function _mul($args) {
            return '(' . implode(' * ', $args) . ')';
        }
        
        protected static function _div($args) {
            return '(' . implode(' / ', $args) . ')';
        }
        
        protected static function _mod($args) {
            return '(' . implode(' % ', $args) . ')';
        }
        
        protected static function _add($args) {
            return '(' . implode(' + ', $args) . ')';
        }
        
        protected static function _sub($args) {
            return '(' . implode(' - ', $args) . ')';
        }
        
        protected static function _incr($args) {
            return sprintf('(%s)', (count($args) == 2 ? $arg[0] . ' += ' + $args[1] : '++' . $args[0]));
        }
        
        protected static function _decr($args) {
            return sprintf('(%s)', (count($args) == 2 ? $arg[0] . ' -= ' + $args[1] : '--' . $args[0]));
        }
        
        protected static function _and($args) {
            return '(' . implode(' && ', $args) . ')';
        }

        protected static function _or($args) {
            return '(' . implode(' || ', $args) . ')';
        }

        protected static function _xor($args) {
            return sprintf('(%d != %d)', !!$args[0], !!$args[1]);
        }

        protected static function _not($args) {
            return '!' . $args[0];
        }
        
        protected static function _lt($args) {
            return '(' . implode(' < ', $args) . ')';
        }
        
        protected static function _gt($args) {
            return '(' . implode(' > ', $args) . ')';
        }
        
        protected static function _eq($args) {
            return '(' . implode(' == ', $args) . ')';
        }
        
        protected static function _le($args) {
            return '(' . implode(' <= ', $args) . ')';
        }
        
        protected static function _ge($args) {
            return '(' . implode(' >= ', $args) . ')';
        }
        
        protected static function _ne($args) {
            return '(' . implode(' == ', $args) . ')';
        }
        
        protected static function _bool($args) {
            return '((bool)' . $args[0] . ')';
        }
        
        protected static function _int($args) {
            return '((int)' . $args[0] . ')';
        }
        
        protected static function _float($args) {
            return '((float)' . $args[0] . ')';
        }
        
        protected static function _string($args) {
            return '((string)' . $args[0] . ')';
        }
        
        protected static function _now() {
            return '(time())';
        }
        
        protected static function _uniqid() {
            return '(uniqid(mt_rand()))';
        }
        
        protected static function _let($args) {
            return '(' . implode(' = ', $args) . ')';
        }
        
        protected static function _dump($args) {
            return '$this->dump(' . implode('', $args) . ')';
        }
        
        protected static function _error($args) {
            return '$this->error(' . implode(', ', $args) . ', __LINE__)';
        }
        
        protected static function _include($args) {
            return '$this->includetpl(' . implode('', $args) . ')';
        }
    }
}
