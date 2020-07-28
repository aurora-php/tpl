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
 * Class for providing read-only access to compiler environment to extensions.
 *
 * @copyright   copyright (c) 2018-present by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
abstract class Env
{
    /**
     * Environment data.
     *
     * @var     array
     */
    private $env;

    /**
     * Constructor.
     *
     * @param   array       $env                Environment data.
     */
    public function __construct(array $env)
    {
        $this->env = $env;
    }

    /**
     * Return value of specified environment variable.
     *
     * @param   string      $name               Name of environment variable.
     * @return  mixed                           Stored value.
     */
    public function __get($name)
    {
        return $this->env[$name];
    }
}
