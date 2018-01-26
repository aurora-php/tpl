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
 * Internal extension bundle is always loaded automatically.
 *
 * @copyright   copyright (c) 2018 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
final class Internal extends \Octris\Tpl\Extension\AbstractBundle
{
    /**
     * Instance of template engine.
     *
     * @type    \Octris\Tpl
     */
    protected $tpl;

    /**
     * Constructor.
     *
     * @param   \Octris\Tpl     $tpl            Instance of template engine.
     */
    public function __construct(\Octris\Tpl $tpl)
    {
        $this->tpl = $tpl;

        parent::__construct();
    }

    /**
     * Return extensions from bundle.
     *
     * @return  array<\Octris\Tpl\AbstractExtension>[]
     */
    public function getExtensions()
    {
        return [
            new Tpl\Extension\Internal\BlockFor('for'),
            new Tpl\Extension\Internal\BlockForeach('foreach'),
            new Tpl\Extension\Internal\BlockIf('if'),

            new Tpl\Extension\Internal\FunEscape('escape'),
            new Tpl\Extension\Internal\FunLet('let'),

            new Tpl\Extension\Internal\MacroImport('import', $this->tpl),
        ];
    }
}
