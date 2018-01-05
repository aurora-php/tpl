<?php

/*
 * This file is part of the 'octris/tpl' package.
 *
 * (c) Harald Lapp <harald@octris.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Octris\Tpl\Cache;

/**
 * Transient storage.
 *
 * @copyright   copyright (c) 2016-2018 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
class Transient implements \Octris\Tpl\CacheInterface
{
    /**
     * Storage segments.
     *
     * @type    array
     */
    protected $segments = array();

    /**
     * Constructor.
     *
     * @param   string          $path           Root path of filesystem cache.
     */
    public function __construct()
    {
    }

    /**
     * Return cache URI for a specified filename.
     *
     * @param   string          $filename       Name of file to get URI for.
     * @param   string          $l10n           Optional locale string.
     * @return  string                          URI of specified file.
     */
    public function getURI($filename, $l10n = '')
    {
        $uri = md5($filename . '|' . $l10n);

        return $uri;
    }

    /**
     * Test if memory segment exists for URI.
     *
     * @param   string          $uri            URI to test.
     * @return  bool                            Returns 'true', if cache URI exists, otherwise 'false'.
     */
    public function isExist($uri)
    {
        return (isset($this->segments[$uri]));
    }

    /**
     * Write content to location.
     *
     * @param   string          $uri            Location to write content to.
     * @param   string          $content        Content to write.
     */
    public function putContents($uri, $content)
    {
        if (isset($this->segments[$uri])) {
            fclose($this->segments[$uri]);
        }

        $this->segments[$uri] = fopen('php://memory', 'r+');
        fputs($this->segments[$uri], $content);
    }

    /**
     * Get content from location.
     *
     * @param   string          $uri            Location to get content from.
     * @return  string|bool                     Content read or false if content is not available.
     */
    public function getContents($uri)
    {
        if (isset($this->segments[$uri])) {
            rewind($this->segments[$uri]);

            $result = stream_get_contents($this->segments[$uri]);
        } else {
            $result = false;
        }

        return $result;
    }
}
