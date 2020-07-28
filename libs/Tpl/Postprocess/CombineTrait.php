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
 * Combine multiple source files into a single file.
 *
 * @copyright   copyright (c) 2010-present by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
trait CombineTrait
{
    /**
     * File extension of created file.
     *
     * @type    string
     */
    protected $ext;

    /**
     * Pattern to match.
     *
     * @type    string
     */
    protected $pattern;

    /**
     * Snippet to replace pattern with.
     *
     * @type    string
     */
    protected $snippet;

    /**
     * Destination directory for created files.
     *
     * @type    string
     */
    protected $dst;

    /**
     * Process (combine) collected files.
     *
     * @param   array       $files      Files to combine.
     * @return  string                  Destination name.
     */
    public function processFiles(array $files)
    {
        $files = array_map(function ($file) {
            return escapeshellarg($file);
        }, $files);

        $tmp = tempnam('/tmp', 'oct');

        $cmd = sprintf(
            'cat %s > %s 2>&1',
            implode(' ', $files),
            $tmp
        );

        $ret = [];
        $ret_val = 0;
        exec($cmd, $ret, $ret_val);

        $md5  = md5_file($tmp);
        $name = $md5 . '.' . $this->ext;
        rename($tmp, $this->dst . '/' . $name);

        return $name;
    }

    /**
     * Postprocess a template. The method collects all blocks found using '$this->pattern' and extract
     * all included external files. The function makes sure that files are not included mutliple times. Patterns
     * found will be replaces with '$this->snippet'.
     *
     * @param   string      $tpl        Template to postprocess.
     * @return  string                  Postprocessed template.
     */
    public function postProcess($tpl)
    {
        $files  = [];
        $offset = 0;

        while (preg_match("#(?:$this->pattern"."([\n\r\s]*))+#si", $tpl, $m_block, PREG_OFFSET_CAPTURE, $offset)) {
            $compressed = '';

            if (preg_match_all("#$this->pattern#si", $m_block[0][0], $m_tag)) {
                // collect files to process
                $diff = array_diff($m_tag[1], $files);
                $files = array_merge($files, $diff);

                // process files
                $resolved = $this->resolvePaths($diff);
                $name = $this->processFiles($resolved);

                $compressed = sprintf($this->snippet, $name);
            }

            $compressed .= $m_block[2][0];

            $tpl = substr_replace($tpl, $compressed, $m_block[0][1], strlen($m_block[0][0]));
            $offset = $m_block[0][1] + strlen($compressed);
        };

        return $tpl;
    }
}
