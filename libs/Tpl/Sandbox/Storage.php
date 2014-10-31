<?php

/*
 * This file is part of the 'octris/core' package.
 *
 * (c) Harald Lapp <harald@octris.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Octris\Core\Tpl\Sandbox;

/**
 * Object storage for sandbox.
 *
 * @copyright   copyright (c) 2012-2014 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
class Storage
{
    /**
     * Storage for initialization callbacks.
     *
     * @type    array
     */
    private $init = array();
    
    /**
     * Instance of storage class.
     *
     * @type    \octris\core\tpl\sandbox\storage|null
     */
    private static $instance = null;
    
    /**
     * Private constructor, clone method, to make the class singleton.
     *
     */
    private function __construct()
    {
    }
    private function __clone()
    {
    }
    
    /**
     * Get instance of storage class.
     *
     * @return  \octris\core\tpl\sandbox\storage                Instance of storage class.
     */
    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Getter.
     *
     * @param   string                  $name                       Name of property to get.
     * @return  mixed                                               Data stored in property.
     */
    public function __get($name)
    {
        if (!isset($this->init[$name])) {
            throw new \Exception('Unknown property "' . $name . '".');
        }

        $cb = $this->init[$name];

        return ($this->{$name} = $cb());
    }

    /**
     * Get a value from storage. Generate it using the specified callback, if the value does not exist.
     *
     * @param   string                  $name                       Name of data to store in storage.
     * @param   callable                $cb                         Callback to call if data is not available.
     * @return  mixed                                               Data.
     */
    public function get($name, callable $cb)
    {
        if (!$name == 'instance' || $name == 'init') {
            throw new \Exception('Forbidden to access property "' . $name . '".');
        }

        $this->init[$name] = $cb;

        return $this->{$name};
    }
}
