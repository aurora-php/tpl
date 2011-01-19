<?php

namespace org\octris\core\tpl\compiler {
    /**
     * Rewrite template code. Rewrite inline function calls and rewrite function calls according to
     * if they are allowed php function calls or calls to functions that have to be registered to 
     * sandbox on template rendering.
     *
     * @octdoc      c:compiler/rewrite
     * @copyright   copyright (c) 2010-2011 by Harald Lapp
     * @author      Harald Lapp <harald@octris.org>
     */
    class rewrite
    /**/
    {
        /**
         * Inline method rewrite.
         *
         * @octdoc  v:rewrite/$inline
         * @var     array
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

            'bool'       => array('min' => 1, 'max' => 1),  // (bool)...
            'int'        => array('min' => 1, 'max' => 1),  // (int)...
            'float'      => array('min' => 1, 'max' => 1),  // (float)...
            'string'     => array('min' => 1, 'max' => 1),  // (string)...
            'collection' => array('min' => 1, 'max' => 1),

            'now'       => array('min' => 0, 'max' => 0),
            'uniqid'    => array('min' => 0, 'max' => 0),
            'let'       => array('min' => 2, 'max' => 2),
            'dump'      => array('min' => 1, 'max' => 1),
            'error'     => array('min' => 1, 'max' => 1),
            
            'include'   => array('min' => 1, 'max' => 1),
            
            // string functions
            'explode'   => array('min' => 2, 'max' => 2),
            'implode'   => array('min' => 2, 'max' => 2),
            'lpad'      => array('min' => 2, 'max' => 3),
            'rpad'      => array('min' => 2, 'max' => 3),
            'concat'    => array('min' => 2),
        );
        /**/

        /**
         * Allowed PHP functions and optional mapping to an template engine internal name.
         *
         * @octdoc  v:rewrite/$phpfunc
         * @var     array
         */
        protected static $phpfunc = array(
            // string functions
            'chunk'      => array('min' => 3, 'max' => 3, 'map' => 'chunk_split'),
            'count'      => array('min' => 1, 'max' => 1),
            'ltrim'      => array('min' => 1, 'max' => 2),
            'repeat'     => array('min' => 2, 'max' => 2, 'map' => 'str_repeat'),
            'replace'    => array('min' => 3, 'max' => 3, 'map' => 'str_replace'),
            'rtrim'      => array('min' => 1, 'max' => 2),
            'sprintf'    => array('min' => 1),
            'tolower'    => array('min' => 1, 'max' => 1, 'map' => 'strtolower'),
            'toupper'    => array('min' => 1, 'max' => 1, 'map' => 'strtoupper'),
            'substr'     => array('min' => 2, 'max' => 3),
            'trim'       => array('min' => 1, 'max' => 2),
            'vsprintf'   => array('min' => 2, 'max' => 2),
            
            // numeric functions
            'round'      => array('min' => 1, 'max' => 2),
            'ceil'       => array('min' => 1, 'max' => 1),
        );
        /**/

        /**
         * Forbidden function names.
         *
         * @octdoc  v:rewrite/$forbidden
         * @var     array
         */
        protected static $forbidden = array(
            'setvalue', 'setvalues', 'each', 'bufferstart', 'bufferend', 'cache', 'cron', 'loop', 'onchange', 'trigger',
            '__construct', '__call', 'registermethod', 'render'
        );
        /**/
        
        /**
         * Last error occured.
         *
         * @octdoc  v:rewrite/$last_error
         * @var     string
         */
        protected static $last_error = '';
        /**/

        /**
         * Constructor and clone magic method are protected to prevent instantiating of class.
         *
         * @octdoc  m:rewrite/__construct, __clone
         */
        protected function __construct() {}
        protected function __clone() {}
        /**/
        
        /**
         * Return last occured error.
         *
         * @octdoc  m:rewrite/getError
         * @return  string                  Last occured error.
         */
        public static function getError()
        /**/
        {
            return self::$last_error;
        }

        /**
         * Set error.
         *
         * @octdoc  m:rewrite/setError
         * @param   string      $name       Name of constant the error occured for.
         * @param   string      $msg        Additional error message.
         */
        protected static function setError($name, $msg)
        /**/
        {
            self::$last_error = sprintf('"%s" -- %s', $name, $msg);
        }

        /**
         * Wrapper for methods that can be rewritten.
         *
         * @octdoc  m:rewrite/__callStatic
         * @param   string      $name       Name of method to rewrite.
         * @param   array       $args       Arguments for method.
         */
        public static function __callStatic($name, $args)
        /**/
        {
            self::$last_error = '';
           
            $name = strtolower($name);
            $args = $args[0];
            
            if (in_array($name, self::$forbidden)) {
                self::setError($name, 'access denied');
            } elseif (isset(self::$phpfunc[$name])) {
                // call to allowed PHP function
                $cnt = count($args);
                
                if (isset(self::$phpfunc[$name]['min'])) {
                    if ($cnt < self::$phpfunc[$name]['min']) {
                        self::setError($name, 'not enough arguments');
                    }
                }
                if (isset(self::$phpfunc[$name]['max'])) {
                    if ($cnt > self::$phpfunc[$name]['max']) {
                        self::setError($name, 'too many arguments');
                    }
                }
                
                if (isset(self::$phpfunc[$name]['map'])) {
                    // resolve 'real' PHP method name
                    $name = self::$phpfunc[$name]['map'];
                }
                
                return $name . '(' . implode(', ', $args) . ')';
            } elseif (isset(self::$inline[$name])) {
                // inline function rewrite
                $cnt = count($args);
                
                if (isset(self::$inline[$name]['min'])) {
                    if ($cnt < self::$inline[$name]['min']) {
                        self::setError($name, 'not enough arguments');
                    }
                }
                if (isset(self::$inline[$name]['max'])) {
                    if ($cnt > self::$inline[$name]['max']) {
                        self::setError($name, 'too many arguments');
                    }
                }
                
                $name = '_' . str_replace('#', '_', $name);

                return self::$name($args);
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
        
        /**
         * Helper function to create a uniq identifier required by several functions.
         *
         * @octdoc  m:rewrite/getUniqId
         * @return  string                  Uniq identifier
         */
        protected static function getUniqId()
        /**/
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
        
        protected static function _collection($args) {
            return '\\org\\octris\\core\\tpl\\type::settype(' . $args[0] . ', "collection")';
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
        
        // string functions
        protected static function _explode($args) {
            return 'new \\org\\octris\\core\\tpl\\type\\collection(explode(' . implode(', ', $args) . '))';
        }
        
        protected static function _implode($args) {
            return '(implode(' . $args[0] . ', \\org\\octris\\core\\tpl\\type::settype(' . $args[1] . ', "array")))';
        }
        
        protected static function _lpad($args) {
            $args = $args + array(null, null, ' ');
            
            return '(str_pad(' . implode(', ', $args) . ', STR_PAD_LEFT))';
        }
        
        protected static function _rpad($args) {
            $args = $args + array(null, null, ' ');
            
            return '(str_pad(' . implode(', ', $args) . ', STR_PAD_RIGHT))';
        }
        
        protected static function _concat($args) {
            return '(' . implode(' . ', $args) . ')';
        }
    }
}
