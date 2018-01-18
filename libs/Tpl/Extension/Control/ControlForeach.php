<?php

/*
 * This file is part of the 'octris/tpl' package.
 *
 * (c) Harald Lapp <harald@octris.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Octris\Tpl\Extension\Control;

/**
 * FOREACH control structure.
 *
 * @copyright   copyright (c) 2018 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
class ControlForeach
{
    /**
     * Constructor.
     *
     * @param   string              $name               Name to register extension with.
     * @param   array               $options            Optional options.
     */
    public function __construct($name, array $options = [])
    {
        $code_gen = function($item, $data, $meta = null) {
            if (is_null($meta)) {
                $return = sprintf('foreach ($this->library[\'' . static::class . '\']->call(%s) as list(%s, )) {', $data, $item);
            } else {
                $return = sprintf('foreach ($this->library[\'' . static::class . '\']->call(%s) as list(%s, %s)) {', $data, $item, $meta);
            }

            return $return;
        };

        parent::__construct($name, $code_gen, $options);
    }
}
