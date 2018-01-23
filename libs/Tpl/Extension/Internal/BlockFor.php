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
 * FOR control structure.
 *
 * @copyright   copyright (c) 2018 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
final class BlockFor extends \Octris\Tpl\Extension\AbstractExtension {
    /**
     * Constructor.
     *
     * @param   string              $name               Name to register extension with.
     * @param   array               $options            Optional options.
     */
    public function __construct($name, array $options = [])
    {
        $code_gen = function($start, $end, $step, $meta = null) { };

        parent::__construct($name, $code_gen, [ 'final' => true ] + $options);
    }
    
    /**
     * Code generator.
     *
     * @param   array               $args               Function arguments definition.
     * @param   array               $env                Engine environment.
     * @return  array                                   Template code for head and foot.
     */
    public function getCode(array $args, array $env)
    {
        return [
            (count($args) == 3
                ? vsprintf('foreach ($this->createFor(%s) as list(%s, )) {', $args)
                : vsprintf('foreach ($this->createFor(%s) as list(%s, %s)) {', $args)),
            '}'
        ];
    }
}
