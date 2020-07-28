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
 * @copyright   copyright (c) 2018-present by Harald Lapp
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
            new Extension\Block('for', [$this, 'blockFor'], [ 'final' => true ]),
            new Extension\Block('foreach', [$this, 'blockForeach'], [ 'final' => true ]),
            new Extension\Block('forif', [$this, 'blockIf'], [ 'final' => true ]),

            new Extension\Fun('escape', [$this, 'funcEscape'], [ 'final' => true ]),
            new Extension\Fun('let', [$this, 'funcLet'], [ 'final' => true ]),

            new Extension\Macro\Import('import', $this->tpl, [ 'final' => true ]),
        ];
    }

    public function blockFor($start, $end, $step, $value, $meta = null)
    {
        return [
            (is_null($meta)
                ? sprintf('foreach ($this->createFor(%s, %s, %s) as list(%s, )) {', $start, $end, $step, $value)
                : sprintf('foreach ($this->createFor(%s, %s, %s) as list(%s, %s)) {', $start, $end, $step, $value, $meta)),
            '}'
        ];
    }

    public function blockForeach($data, $value, $meta = null)
    {
        return [
            (is_null($meta)
                ? sprintf('foreach ($this->createForeach(%s) as list(%s, )) {', $data, $value)
                : sprintf('foreach ($this->createForeach(%s) as list(%s, %s)) {', $data, $value, $meta)),
            '}'
        ];
    }

    public function blockIf($condition)
    {
        return ['if (' . $condition . ') {', '}'];
    }

    public function funcEscape($value, $escape)
    {
        return '$this->escape(' . $value . ', ' . $escape . ')';
    }

    public function funcLet($name, $value)
    {
        return '(' . $name . ' = ' . $value . ')';
    }
}
