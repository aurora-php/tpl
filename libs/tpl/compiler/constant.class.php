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
     * Library for handling template constants.
     *
     * @octdoc      c:compiler/constant
     * @copyright   copyright (c) 2010-2011 by Harald Lapp
     * @author      Harald Lapp <harald@octris.org>
     */
    class constant
    /**/
    {
        /**
         * Constant registry.
         *
         * @octdoc  v:constant/$registry
         * @var     array
         */
        protected static $registry = array();
        /**/

        /**
         * Last occured error.
         *
         * @octdoc  v:constant/$last_error
         * @var     string
         */
        protected static $last_error = '';
        /**/

        /**
         * Constructor and clone magic method are protected to prevent instantiating of class.
         *
         * @octdoc  m:constant/__construct, __clone
         */
        protected function __construct() {}
        protected function __clone() {}
        /**/
        
        /**
         * Return last occured error.
         *
         * @octdoc  m:constant/getError
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
         * @octdoc  m:constant/setError
         * @param   string      $name       Name of constant the error occured for.
         * @param   string      $msg        Additional error message.
         */
        protected static function setError($name, $msg)
        /**/
        {
            self::$last_error = sprintf('"%s" -- %s', $name, $msg);
        }

        /**
         * Set a constant.
         *
         * @octdoc  m:constant/setConstant
         * @param   string      $name       Name of constant to set.
         * @param   mixed       $value      Value of constant.
         */
        public static function setConstant($name, $value)
        /**/
        {
            self::$registry[$name] = $value;
        }

        /**
         * Set multiple constants.
         *
         * @octdoc  m:constant/setConstants
         * @param   array       $array      Key/value array defining constants.
         */
        public static function setConstants($array)
        /**/
        {
            self::$registry[$name] = array_merge(self::$registry[$name], $array);
        }

        /**
         * Return value of a constant. An error will be set if the requested constant is not defined.
         *
         * @octdoc  m:constant/getConstant
         * @param   string      $name       Name of constant to return value of.
         * @return  mixed                   Value of constant.
         */
        public static function getConstant($name)
        /**/
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
