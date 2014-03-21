<?php

/*
 * This file is part of the 'org.octris.core' package.
 *
 * (c) Harald Lapp <harald@octris.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
         * @octdoc  p:rewrite/$inline
         * @type    array
         */
        protected static $inline = array(
            // blocks
            '#bench'    => array('min' => 1, 'max' => 1),
            '#cache'    => array('min' => 2, 'max' => 3),
            '#copy'     => array('min' => 1, 'max' => 1),
            '#cron'     => array('min' => 1, 'max' => 2),
            '#cut'      => array('min' => 1, 'max' => 1),
            '#if'       => array('min' => 1, 'max' => 1),
            '#foreach'  => array('min' => 2, 'max' => 3),
            '#loop'     => array('min' => 4, 'max' => 5),
            '#onchange' => array('min' => 1, 'max' => 1),
            '#trigger'  => array('min' => 0, 'max' => 3),
            
            // functions
            'if'     => array('min' => 2, 'max' => 3),   // (... ? ... : ...)
            'ifset'  => array('min' => 2, 'max' => 3),   // (isset(...) ? ... : ...)
            'ifnull' => array('min' => 2, 'max' => 3),   // (is_null(...) ? ... : ...)
                     
            'mul'    => array('min' => 2),               // ... * ...
            'div'    => array('min' => 2),               // ... / ...
            'mod'    => array('min' => 2, 'max' => 2),   // ... % ...
            'add'    => array('min' => 2),               // ... + ...
            'sub'    => array('min' => 2),               // ... - ...
            'incr'   => array('min' => 1, 'max' => 2),   // ++ / +=
            'decr'   => array('min' => 1, 'max' => 2),   // -- / -=
            'neg'    => array('min' => 1, 'max' => 1),   // -...
                     
            'and'    => array('min' => 2),               // ... && ...
            'or'     => array('min' => 2),               // ... || ...
            'xor'    => array('min' => 2, 'max' => 2),   // ... xor ...
            'not'    => array('min' => 1, 'max' => 1),   // !...
                     
            'lt'     => array('min' => 2, 'max' => 2),   // ... < ...
            'gt'     => array('min' => 2, 'max' => 2),   // ... > ...
            'eq'     => array('min' => 2, 'max' => 2),   // ... == ...
            'le'     => array('min' => 2, 'max' => 2),   // ... <= ...
            'ge'     => array('min' => 2, 'max' => 2),   // ... >= ...
            'ne'     => array('min' => 2, 'max' => 2),   // ... != ...
                     
            'bool'       => array('min' => 1, 'max' => 1),  // (bool)...
            'int'        => array('min' => 1, 'max' => 1),  // (int)...
            'float'      => array('min' => 1, 'max' => 1),  // (float)...
            'string'     => array('min' => 1, 'max' => 1),  // (string)...
            'collection' => array('min' => 1, 'max' => 1),

            'now'       => array('min' => 0, 'max' => 0),
            'uniqid'    => array('min' => 0, 'max' => 0),
            'let'       => array('min' => 2, 'max' => 2),
            'ddump'     => array('min' => 1),
            'dprint'    => array('min' => 1),
            'error'     => array('min' => 1, 'max' => 1),
            
            'include'   => array('min' => 1, 'max' => 1),
            
            // string functions
            'explode'   => array('min' => 2, 'max' => 2),
            'implode'   => array('min' => 2, 'max' => 2),
            'lpad'      => array('min' => 2, 'max' => 3),
            'rpad'      => array('min' => 2, 'max' => 3),
            'totitle'   => array('min' => 1, 'max' => 1),
            'concat'    => array('min' => 2),
            
            // array functions
            'array'     => array('min' => 1),
            'cycle'     => array('min' => 1, 'max' => 3),

            // misc functions
            'escape'    => array('min' => 2, 'max' => 2),

            // localisation functions
            'comify'    => array('min' => 2, 'max' => 3),
            'enum'      => array('min' => 2),
            'monf'      => array('min' => 1, 'max' => 2),
            'numf'      => array('min' => 1, 'max' => 2),
            'perf'      => array('min' => 1, 'max' => 2),
            'datef'     => array('min' => 1, 'max' => 2),
            'gender'    => array('min' => 4, 'max' => 4),
            'quant'     => array('min' => 2, 'max' => 4),
            'yesno'     => array('min' => 2, 'max' => 3),
        );
        /**/

        /**
         * Allowed PHP functions and optional mapping to an PHP or framework internal name.
         *
         * @octdoc  p:rewrite/$phpfunc
         * @type    array
         */
        protected static $phpfunc = array(
            // string functions
            'chunk'          => array('min' => 3, 'max' => 3, 'map' => '\org\octris\core\type\string::chunk_split'),
            'chunk_id'       => array('min' => 1, 'max' => 5, 'map' => '\org\octris\core\type\string::chunk_id'),
            'cut'            => array('min' => 2, 'max' => 4, 'map' => '\org\octris\core\type\string::cut'),
            'escapeshellarg' => array('min' => 1, 'max' => 1, 'map' => 'escapeshellarg'),
            'lcfirst'        => array('min' => 1, 'max' => 1, 'map' => '\org\octris\core\type\string::lcfirst'),
            'ltrim'          => array('min' => 1, 'max' => 2, 'map' => '\org\octris\core\type\string::ltrim'),
            'obliterate'     => array('min' => 2, 'max' => 4, 'map' => '\org\octris\core\type\string::obliterate'),
            'repeat'         => array('min' => 2, 'max' => 2, 'map' => 'str_repeat'),
            'replace'        => array('min' => 3, 'max' => 3, 'map' => '\org\octris\core\type\string::str_replace'),
            'rtrim'          => array('min' => 1, 'max' => 2, 'map' => '\org\octris\core\type\string::rtrim'),
            'shorten'        => array('min' => 1, 'max' => 3, 'map' => '\org\octris\core\type\string::shorten'),
            'sprintf'        => array('min' => 1,             'map' => '\org\octris\core\type\string::sprintf'),
            'substr'         => array('min' => 2, 'max' => 3, 'map' => '\org\octris\core\type\string::substr'),
            'tolower'        => array('min' => 1, 'max' => 1, 'map' => '\org\octris\core\type\string::strtolower'),
            'toupper'        => array('min' => 1, 'max' => 1, 'map' => '\org\octris\core\type\string::strtoupper'),
            'trim'           => array('min' => 1, 'max' => 2, 'map' => '\org\octris\core\type\string::trim'),
            'ucfirst'        => array('min' => 1, 'max' => 1, 'map' => '\org\octris\core\type\string::ucfirst'),
            'vsprintf'       => array('min' => 2, 'max' => 2, 'map' => '\org\octris\core\type\string::vsprintf'),
            
            // numeric functions
            'abs'        => array('min' => 1, 'max' => 1),
            'ceil'       => array('min' => 1, 'max' => 1),
            'floor'      => array('min' => 1, 'max' => 1),
            'max'        => array('min' => 2),
            'min'        => array('min' => 2),
            'round'      => array('min' => 1, 'max' => 2),

            // array functions
            'count'      => array('min' => 1, 'max' => 1),

            // misc functions
            'isset'      => array('min' => 1, 'max' => 1),
            'jsonencode' => array('min' => 1, 'max' => 2, 'map' => 'json_encode'),
            'jsondecode' => array('min' => 1, 'max' => 4, 'map' => 'json_decode'),
        );
        /**/

        /**
         * Forbidden function names.
         *
         * @octdoc  p:rewrite/$forbidden
         * @type    array
         */
        protected static $forbidden = array(
            'setvalue', 'setvalues', 'each', 'bufferstart', 'bufferend', 'cache', 'cron', 'loop', 'onchange', 'trigger',
            '__construct', '__call', 'registermethod', 'render', 'write'
        );
        /**/
        
        /**
         * Last error occured.
         *
         * @octdoc  p:rewrite/$last_error
         * @type    string
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
         * @param   string      $name       Name of method the error occured for.
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
                
                $name = '_' . str_replace('#', 'block_', $name);

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

        /**
         * Implementation of gettext compiler.
         *
         * @octdoc  m:rewrite/_gettext
         * @param   \org\octris\core\l10n       $l10n           Instance of l10n class.
         * @param   string                      $domain         Text domain to use.
         * @param   string                      $msg            Message to translate.
         * @param   array                       $args           Parameters for inline functions.
         * @return  string                                      Compiled code for gettext.
         */
        public static function gettext($l10n, $domain, $msg, $args)
        /**/
        {
            self::$last_error = '';
           
            $fn = array('comify', 'enum', 'monf', 'numf', 'perf', 'datef', 'gender', 'quant', 'yesno');
            
            if (preg_match('/^(["\'])(.*?)\1$/', $msg, $match)) {
                $pattern = '/\[(?:(?P<cmd>[a-z]+), *)?_(?P<arg>\d+)(?:, *(?P<str>.*?))?(?<!\\\)\]/s';

                $chr = $match[1];                           // quotation character
                $txt = $l10n->lookup($match[2], $domain);   // get translated text
                
                $txt = $chr . addcslashes($txt, ($chr == '"' ? '"' : "'")) . $chr;
                
                try {
                    $txt = preg_replace_callback($pattern, function($m) use ($args, $chr, $fn) {
                        $cmd = (isset($m['cmd']) ? $m['cmd'] : '');
                        $arg = $m['arg'];

                        if ($arg > count($args)) {
                            self::setError('gettext', sprintf('argument "%d" is not defined', $arg));
                            throw new \Exception(self::getError());
                        }

                        if ($cmd != '') {
                            if (!in_array($cmd, $fn)) {
                                self::setError('gettext', sprintf('unknown function "%s"', $cmd));
                                throw new \Exception(self::getError());
                            }
                        
                            $tmp = array_map(function($arg) use ($chr) {
                                return $chr . trim($arg) . $chr;
                            }, (isset($m['str']) ? preg_split('/(?<!\\\),/', $m['str']) : array()));

                            $code = $chr . ' . ' . 
                                    self::$cmd(array_merge(array($args[$arg - 1]), $tmp)) . 
                                    ' . ' . $chr;
                            
                            if (self::getError() != '') {
                                throw new \Exception(self::getError());
                            }
                        } else {
                            $code = $chr . ' . ' . $args[$arg - 1] . ' . ' . $chr;
                        }

                        return $code;
                    }, $txt, -1, $cnt);
                } catch(\Exception $e) {
                }
                
                $return = $txt;
            } else {
                $return = '$this->l10n->translate(' . $msg . ', array(), ' . $domain . ')';
            }
            
            return $return;
        }
        
        /*
         * inline block functions, that can be converted directly
         */
        protected static function _block_bench($args) {
            $var1 = '$_' . self::getUniqId();
            $var2 = '$_' . self::getUniqId();

            return array(
                sprintf(
                    '%s = microtime(true); ' .
                    'for (%s = 0; %s < abs(%s); ++%s) { ' .
                    'if (%s == 1) ob_start();',
                    $var1,
                    $var2, $var2, $args[0], $var2,
                    $var2
                ),
                sprintf(
                    '} %s = microtime(true) - %s; ' .
                    'if (abs(%s) > 0) ob_end_clean(); ' .
                    'printf("[benchmark iterations: %%s, time: %%1.6f]", abs(%s), %s);',
                    $var1, $var1, $args[0], $args[0], $var1
                )
            );
        }

        protected static function _block_cache($args) {
            $var = '$_' . self::getUniqId();
            $key = $args[0];
            $ttl = $args[1];
            $esc = (isset($args[2]) ? $args[2] : \org\octris\core\tpl::T_ESC_NONE);

            return array(
                sprintf(
                    'if (!$this->cacheLookup(%s, "%s")) { $this->bufferStart(%s, false);',
                    $key, $esc, $var
                ),
                sprintf(
                    '$this->bufferEnd(); $this->cacheStore(%s, %s, %s); }', 
                    $key, $var, $ttl
                )
            );
        }
        
        protected static function _block_copy($args) {
            return array(
                '$this->bufferStart(' . implode(', ', $args) . ', false);', 
                '$this->bufferEnd();'
            );
        }
        
        protected static function _block_cron($args) {
            return array(
                'if ($this->cron(' . implode(', ', $args) . ')) {',
                '}'
            );
        }
        
        protected static function _block_cut($args) {
            return array(
                '$this->bufferStart(' . implode(', ', $args) . ', true);', 
                '$this->bufferEnd();'
            );
        }
        
        protected static function _block_foreach($args) {
            $var = self::getUniqId();
            $arg = $args[1];
            unset($args[1]);

            return array(
                sprintf(
                    '$_%s = $this->storage->get("_%s", function() { ' .
                    'return new \org\octris\core\tpl\sandbox\eachiterator(%s);' . 
                    '}); ' .
                    'while ($this->each($_%s, ' . implode(', ', $args) . ')) {',
                    $var, $var, $arg, $var
                ),
                '}'
            );
        }
        
        protected static function _block_if($args) {
            return array(
                'if (' . implode('', $args) . ') {',
                '}'
            );
        }

        protected static function _block_loop($args) {
            $var = self::getUniqId();
            
            $start = $args[1];
            $end   = $args[2];
            $step  = $args[3];

            unset($args[1]);
            unset($args[2]);
            unset($args[3]);

            return array(
                sprintf(
                    '$_%s = $this->storage->get("_%s", function() { ' .
                    'return new \org\octris\core\tpl\sandbox\eachiterator(' .
                    'new \ArrayIterator(array_slice(range(%s, %s, %s), 0, -1))' .
                    '); }); ' . 
                    'while ($this->each($_%s, ' . implode(', ', $args) . ')) {',
                    $var, $var, $start, $end, $step, $var
                ),
                '}'
            );
        }

        protected static function _block_onchange($args) {
            return array(
                'if ($this->onchange("' . self::getUniqId() . '", ' . implode(', ', $args) . ')) {',
                '}'
            );
        }
        
        protected static function _block_trigger($args) {
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
                (count($args) == 3 ? $args[2] : '')
            );
        }
        
        protected static function _ifset($args) {
            return sprintf(
                '(isset(%s) ? %s : %s)',
                $args[0],
                $args[1],
                (count($args) == 3 ? $args[2] : '')
            );
        }
        
        protected static function _ifnull($args) {
            return sprintf(
                '(is_null(%s) ? %s : %s)',
                $args[0],
                $args[1],
                (count($args) == 3 ? $args[2] : '')
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
            return '(' . implode(' != ', $args) . ')';
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
            return '\\org\\octris\\core\\type::settype(' . $args[0] . ', "collection")';
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
        
        protected static function _ddump($args) {
            return '\\org\\octris\\core\\debug::ddump(' . implode(', ', $args) . ')';
        }
        
        protected static function _dprint($args) {
            return '\\org\\octris\\core\\debug::dprint(' . implode(', ', $args) . ')';
        }
        
        protected static function _error($args) {
            return '$this->error(' . implode(', ', $args) . ', __LINE__)';
        }
        
        protected static function _include($args) {
            return '$this->includetpl(' . implode('', $args) . ')';
        }
        
        // string functions
        protected static function _explode($args) {
            return 'new \\org\\octris\\core\\type\\collection(explode(' . implode(', ', $args) . '))';
        }
        
        protected static function _implode($args) {
            return '(implode(' . $args[0] . ', \\org\\octris\\core\\type::settype(' . $args[1] . ', "array")))';
        }
        
        protected static function _lpad($args) {
            $args = $args + array(null, null, ' ');
            
            return '(str_pad(' . implode(', ', $args) . ', STR_PAD_LEFT))';
        }
        
        protected static function _rpad($args) {
            $args = $args + array(null, null, ' ');
            
            return '(str_pad(' . implode(', ', $args) . ', STR_PAD_RIGHT))';
        }
        
        protected static function _totitle($args) {
            return '\\org\\octris\\core\\type\\string::convert_case(' . $args[0] . ', MB_CASE_TITLE)';
        }

        protected static function _concat($args) {
            return '(' . implode(' . ', $args) . ')';
        }
        
        // array functions
        protected static function _array($args) {
            return 'new \\org\\octris\\core\\type\\collection(array(' . implode(', ', $args) . '))';
        }
        
        protected static function _cycle($args) {
            return '($this->cycle("' . self::getUniqId() . '", ' . implode(', ', $args) . '))';
        }

        // misc functions
        protected static function _escape($args) {
            return '($this->escape(' . implode(', ', $args) . '))';
        }

        // localization functions
        protected static function _comify($args) {
            return '($this->l10n->comify(' . implode(', ', $args) . '))';
        }
        protected static function _enum($args) {
            return '($this->l10n->enum(' . implode(', ', $args) . '))';
        }        
        protected static function _monf($args) {
            return '($this->l10n->monf(' . implode(', ', $args) . '))';
        }
        protected static function _numf($args) {
            return '($this->l10n->numf(' . implode(', ', $args) . '))';
        }
        protected static function _perf($args) {
            return '($this->l10n->perf(' . implode(', ', $args) . '))';
        }
        protected static function _datef($args) {
            return '($this->l10n->datef(' . implode(', ', $args) . '))';
        }
        protected static function _gender($args) {
            return '($this->l10n->gender(' . implode(', ', $args) . '))';
        }
        protected static function _quant($args) {
            return '($this->l10n->quant(' . implode(', ', $args) . '))';
        }
        protected static function _yesno($args) {
            return '($this->l10n->yesno(' . implode(', ', $args) . '))';
        }
    }
}
