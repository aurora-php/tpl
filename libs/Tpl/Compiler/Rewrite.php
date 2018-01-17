<?php

/*
 * This file is part of the 'octris/tpl' package.
 *
 * (c) Harald Lapp <harald@octris.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Octris\Tpl\Compiler;

/**
 * Rewrite template code. Rewrite inline function calls and rewrite function calls according to
 * if they are allowed php function calls or calls to functions that have to be registered to
 * sandbox on template rendering.
 *
 * @copyright   copyright (c) 2010-2018 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
class Rewrite
{
    /**
     * Inline method rewrite.
     *
     * @type    array
     */
    protected static $inline = array(
        // blocks
        '#bench'    => array('min' => 1, 'max' => 1),
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
        'in'        => array('min' => 2, 'max' => 2),

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

    /**
     * Allowed PHP functions and optional mapping to an PHP or framework internal name.
     *
     * @type    array
     */
    protected static $phpfunc = array(
        // string functions
        'chunk'          => array('min' => 3, 'max' => 3, 'map' => '\Octris\Core\Type\Text::chunk_split'),
        'chunk_id'       => array('min' => 1, 'max' => 5, 'map' => '\Octris\Core\Type\Text::chunk_id'),
        'cut'            => array('min' => 2, 'max' => 4, 'map' => '\Octris\Core\Type\Text::cut'),
        'escapeshellarg' => array('min' => 1, 'max' => 1, 'map' => 'escapeshellarg'),
        'lcfirst'        => array('min' => 1, 'max' => 1, 'map' => '\Octris\Core\Type\Text::lcfirst'),
        'length'         => array('min' => 1, 'max' => 1, 'map' => 'strlen'),
        'ltrim'          => array('min' => 1, 'max' => 2, 'map' => '\Octris\Core\Type\Text::ltrim'),
        'obliterate'     => array('min' => 2, 'max' => 4, 'map' => '\Octris\Core\Type\Text::obliterate'),
        'repeat'         => array('min' => 2, 'max' => 2, 'map' => 'str_repeat'),
        'replace'        => array('min' => 3, 'max' => 3, 'map' => '\Octris\Core\Type\Text::str_replace'),
        'rtrim'          => array('min' => 1, 'max' => 2, 'map' => '\Octris\Core\Type\Text::rtrim'),
        'shorten'        => array('min' => 1, 'max' => 3, 'map' => '\Octris\Core\Type\Text::shorten'),
        'sprintf'        => array('min' => 1,             'map' => '\Octris\Core\Type\Text::sprintf'),
        'substr'         => array('min' => 2, 'max' => 3, 'map' => '\Octris\Core\Type\Text::substr'),
        'tolower'        => array('min' => 1, 'max' => 1, 'map' => '\Octris\Core\Type\Text::strtolower'),
        'toupper'        => array('min' => 1, 'max' => 1, 'map' => '\Octris\Core\Type\Text::strtoupper'),
        'trim'           => array('min' => 1, 'max' => 2, 'map' => '\Octris\Core\Type\Text::trim'),
        'ucfirst'        => array('min' => 1, 'max' => 1, 'map' => '\Octris\Core\Type\Text::ucfirst'),
        'vsprintf'       => array('min' => 2, 'max' => 2, 'map' => '\Octris\Core\Type\Text::vsprintf'),

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

    /**
     * Forbidden function names.
     *
     * @type    array
     */
    protected static $forbidden = array(
        'setvalue', 'setvalues', 'foreach', 'bufferstart', 'bufferend', 'cron', 'loop', 'onchange', 'trigger',
        '__construct', '__call', 'registermethod', 'render', 'write'
    );

    /**
     * Last error occured.
     *
     * @type    string
     */
    protected static $last_error = '';

    /**
     * Constructor and clone magic method are protected to prevent instantiating of class.
     */
    protected function __construct()
    {
    }
    protected function __clone()
    {
    }

    /**
     * Return last occured error.
     *
     * @return  string                  Last occured error.
     */
    public static function getError()
    {
        return self::$last_error;
    }

    /**
     * Set error.
     *
     * @param   string      $name       Name of method the error occured for.
     * @param   string      $msg        Additional error message.
     */
    protected static function setError($name, $msg)
    {
        self::$last_error = sprintf('"%s" -- %s', $name, $msg);
    }

    /**
     * Wrapper for methods that can be rewritten.
     *
     * @param   string      $name       Name of method to rewrite.
     * @param   array       $args       Arguments for method.
     */
    public static function __callStatic($name, $args)
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

            if (substr($name, 0, 1) == '#') {
                $name = 'block' . ucfirst(substr($name, 1));
            } else {
                $name = 'func' . ucFirst($name);
            }

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
     * @return  string                  Uniq identifier
     */
    protected static function getUniqId()
    {
        return md5(uniqid());
    }
}
