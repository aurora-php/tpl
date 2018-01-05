<?php

/*
 * This file is part of the 'octris/tpl' package.
 *
 * (c) Harald Lapp <harald@octris.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Octris\Tpl\Postprocess;

/**
 * Combine multiple javascript source files into a single file.
 *
 * @copyright   copyright (c) 2010-2018 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
class CombineJs extends \Octris\Tpl\Postprocess
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
        $this->ext = 'js';
        $this->pattern = '<script[^>]+src="(?!https?://|//)([^"]+)"[^>]*></script>';
        $this->snippet = '<script type="text/javascript" src="/libsjs/%s"></script>';
        $this->dst = $dst;

        parent::__construct($mappings);
    }
}
