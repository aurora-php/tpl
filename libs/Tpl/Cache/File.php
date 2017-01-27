<?php

/*
 * This file is part of the 'octris/core' package.
 *
 * (c) Harald Lapp <harald@octris.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Octris\Core\Tpl\Cache;

/**
 * Filesystem cache storage.
 *
 * @copyright   copyright (c) 2016 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
class File implements \Octris\Core\Tpl\ICache
{
    /**
     * Root path of filesystem cache.
     *
     * @type    string
     */
    protected $path;

    /**
     * Constructor.
     *
     * @param   string          $path           Root path of filesystem cache.
     */
    public function __construct($path)
    {
        $this->path = $path;
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
        $uri = str_replace(
            '/',
            '-',
            preg_replace(
                '/[\s\.]/',
                '_',
                ltrim(
                    preg_replace(
                        '/\/\/+/',
                        '/',
                        preg_replace(
                            '/\.\.*\//',
                            '/',
                            $filename
                        )
                    ),
                    '/'
                )
            )
        );

        $uri .= ($l10n != '' ? '-' . $l10n : '') . '.php';

        return $uri;
    }

    /**
     * Test if cache file exists.
     *
     * @param   string          $uri            URI to test.
     * @return  bool                            Returns 'true', if cache URI exists, otherwise 'false'.
     */
    public function isExist($uri)
    {
        return (file_exists($this->path . '/' . $uri));
    }

    /**
     * Write content to location.
     *
     * @param   string          $uri            Location to write content to.
     * @param   string          $content        Content to write.
     */
    public function putContents($uri, $content)
    {
        $tmp = tempnam(sys_get_temp_dir(), 'tpl');
        file_put_contents($tmp, $content);

        rename($tmp, $this->path . '/' . $uri);
    }

    /**
     * Get content from location.
     *
     * @param   string          $uri            Location to get content from.
     * @return  string|bool                     Content read or false if content is not available.
     */
    public function getContents($uri)
    {
        $file = $this->path . '/' . $uri;

        if (($result = (is_file($file) && is_readable($file)))) {
            $result = file_get_contents($file);
        }

        return $result;
    }
}
