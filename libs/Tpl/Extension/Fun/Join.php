<?php

/*
 * This file is part of the 'octris/tpl' package.
 *
 * (c) Harald Lapp <harald@octris.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Octris\Tpl\Extension\Fun;

/**
 * Join array elements with a string.
 *
 * @copyright   copyright (c) 2018 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
final class Join extends \Octris\Tpl\Extension\Fun {
    /**
     * Constructor.
     *
     * @param   string              $name               Name to register extension with.
     * @param   array               $options            Optional options.
     */
    public function __construct($name, array $options = [])
    {
        $code_gen = function(iterable $pieces, $glue = ',') { };

        parent::__construct($name, $code_gen, $options);
    }

    /**
     * Implementation for the called function.
     * 
     * @param   iterable    $pieces     Pieces to join.
     * @param   string      $glue       String to use as glue.
     * @return  string                  Joined pieces.
     */
    public function call(iterable $pieces, $glue = ',')
    {
        $tmp = [];
        array_push($tmp, ...$pieces);
    
        return implode($glue, $tmp);
    }
}
