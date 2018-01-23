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
 * Abstract class for implementing post processors.
 *
 * @copyright   copyright (c) 2016-2018 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
abstract class Postprocess
{
    /**
     * Path mappings.
     *
     * @type    array
     */
    protected $mappings = [];

    /**
     * Constructor.
     *
     * @param   array       $mappings   Optional array of path-prefix to real-path mappings.
     * @param   string      $dst        Destination directory for created files.
     */
    public function __construct(array $mappings = [])
    {
        foreach ($mappings as $prefix => $mapping) {
            $this->addMapping($prefix, $mapping);
        }
    }

    /**
     * Add a path mapping.
     *
     * @param   string      $prefix     Path prefix to look for.
     * @param   string      $mapping    Path to map prefix to.
     */
    public function addMapping($prefix, $mapping)
    {
        if (!is_dir($mapping)) {
            trigger_error('Path to map "' . $prefix . '" to does not exist "' . $mapping . '".');
        } else {
            $prefix = rtrim($prefix, '/') . '/';
            $mapping = rtrim($mapping, '/') . '/';

            $this->mappings[$prefix] = $mapping;

            krsort($this->mappings);
        }
    }

    /**
     * Resolve file paths.
     *
     * @param   array        $paths     An array of paths to resolve according to path mapping.
     * @return  array                   Resolved paths.
     */
    public function resolvePaths(array $paths)
    {
        $resolved = [];

        foreach ($paths as $src_path) {
            foreach ($this->mappings as $prefix => $mapping) {
                if (strpos($src_path, $prefix) === 0) {
                    $dst_path = $mapping . substr($src_path, strlen($prefix));

                    if (file_exists($dst_path)) {
                        $resolved[] = $dst_path;

                        continue 2;
                    }
                }
            }

            trigger_error('Unable to resolve path "' . $src_path . '".');
        }

        return $resolved;
    }

    /**
     * Postprocess a template.
     *
     * @param   string      $tpl        Template to postprocess.
     * @return  string                  Postprocessed template.
     */
    abstract public function postProcess($tpl);
}
