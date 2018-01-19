<?php

/*
 * This file is part of the 'octris/tpl' package.
 *
 * (c) Harald Lapp <harald@octris.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Octris\Tpl\Extension;

/**
 * Extension base class.
 *
 * @copyright   copyright (c) 2018 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
abstract class AbstractExtension
{
    /**
     * Name of extension.
     *
     * @type    string
     */
    private $name = '';

    /**
     * Callback implementing code generator.
     *
     * @type    callable
     */
    private $fn = null;

    /**
     * Extension options.
     *
     * @type    array
     */
    private $options = [];

    /**
     * Constructor.
     *
     * @param   string              $name               Name to register extension with.
     * @param   callable            $fn                 Callback implementing code generator.
     * @param   array               $options            Optional options.
     */
    public function __construct($name, callable $fn, array $options = [])
    {
        $this->name = $name;
        $this->fn = $this->fn;
        $this->options = $options + [ 'final' => false, 'env' => false ];
    }

    /**
     * Return name of extension.
     *
     * @return  string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Return instance for reflection of callable.
     * 
     * @return  \ReflectionFunctionAbstract
     */
    final protected function getReflectionCallable()
    {
        return (is_array($this->fn) 
                    ? new ReflectionMethod($this->fn[0], $this->fn[1]) 
                    : (is_object($this->fn) && is_callable($this->fn, '__invoke')
                        ? new ReflectionMethod($this->fn, '__invoke')
                        : new ReflectionFunction($this->fn)));
    }

    /**
     * Return min,max number of parameters.
     *
     * @return  array
     */
    final public function getNumberOfParameters()
    {
        static $ret = null;
        
        if (is_null($ret)) {
            $ref = $this->getReflectionCallable();

            $min = $ref->getNumberOfRequiredParameters();
            $max = ($ref->isVariadic() ? -1 : $ref->getNumberOfParameters());
            
            $ret = [$min, $max];
        }

        return $ret;
    }
    
    /**
     * Return code generated by extension.
     *
     * @param   array               $args               Extension arguments definition.
     * @param   array               $env                Engine environment.
     * @return  array                                   Generated code.
     */
    abstract public function getCode(array $args, array $env);

    /**
     * Return code-generator callable.
     * 
     * @param   array               $env                Environment to optionally pass to the callable.
     * @return  object
     */
    final protected function getFn(array $env)
    {
        if ($this->options['env']) {
            $return = $this->fn->bindTo(
                new class($env) { 
                    private $env;
            
                    public function __construct($env) 
                    {
                        $this->env = $env;
                    }
                };
            );
        } else {
            $return = $this->fn;
        }

        return $return;
    }

    /**
     * Return if extension is defined as final and cannot be overwritten by an extension
     * of the same name.
     *
     * @return  bool
     */
    public function isFinal()
    {
        return ($this->options['final']);
    }
}
