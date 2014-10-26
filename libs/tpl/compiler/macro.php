<?php

/*
 * This file is part of the 'octris/core' package.
 *
 * (c) Harald Lapp <harald@octris.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace octris\core\tpl\compiler;

/**
 * Library for handling template macros.
 *
 * @octdoc      c:compiler/macro
 * @copyright   copyright (c) 2010-2011 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
class macro
{
    /**
     * Macro registry.
     *
     * @octdoc  p:macro/$registry
     * @type    array
     */
    protected static $registry = array();
    /**/

    /**
     * Last error occured.
     *
     * @octdoc  p:macro/$last_error
     * @type    string
     */
    protected static $last_error = '';
    /**/

    /**
     * Constructor and clone magic method are protected to prevent instantiating of class.
     *
     * @octdoc  m:macro/__construct, __clone
     */
    protected function __construct() {}
    protected function __clone() {}
    /**/

    /**
     * Return last occured error.
     *
     * @octdoc  m:macro/getError
     * @return  string                  Last occured error.
     */
    public static function getError()
    {
        return self::$last_error;
    }

    /**
     * Set error.
     *
     * @octdoc  m:macro/setError
     * @param   string      $name       Name of macro the error occured for.
     * @param   string      $msg        Additional error message.
     */
    protected static function setError($name, $msg)
    {
        self::$last_error = sprintf('"%s" -- %s', $name, $msg);
    }

    /**
     * Register a macro.
     *
     * @octdoc  m:macro/registerMacro
     * @param   string      $name       Name of macro to register.
     * @param   callable    $callback   Callback to call when macro is requested.
     * @param   array       $args       For testing min/max number of arguments required for macro.
     */
    public static function registerMacro($name, callable $callback, array $args)
    {
        self::$registry[strtolower($name)] = array(
            'callback' => $callback,
            'args'     => array_merge(array('min' => 0, 'max' => 0), $args)
        );
    }

    /**
     * Execute specified macro with specified arguments.
     *
     * @octdoc  m:macro/execMacro
     * @param   string      $name       Name of macro to execute.
     * @param   array       $args       Arguments for macro.
     * @param   array       $options    Optional additional options for macro.
     */
    public static function execMacro($name, $args, array $options = array())
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
 * register "import", "uniqid" macro
 */
macro::registerMacro(
    'import',
    function ($args, array $options = array()) {
        $ret = '';
        $err = '';

        $c = clone($options['compiler']);

        if (($file = $c->findFile($args[0])) !== false) {
            $ret = $c->process($file, $options['escape']);
        } else {
            $err = sprintf(
                'unable to locate file "%s" in "%s"',
                $args[0],
                implode(':', \octris\core\tpl\compiler\searchpath::getPath())
            );
        }

        return array($ret, $err);
    },
    array('min' => 1, 'max' => 1)
);
macro::registerMacro(
    'uniqid',
    function ($args, array $options = array()) {
        return uniqid(mt_rand());
    },
    array('min' => 0, 'max' => 0)
);

