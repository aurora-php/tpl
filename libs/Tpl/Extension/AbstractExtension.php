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
    protected $name = '';
    
    /**
     * Callable rewriting template code.
     * 
     * @type    callable
     */
    protected $fun = null;
    
    /**
     * Constructor.
     * 
     * @param   string              $name               Name to register extension with.
     */
    public function __construct($name)
    {
        $this->name = $name;
        $this->fun = function() { return ''; };
    }
    
    /**
     * Return min,max number of parameters.
     * 
     * @return  array
     */
    public function getNumberOfParameters()
    {
        $ref = new ReflectionFunction($this->rewrite);
        
        $min = $ref->getNumberOfRequiredParameters();
        $max = ($ref->isVariadic() ? -1 : $ref->getNumberOfParameters());
        
        return [$min, $max];
    }
}
