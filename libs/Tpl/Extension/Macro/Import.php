<?php

/*
 * This file is part of the 'octris/tpl' package.
 *
 * (c) Harald Lapp <harald@octris.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Octris\Tpl\Extension\Macro;

/**
 * Macro for importing sub-template.
 *
 * @copyright   copyright (c) 2018 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
final class Import extends \Octris\Tpl\Extension\Macro {    
    /**
     * Constructor.
     *
     * @param   string              $name               Name to register extension with.
     * @param   array               $options            Optional options.
     */
    public function __construct($name, array $options = [])
    {
        $code_gen = function($filename) {
        };

        parent::__construct($name, $code_gen, $options);
    }

    /**
     * Code generator.
     *
     * @param   array               $args               Function arguments definition.
     * @param   array               $env                Engine environment.
     * @return  string                                  Template code.
     */
    public function getCode(array $args, array $env)
    {
        $ret = '';
        $err = '';

        $c = clone($env['compiler']);

        if (($file = $c->findFile($args[0])) !== false) {
            $ret = $c->process($file, $env['escape']);
        } else {
            $err = sprintf(
                'unable to locate file "%s" in "%s"',
                $args[0],
                implode(':', $c->getSearchPath())
            );
        }

        return $ret;
        //return array($ret, $err);
    }
}
