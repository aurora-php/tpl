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
 * Standard extension bundle for template engine.
 *
 * @copyright   copyright (c) 2018 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
class Std extends \Octris\Tpl\Extension\AbstractBundle
{
    /**
     * Return extensions from bundle.
     *
     * @return  array<\Octris\Tpl\AbstractExtension>[]
     */
    public function getExtensions()
    {
        return [
            new Extension\Block\Benchmark('benchmark'),
            new Extension\Block\Copy('copy'),
            new Extension\Block\Cut('cut'),

            new Extension\Macro('uniqid', [$this, 'macroUniqId']),
            new Extension\Macro('date', [$this, 'macroDate']),

            new Extension\Fun\Cycle('cycle'),
            new Extension\Fun\Trigger('trigger'),
            new Extension\Fun\OnChange('onchange'),
            new Extension\Fun\Join('join'),

            new Extension\Fun('if', [$this, 'funcIf']),
            new Extension\Fun('ifset', [$this, 'funcIfset']),
            new Extension\Fun('ifnull', [$this, 'funcIfnull']),

            new Extension\Fun('neg', [$this, 'funcNeg']),
            new Extension\Fun('mul', [$this, 'funcMul']),
            new Extension\Fun('div', [$this, 'funcDiv']),
            new Extension\Fun('nod', [$this, 'funcMod']),
            new Extension\Fun('add', [$this, 'funcAdd']),
            new Extension\Fun('sub', [$this, 'funcSub']),
            new Extension\Fun('incr', [$this, 'funcIncr']),
            new Extension\Fun('decr', [$this, 'funcDecr']),
            new Extension\Fun('and', [$this, 'funcAnd']),
            new Extension\Fun('or', [$this, 'funcOr']),
            new Extension\Fun('xor', [$this, 'funcXor']),
            new Extension\Fun('not', [$this, 'funcNot']),
            new Extension\Fun('lt', [$this, 'funcLt']),
            new Extension\Fun('gt', [$this, 'funcGt']),
            new Extension\un('eq', [$this, 'funcEq']),
            new Extension\Fun('le', [$this, 'funcLe']),
            new Extension\Fun('ge', [$this, 'funcGe']),
            new Extension\Fun('ne', [$this, 'funcNe']),
            new Extension\Fun('abs', [$this, 'funcAbs']),
            new Extension\Fun('ceil', [$this, 'funcCeil']),
            new Extension\Fun('floor', [$this, 'funcFloor']),
            new Extension\Fun('max', [$this, 'funcMax']),
            new Extension\Fun('min', [$this, 'funcMin']),
            new Extension\Fun('round', [$this, 'funcRound']),
            new Extension\Fun('count', [$this, 'funcCount']),

            new Extension\Fun('now', [$this, 'funcNow']),
            new Extension\Fun('uniqid', [$this, 'funcUniqid']),

            new Extension\Fun('lpad', [$this, 'funcLpad']),
            new Extension\Fun('rpad', [$this, 'funcRpad']),
            new Extension\Fun('concat', [$this, 'funcConcat']),
            new Extension\Fun('repeat', [$this, 'funcRepeat']),
            new Extension\Fun('jsonencode', [$this, 'funcJsonencode']),
            new Extension\Fun('jsondecode', [$this, 'funcJsondecode']),
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
        ];
    }

    /** macros **/
    public function macroUniqId()
    {
        return \Octris\Tpl\Extension::getUniqId();
    }
    public function macroDate()
    {
        return strftime('%Y-%m-%d %H:%M:%S');
    }

    /** control flow functions **/
    public function funcIf($expr1, $expr2, $expr3 = null)
    {
        return sprintf(
            '(%s ? %s : %s)',
            $expr1,
            $expr2,
            (is_null($expr3) ? '' : $expr3)
        );
    }

    public function funcIfset($expr1, $expr2, $expr3 = null)
    {
        return sprintf(
            '(isset(%s) ? %s : %s)',
            $expr1,
            $expr2,
            (is_null($expr3) ? '' : $expr3)
        );
    }

    public function funcIfnull($expr1, $expr2, $expr3 = null)
    {
        return sprintf(
            '(is_null(%s) ? %s : %s)',
            $expr1,
            $expr2,
            (is_null($expr3) ? '' : $expr3)
        );
    }

    /** math functions **/
    public function funNeg($value)
    {
        return '(-' . $value . ')';
    }

