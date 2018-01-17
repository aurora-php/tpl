<?php

/*
 * This file is part of the 'octris/tpl' package.
 *
 * (c) Harald Lapp <harald@octris.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Octris\Tpl;

/**
 * Language library of the template engine.
 *
 * @copyright   copyright (c) 2018 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
class Library
{
    /**
     * Constructor.
     */
    public function __construct()
    {
    }

    /**
     * Add a single extension.
     *
     * @param   \Octris\Tpl\Extension\AbstractExtension $extension          A single extension to add.
     */
    public function addExtension(\Octris\Tpl\Extension\AbstractExtension $extension)
    {

    }

    /**
     * Add an extension bundle.
     *
     * @param   \Octris\Tpl\Extension\AbstractBundle    $bundle             Extension bundle to add.
     */
    public function addExtensionBundle(\Octris\Tpl\Extension\AbstractBundle $bundle)
    {
        foreach ($bundle->getExtensions() as $extension) {
            $this->addExtension($extension);
        }

        // TODO: add bundle constants
    }

}

