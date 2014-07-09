<?php

/*
 * This file is part of the 'octris/core' package.
 *
 * (c) Harald Lapp <harald@octris.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Octris\Core\Tpl;

/**
 * Lint for templates.
 *
 * @octdoc      c:tpl/lint
 * @copyright   copyright (c) 2010-2014 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
class Lint extends \Octris\Core\Tpl\Compiler
{
    /**
     * Number of errors occured.
     *
     * @octdoc  p:lint/$errors
     * @type    int
     */
    protected $errors = 0;
    /**/

    /**
     * Constructor.
     *
     * @octdoc  m:lint/__construct
     */
    public function __construct()
    {
        parent::__construct();

        if (defined('STDERR')) {
            $this->errout = fopen('php://stderr', 'w');
        }
    }

    /**
     * Trigger an error.
     *
     * @octdoc  m:lint/error
     * @param   string      $ifile      Internal filename the error occured in.
     * @param   int         $iline      Internal line number the error occured in.
     * @param   int         $line       Line in template the error was triggered for.
     * @param   int         $token      ID of token that triggered the error.
     * @param   mixed       $payload    Optional additional information. Either an array of expected token IDs or an additional message to output.
     */
    protected function error($ifile, $iline, $line, $token, $payload = NULL)
    {
        try {
            parent::error($ifile, $iline, $line, $token, $payload);
        } catch (\Exception $e) {
        }

        ++$this->errors;
    }

    /**
     * Execute lint toolchain for a template snippet.
     *
     * @octdoc  m:lint/toolchain
     * @param   string      $snippet        Template snippet to process.
     * @param   int         $line           Line in template processed.
     * @param   array       $blocks         Block information required by analyzer / compiler.
     * @param   string      $escape         Escaping to use.
     * @return  string                      Processed / compiled snippet.
     */
    protected function toolchain($snippet, $line, array &$blocks, $escape)
    {
        if (is_null(self::$parser)) {
            // initialize parser
            $this->setup($blocks);
        }

        try {
            if (($tokens = self::$parser->tokenize($snippet, $line, $this->filename)) === false) {
                $error = self::$parser->getLastError();

                $this->error($error['ifile'], $error['iline'], $error['line'], $error['token'], $error['payload']);
            } elseif (count($tokens) > 0) {
                if (self::$parser->analyze($tokens) === false) {
                    $error = self::$parser->getLastError();

                    $this->error($error['ifile'], $error['iline'], $error['line'], $error['token'], $error['payload']);
                }
            }
        } catch (\Exception $e) {
            // dismiss exception to continue lint process
        }

        return '';
    }

    /**
     * Process a template.
     *
     * @octdoc  m:lint/process
     * @param   string      $filename       Name of template file to lint.
     * @param   string      $escape         Escaping to use.
     * @param   string      $err            Destination for error reporting.
     * @return  bool                        Returns true if template is valid.
     */
    public function process($filename, $escape)
    {
        $this->errors = 0;

        parent::process($filename, $escape);

        return ($this->errors == 0);
    }
}