    public function funMul($factor1, $factor2, ...$factorN)
    {
        return '(' . implode(' * ', array_merge([$factor1, $facto2], $factorN) . ')';
    }

    public function funDiv($dividend, $divisor1, ...$divisorN)
    {
        return '(' . implode(' / ', array_merge([$dividend, $divisor1], $divisorN)) . ')';
    }
    public function funcMod($dividend, $divisor)
    {
        return '(' . $dividend . ' % ' . $divisor . ')';
    }

    public function funcAdd($summand1, $summand2, ...$summandN)
    {
        return '(' . implode(' + ', array_merge([$summand1, $summand2], $summandN)) . ')';
    }

    public function funcSub($minuend, $subtrahend1, ...$subtrahendN)
    {
        return '(' . implode(' - ', array_merge([$minuend, $subtrahend1], $subtrahendN)) . ')';
    }

    public function funcIncr($summand1, $summand2 = null)
    {
        return sprintf('(%s)', (is_null($summand2) ? '++' . $summand1 : $summand1 . ' += ' . $summand2));
    }

    public function funcDecr($minuend, $subtrahend = null)
    {
        return sprintf('(%s)', (is_null($subtrahend) ? '--' . $minuend : $minuend . ' -= ' . $subtrahend));
    }

    public function funcAnd($term1, $term2, ...$termN)
    {
        return '(' . implode(' && ', array_merge([$term1, $term2], $termN)) . ')';
    }

    public function funcOr($term1, $term2, ...$termN)
    {
        return '(' . implode(' || ', array_merge([$term1, $term2], $termN)) . ')';
    }

    public function funcXor($term1, $term2)
    {
        return sprintf('(%d != %d)', !!$term1, !!$term2);
    }

    public function funcNot($term)
    {
        return '!' . $term;
    }

    public function funcLt($term1, $term2)
    {
        return '(' . implode(' < ', [$term1, $term2]) . ')';
    }

    public function funcGt($term1, $term2)
    {
        return '(' . implode(' > ', [$term1, $term2]) . ')';
    }

    public function funcEq($term1, $term2)
    {
        return '(' . implode(' == ', [$term1, $term2]) . ')';
    }

    public function funcLe($term1, $term2)
    {
        return '(' . implode(' <= ', [$term1, $term2]) . ')';
    }

    public function funcGe($term1, $term2)
    {
        return '(' . implode(' >= ', [$term1, $term2]) . ')';
    }

    public function funcNe($term1, $term2)
    {
        return '(' . implode(' != ', [$term1, $term2]) . ')';
    }

    public function funcAbs($term)
    {
        return '(abs(' . $term . '))';
    }

    public function funcCeil($term)
    {
        return '(ceil(' . $term . '))';
    }

    public function funcFloor($term)
    {
        return '(floor(' . $term . '))';
    }

    public function funcRound($term)
    {
        return '(round(' . $term . '))';
    }

    public function funcCount($term)
    {
        return '(count(' . $term . '))';
    }

    public function funcMin($term1, $term2, ...$termN)
    {
        return '(min(' . implode(', ', array_merge([$term1, $term2], $termN)) . '))';
    }

    public function funcMax($term1, $term2, ...$termN)
    {
        return '(max(' . implode(', ', array_merge([$term1, $term2], $termN)) . '))';
    }

    /** misc **/
    public function funcNow()
    {
        return '(time())';
    }

    public function funcUniqid()
    {
        return '\Octris\Tpl\Extension::getUniqId()';
    }

    /** string functions **/
    public function funcLpad($input, $pad_length, $pad_string = ' ')
    {
        return '(str_pad(' . implode(', ', [$input, $path_length, $pad_string] . ', STR_PAD_LEFT))';
    }

    public function funcRpad($input, $pad_length, $pad_string = ' ')
    {
        return '(str_pad(' . implode(', ', [$input, $path_length, $pad_string] . ', STR_PAD_RIGHT))';
    }

    public function funcConcat($str1, $str2, ...$strN)
    {
        return '(' . implode(' . ', array_merge([$str1, $str2], $strN)) . ')';
    }

    public function funcRepeat($string, $multiplier)
    {
        return '(str_repeat(' . $string . ', ' . $multiplier . '))';
    }

    /** array functions **/
    public function funcUnpack($arg)
    {
        return '(...' . $arg . ')';
    }

    public function funcJsonencode($value, $options = 0, $depth = 512)
    {
        return '(json_encode(' . $value . ', ' . $options . ', ' . $depth . '))';
    }

    public function funcJsondecode($json, $depth = 512, $options = 0)
    {
        return '(json_decode(' . $json . ', true, ' . $depth . ', ' . $options . '))';
    }
}
