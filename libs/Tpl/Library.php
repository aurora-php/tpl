<?php

/*
 * This file is part of the 'octris/tpl' package.
 *
 * (c) Harald Lapp <harald@octris.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Octris\Tpl;

/**
 * Language library of the template engine.
 *
 * @copyright   copyright (c) 2018 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
class Library
{
    /**
     * Defined constants.
     * 
     * @type    array
     */
    protected $constants = [];
    
    /**
     * Defined extensions.
     * 
     * @type    array
     */
    protected $extensions = [
        'block' => [],
        'function' => [],
        'macro' => [],
        'structure' => [],
    ];
    
    /**
     * Constructor.
     */
    public function __construct()
    {
    }

    /**
     * Add a single extension.
     *
     * @param   \Octris\Tpl\Extension\AbstractExtension $extension          A single extension to add.
     */
    public function addExtension(\Octris\Tpl\Extension\AbstractExtension $extension)
    {
        if ($extension instanceof Octris\Tpl\Extension\Block) {
            $type = 'block';
        } elseif ($extension instanceof Octris\Tpl\Extension\Control) {
            $type = 'structure';
        } elseif ($extension instanceof Octris\Tpl\Extension\Fun) {
            $type = 'function';
        } elseif ($extension instanceof Octris\Tpl\Extension\Macro) {
            $type = 'macro';
        } else {
            throw new \InvalidArgumentException('Unknown extension type');
        }

        $name = $extension->getName();
        
        if (!isset($this->extensions[$type][$name]) || !$this->extensions[$type][$name]->isFinal()) {
            $this->extensions[$type][$name] = $extension;
        } else {
            throw new \InvalidArgumentException('A ' . $type . ' with the name "' . $name . '" is already defined and marked as final');
        }
    }

    /**
     * Add an extension bundle.
     *
     * @param   \Octris\Tpl\Extension\AbstractBundle    $bundle             Extension bundle to add.
     */
    public function addExtensionBundle(\Octris\Tpl\Extension\AbstractBundle $bundle)
    {
        foreach ($bundle->getExtensions() as $extension) {
            $this->addExtension($extension);
        }

        foreach ($bundle->getConstants() as $name => $value) {
            $name = strtoupper($name);
            
            if (isset($this->constants[$name])) {
                throw new \Exception("Constant '$name' is already defined!");
            } else {
                $this->constants[$name] = $value;
            }
        }
    }

    /**
     * Return value of a defined constant.
     * 
     * @param   string      $name               Name of constant.
     * @return  mixed
     */
    public function getConstant($name)
    {
        $name = strtoupper($name);

        if (!isset($this->constants[$name])) {
            throw new \Exception($name, 'unknown constant');
        } else {
            return $this->constants[$name];
        }
    }
}
