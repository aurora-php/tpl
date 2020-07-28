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
 * Interface for building caching backends for the template engine.
 *
 * @copyright   copyright (c) 2016-present by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
interface CacheInterface
{
    /**
     * Return cache URI for a specified filename.
     *
     * @param   string          $filename               Name of file to get URI for.
     */
    public function getURI($filename);
    /**/

    /**
     * Test if cache file exists.
     *
     * @param   string          $uri            URI to test.
     * @return  bool                            Returns 'true', if cache URI exists, otherwise 'false'.
     */
    public function isExist($uri);

    /**
     * Write content to location.
     *
     * @param   string          $uri            Location to write content to.
     * @param   string          $content        Content to write.
     */
    public function putContents($uri, $content);

    /**
     * Get content from location.
     *
     * @param   string          $uri            Location to get content from.
     * @return  string|bool                     Content read or false if content is not available.
     */
    public function getContents($uri);
}
