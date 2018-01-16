<?php

/*
 * This file is part of the 'octris/tpl' package.
 *
 * (c) Harald Lapp <harald@octris.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Octris\Tpl\Extension\Bundle;

use \Octris\Tpl\Extension;

/**
 * Core extension for template engine.
 *
 * @copyright   copyright (c) 2018 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
class Core extends AbstractBundle
{
    /**
     * Return extensions from bundle.
     * 
     * @return  array<\Octris\Tpl\AbstractExtension>[]
     */
    public function getExtensions()
    {
        return [
            new Fun('neg')->setFun([$this, 'funNeg']),
            new Fun('mul')->setFun([$this, 'funMul']),
            new Fun('div')->setFun([$this, 'funDiv']), 
        ];
    }
    
    /** core functions to register **/

    protected function funNeg($value)
    {
        return '(-' . $value . ')';
    }

    protected function funMul($factor1, $factor2, ...$factorN)
    {
        return '(' . implode(' * ', array_merge([$factor1, $facto2], $factorN) . ')';
    }

    protected function funDiv($dividend, $divisor1, ...$divisorN)
    {
        return '(' . implode(' / ', array_merge([$dividend, $divisor1], $divisorN)) . ')';
    }
}
