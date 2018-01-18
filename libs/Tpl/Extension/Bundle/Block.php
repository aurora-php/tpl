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
 * Extension implementing block functions.
 *
 * @copyright   copyright (c) 2018 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
class Block extends AbstractBundle
{
    /**
     * Return extensions from bundle.
     *
     * @return  array<\Octris\Tpl\AbstractExtension>[]
     */
    public function getExtensions()
    {
        return [
            new Block('bench', [$this, 'blockBench']),
            new Block('copy', [$this, 'blockCopy']),
            new Block('cut', [$this, 'blockCut']),
            new Block('foreach', [$this, 'blockForeach']),
            new Block('if', [$this, 'blockIf'], ['final' => true]),
            new Block('loop', [$this, 'blockLoop']),
        ];
    }

    /** Block functions to register **/

    public function blockForeach($value, $data, $meta = null)
    {
        if (is_null($meta)) {
            $return = [
                sprintf('foreach ($this->block->doLoop(%s) as list(%s, )) {', $data, $value),
                '}'
            ];
        } else {
            $return = [
                sprintf('foreach ($this->library("block")->doLoop(%s) as list(%s, %s)) {', $data, $value, $meta),
                '}'
            ];
        }

        return $return;
    }

    public function blockIf($args)
    {
        return array(
            'if (' . implode('', $args) . ') {',
            '}'
        );
    }

    public function blockLoop($args)
    {
        $params = [$args[0], range($args[1], $args[2], $args[3])];

        if (count($args) == 5) {
            $params[$args[4]];
        }

        return $this->blockForeach($params);
    }
}