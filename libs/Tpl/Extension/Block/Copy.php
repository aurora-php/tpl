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
 * Copy block into a buffer.
 *
 * @copyright   copyright (c) 2018-present by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
final class Copy extends \Octris\Tpl\Extension\Block {
    /**
     * Paste bin.
     *
     * @param   array
     */
    protected $pastebin = [];
    
    /**
     * Constructor.
     *
     * @param   string              $name               Name to register extension with.
     * @param   array               $options            Optional options.
     */
    public function __construct($name, array $options = [])
    {
        $code_gen = function($ctrl) {
            return [ $ctrl, '' ];
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
