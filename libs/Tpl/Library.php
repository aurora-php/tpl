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
 * @copyright   copyright (c) 2018-present by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
final class Library
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
    ];

    /**
     * Reserved names.
     *
     * @type    array
     */
    protected $reserved = [
        'block' => [ 'else', 'end' ],
        'function' => [ 'createFor', 'createForeach', 'write' ],
        'macro' => []
    ];

    /**
     * Constructor.
     *
     * @param   \Octris\Tpl\Extension\AbstractBundle    $bundle             Default bundle.
     */
    public function __construct(\Octris\Tpl\Extension\AbstractBundle $bundle = null)
    {
        if (!is_null($bundle)) {
            $this->addExtensionBundle($bundle);
        }
    }

    /**
     * Add a single extension.
     *
     * @param   \Octris\Tpl\Extension\AbstractExtension $extension          A single extension to add.
     */
    public function addExtension(\Octris\Tpl\Extension\AbstractExtension $extension)
    {
        if ($extension instanceof \Octris\Tpl\Extension\Block) {
            $type = 'block';
        } elseif ($extension instanceof \Octris\Tpl\Extension\Fun) {
            $type = 'function';
        } elseif ($extension instanceof \Octris\Tpl\Extension\Macro) {
            $type = 'macro';
        } else {
            throw new \InvalidArgumentException('Unknown extension type for "' . get_class($extension) . '"');
        }

        $name = $extension->getName();

        if (in_array($type, $this->reserved)) {
            throw new \InvalidArgumentException('The name "' . $name . '" is a reserved word for type ' . $type);
        } elseif (!isset($this->extensions[$type][$name]) || !$this->extensions[$type][$name]->isFinal()) {
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
     * Return code for specified arguments.
     *
     * @param   string          $type               Type of extension to access.
     * @param   string          $name               Name of extension to access.
     * @param   array           $args               Arguments for extension.
     * @param   array           $env                Compiler environment for extension.
     */
    public function getCode($type, $name, array $args, array $env)
    {
        $type = strtolower($type);
        $name = strtolower($name);
        $ret = [ null, null ];

        if (!isset($this->extensions[$type][$name])) {
            if ($type != 'function') {
                throw new \Exception('Unknown ' . $type . ' "' . $name . '"');
            } else {
                // function may be undefined at compile time
                $ret[0] = '($this->registry[\'' . $name . '\'](' . implode(', ', $args) . '))';
            }
        } else {
            $extension = $this->extensions[$type][$name];

            list($min, $max) = $extension->getNumberOfParameters();

            if ($max > 0 && count($args) > $max) {
                throw new \Exception('Too many arguments "' . $name . '"');
            } elseif (count($args) < $min) {
                throw new \Exception('Not enough arguments "' . $name . '"');
            } else {
                $ret = $extension->getCode($args, $env);
            }
        }

        return $ret;
    }

    /**
     * Return value of a defined constant.
     *
     * @param   string      $name               Name of constant.
     * @return  string                          value.
     */
    public function getConstant($name)
    {
        $name = strtoupper($name);
        $value = null;
        $error = false;

        if (!isset($this->constants[$name])) {
            throw new \Exception('Unknown constant "' . $name . '"');
        } else {
            $value = $this->constants[$name];
        }

        return $value;
    }
}
