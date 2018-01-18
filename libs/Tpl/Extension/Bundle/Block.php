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

    public function blockBench($iterations)
    {
        $var1 = '$_' . self::getUniqId();
        $var2 = '$_' . self::getUniqId();

        return array(
            sprintf(
                '%s = microtime(true); ' .
                'for (%s = 0; %s < abs(%s); ++%s) { ' .
                'if (%s == 1) ob_start();',
                $var1,
                $var2,
                $var2,
                $iterations,
                $var2,
                $var2
            ),
            sprintf(
                '} %s = microtime(true) - %s; ' .
                'if (abs(%s) > 0) ob_end_clean(); ' .
                'printf("[benchmark iterations: %%s, time: %%1.6f]", abs(%s), %s);',
                $var1,
                $var1,
                $iterations,
                $iterations,
                $var1
            )
        );
        
        circodasu
    }

    public function blockCopy($args)
    {
        return array(
            '$this->bufferStart(' . implode(', ', $args) . ', false);',
            '$this->bufferEnd();'
        );
    }

    public function blockCron($args)
    {
        return array(
            'if ($library->block->cron(' . implode(', ', $args) . ')) {',
            '}'
        );
    }

    public function blockCut($args)
    {
        return array(
            '$this->bufferStart(' . implode(', ', $args) . ', true);',
            '$this->bufferEnd();'
        );
    }

    /**
     * Implements iterator block function eg.: #foreach and #loop. Iterates over array and repeats an
     * enclosed template block.
     *
     * @param   iterable                        $data               Iteratable data.
     * @return  \Generator                                          Generator to use for iterating.
     */
    public function doLoop(iterable $data)
    {
        $loop = function ($data) {
            $pos = 0;
            $count = count($data);

            foreach ($data as $key => $item) {
                $meta = [
                    'is_first' => ($pos == 0),
                    'is_last' => ($pos + 1 == $count),
                    'count' => $count,
                    'pos' => $pos++,
                    'key' => $key
                ];

                yield [$item, $meta];
            }
        };

        return $loop($data);
    }

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
