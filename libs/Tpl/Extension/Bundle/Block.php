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
            new Block('cron', [$this, 'blockCron']),
            new Block('cut', [$this, 'blockCut']),
            new Block('foreach', [$this, 'blockForeach']),
            new Block('if', [$this, 'blockIf'], ['final' => true]),
            new Block('loop', [$this, 'blockLoop']),
            new Block('onchange', [$this, 'blockOnchange']),
            new Block('trigger', [$this, 'blockTrigger']),
        ];
    }

    /** Block functions to register **/

    protected function blockBench($iterations)
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
    }

    protected function blockCopy($args)
    {
        return array(
            '$this->bufferStart(' . implode(', ', $args) . ', false);',
            '$this->bufferEnd();'
        );
    }

    protected function blockCron($args)
    {
        return array(
            'if ($this->cron(' . implode(', ', $args) . ')) {',
            '}'
        );
    }

    protected function blockCut($args)
    {
        return array(
            '$this->bufferStart(' . implode(', ', $args) . ', true);',
            '$this->bufferEnd();'
        );
    }

    protected function blockForeach($args)
    {
        if (count($args) == 2) {
            $return = [
                sprintf('foreach ($this->loop(%s) as list(%s, )) {', $args[1], $args[0]),
                '}'
            ];
        } else {
            $return = [
                sprintf('foreach ($this->loop(%s) as list(%s, %s)) {', $args[1], $args[0], $args[2]),
                '}'
            ];
        }

        return $return;
    }

    protected function blockIf($args)
    {
        return array(
            'if (' . implode('', $args) . ') {',
            '}'
        );
    }

    protected function blockLoop($args)
    {
        $params = [$args[0], range($args[1], $args[2], $args[3])];

        if (count($args) == 5) {
            $params[$args[4]];
        }

        return $this->blockForeach($params);
    }

    protected function blockOnchange($args)
    {
        return array(
            'if ($this->onchange("' . self::getUniqId() . '", ' . implode(', ', $args) . ')) {',
            '}'
        );
    }

    protected function blockTrigger($args)
    {
        return array(
            'if ($this->trigger("' . self::getUniqId() . '", ' . implode(', ', $args) . ')) {',
            '}'
        );
    }
}
