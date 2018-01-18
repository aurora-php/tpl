<?php

/*
 * This file is part of the 'octris/tpl' package.
 *
 * (c) Harald Lapp <harald@octris.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Octris\Tpl\Extension\Block;

/**
 * Block benchmark.
 *
 * @copyright   copyright (c) 2018 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
final class Bench extends \Octris\Tpl\Extension\Fun {
    /**
     * Constructor.
     *
     * @param   string              $name               Name to register extension with.
     * @param   array               $options            Optional options.
     */
    public function __construct($name, array $options = [])
    {
        $code_gen = function($iterations) {
            $var1 = '$_' . self::getUniqId();
            $var2 = '$_' . self::getUniqId();

            return [
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
            ];
        };

        parent::__construct($name, $code_gen, $options);
    }

    /**
     * Gets called when head of block is reached.
     * 
     * @param   mixed       $ctrl       Control variable to store buffer data in.
     */
    public function head(&$ctrl)
    {
        array_push($this->pastebin, &$ctrl );

        ob_start();
    }
    
    /**
     * Gets called when foot of block is reached.
     * 
     * @return  bool                    Returns true if variable value change was detected.
     */
    public function foot()
    {
        $buffer = array_pop($this->pastebin);
        $buffer = ob_get_contents();

        ob_end_flush();
    }    
}
