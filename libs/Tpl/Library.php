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
        'block' => [ 'if', 'else', 'end', 'foreach', 'for' ],
        'function' => [],
        'macro' => []
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
        } elseif ($extension instanceof Octris\Tpl\Extension\Fun) {
            $type = 'function';
        } elseif ($extension instanceof Octris\Tpl\Extension\Macro) {
            $type = 'macro';
        } else {
            throw new \InvalidArgumentException('Unknown extension type');
        }

        $name = $extension->getName();

        if (in_array($this->reserved[$type])) {
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
     * Format error message.
     * 
     * @param   string      $name       Name.
     * @param   string      $msg        Message.
     * @return  string
     */
    protected function formatError($name, $msg)
    {   
        return sprintf('"%s" -- %s', $name, $msg);
    }

    /**
     * Execute specified macro with specified arguments.
     *
     * @param   string      $name       Name of macro to execute.
     * @param   array       $args       Arguments for macro.
     * @param   array       $options    Optional additional options for macro.
     */
    public function execMacro($name, $args, array $options = array())
    {
        $name = strtolower($name);
        $error = null;
        $return = '';

        if (!isset($this->extensions['macro'][$name])) {
            $error = $this->formatError($name, 'unknown macro');
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

    /**
     * Return value of a defined constant.
     *
     * @param   string      $name               Name of constant.
     * @param   array       $env                Compiler environment.
     * @return  array                           [value, error]
     */
    public function getConstant($name, array $env)
    {
        $name = strtoupper($name);
        $value = null;
        $error = false;

        if (!isset($this->constants[$name])) {
            $error = $name . ' -- unknown constant'
        } else {
            $value = $this->constants[$name];
        }
        
        return [$value, $error];
    }
}
