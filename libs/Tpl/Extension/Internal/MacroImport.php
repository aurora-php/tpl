<?php

/*
 * This file is part of the 'octris/tpl' package.
 *
 * (c) Harald Lapp <harald@octris.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Octris\Tpl\Extension\Internal;

/**
 * Macro for importing sub-template.
 *
 * @copyright   copyright (c) 2018 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
final class MacroImport extends \Octris\Tpl\Extension\Macro {
    /**
     * Constructor.
     *
     * @param   string              $name               Name to register extension with.
     * @param   array               $options            Optional options.
     */
    public function __construct($name, array $options = [])
    {
        $code_gen = function($filename) use ($options) {
            $ret = '';

            $c = clone($this->compiler);

            if (($file = $options['tpl']->findFile($filename)) !== false) {
                $ret = $c->process($file, $this->escape);
            } else {
                throw new \Exception(sprintf('Unable to locate file "%s" in "%s"', $filename, implode(':', $options['tpl']->getSearchPath())));
            }

            return $ret;
        };

        unset($options['tpl']);

        parent::__construct($name, $code_gen, [ 'env' => true, 'final' => true ] + $options);
    }
}
