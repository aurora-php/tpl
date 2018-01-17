<?php

/*
 * This file is part of the 'octris/tpl' package.
 *
 * (c) Harald Lapp <harald@octris.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Octris\Tpl\Extension\Bundle;

use \Octris\Tpl\Extension;

/**
 * Core extension for template engine.
 *
 * @copyright   copyright (c) 2018 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
class Core extends AbstractBundle
{
    /**
     * Return extensions from bundle.
     *
     * @return  array<\Octris\Tpl\AbstractExtension>[]
     */
    public function getExtensions()
    {
        return [
            new Fun('neg', [$this, 'funNeg']),
            new Fun('mul', [$this, 'funMul']),
            new Fun('div', [$this, 'funDiv']),
        ];
    }

    /**
     * Return constants.
     */
    public function getConstants()
    {
        return [
            'TRUE'     => true,
            'FALSE'    => false,

            // pre-defined constants for escaping
            'ESC_NONE' => '',
            'ESC_ATTR' => 'attr',
            'ESC_CSS'  => 'css',
            'ESC_JS'   => 'js',
            'ESC_URI'  => 'uri',

            // pre-defined constants for json_encode/json_decode
            'JSON_HEX_QUOT'          => JSON_HEX_QUOT,
            'JSON_HEX_TAG'           => JSON_HEX_TAG,
            'JSON_HEX_AMP'           => JSON_HEX_AMP,
            'JSON_HEX_APOS'          => JSON_HEX_APOS,
            'JSON_NUMERIC_CHECK'     => JSON_NUMERIC_CHECK,
            'JSON_BIGINT_AS_STRING'  => JSON_BIGINT_AS_STRING,
            'JSON_PRETTY_PRINT'      => JSON_PRETTY_PRINT,
            'JSON_UNESCAPED_SLASHES' => JSON_UNESCAPED_SLASHES,
            'JSON_FORCE_OBJECT'      => JSON_FORCE_OBJECT,
            'JSON_UNESCAPED_UNICODE' => JSON_UNESCAPED_UNICODE,
            'JSON_BIGINT_AS_STRING'  => JSON_BIGINT_AS_STRING,

            // pre-defined constants for string functions
            // 'CASE_UPPER'             => \Octris\Core\Type\Text::CASE_UPPER,
            // 'CASE_LOWER'             => \Octris\Core\Type\Text::CASE_LOWER,
            // 'CASE_TITLE'             => \Octris\Core\Type\Text::CASE_TITLE,
            // 'CASE_UPPER_FIRST'       => \Octris\Core\Type\Text::CASE_UPPER_FIRST,
            // 'CASE_LOWER_FIRST'       => \Octris\Core\Type\Text::CASE_LOWER_FIRST
        ];
    }

    /** core functions to register **/

    protected function funNeg($value)
    {
        return '(-' . $value . ')';
    }

    protected function funMul($factor1, $factor2, ...$factorN)
    {
        return '(' . implode(' * ', array_merge([$factor1, $facto2], $factorN) . ')';
    }

    protected function funDiv($dividend, $divisor1, ...$divisorN)
    {
        return '(' . implode(' / ', array_merge([$dividend, $divisor1], $divisorN)) . ')';
    }
}
