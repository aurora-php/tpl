<?php

/*
 * This file is part of the 'octris/core' package.
 *
 * (c) Harald Lapp <harald@octris.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Octris\Core\Tpl\Postprocess;

/**
 * Combine multiple css source files into a single file.
 *
 * @copyright   copyright (c) 2010-2016 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
class CombineCss extends \Octris\Core\Tpl\Postprocess
{
    use CombineTrait;
    
    /**
     * Constructor.
     *
     * @param   array       $mappings   Array of path-prefix to real-path mappings.
     * @param   string      $dst        Destination directory for created files.
     */
    public function __construct(array $mappings, $dst)
    {
        $this->ext = 'css';
        $this->pattern = '<link[^>]*? href="(?!https?://|//)([^"]+\.css)"[^>]*/>';
        $this->snippet = '<link rel="stylesheet" href="/styles/%s" type="text/css" />';
        $this->dst = $dst;

        parent::__construct($mappings);
    }
}
