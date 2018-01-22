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
class Std extends AbstractBundle
{
    /**
     * Return extensions from bundle.
     *
     * @return  array<\Octris\Tpl\AbstractExtension>[]
     */
    public function getExtensions()
    {
        return [
            new Block\Benchmark('benchmark'),
            new Block\Copy('copy'),
            new Block\Cut('cut'),

            new Macro\Import('import'),
            new Macro('uniqid', [$this, 'macroUniqId']),
            new Macro('date', [$this, 'macroDate']),

            new Fun\Cycle('cycle'),
            new Fun\Trigger('trigger'),
            new Fun\OnChange('onchange'),
            new Fun\Escape('escape'),

            new Fun('if', [$this, 'funcIf']),
            new Fun('ifset', [$this, 'funcIfset']),
            new Fun('ifnull', [$this, 'funcIfnull']),

            new Fun('neg', [$this, 'funNeg']),
            new Fun('mul', [$this, 'funMul']),
            new Fun('div', [$this, 'funDiv']),
            new Fun('nod', [$this, 'funcMod']),
            new Fun('add', [$this, 'funcAdd']),
            new Fun('sub', [$this, 'funcSub']),
            new Fun('incr', [$this, 'funcIncr']),
            new Fun('decr', [$this, 'funcDecr']),
            new Fun('and', [$this, 'funcAnd']),
            new Fun('or', [$this, 'funcOr']),
            new Fun('xor', [$this, 'funcXor']),
            new Fun('not', [$this, 'funcNot']),
            new Fun('lt', [$this, 'funcLt']),
            new Fun('gt', [$this, 'funcGt']),
            new Fun('eq', [$this, 'funcEq']),
            new Fun('le', [$this, 'funcLe']),
            new Fun('ge', [$this, 'funcGe']),
            new Fun('ne', [$this, 'funcNe']),

            new Fun('bool', [$this, 'funcBool']),
            new Fun('int', [$this, 'funcInt']),
            new Fun('float', [$this, 'funcFloat']),
            new Fun('string', [$this, 'funcString']),

            new Fun('now', [$this, 'funcNow']),
            new Fun('uniqid', [$this, 'funcUniqid']),

            new Fun('explode', [$this, 'funcExplode']),
            new Fun('implode', [$this, 'funcImplode']),
            new Fun('lpad', [$this, 'funcLpad']),
            new Fun('rpad', [$this, 'funcRpad']),
            new Fun('totitle', [$this, 'funcTotitle']),
            new Fun('concat', [$this, 'funcConcat']),
            new Fun('array', [$this, 'funcArray']),
            new Fun('in', [$this, 'funcIn']),
            new Fun('comify', [$this, 'funcComify']),
            new Fun('enum', [$this, 'funcEnum']),
            new Fun('monf', [$this, 'funcMonf']),
            new Fun('numf', [$this, 'funcNumf']),
            new Fun('perf', [$this, 'funcPerf']),
            new Fun('datef', [$this, 'funcDatef']),
            new Fun('gender', [$this, 'funcGender']),
            new Fun('quant', [$this, 'funcQuant']),
            new Fun('yesno', [$this, 'funcYesno']),

            new Fun('abs', 'abs'),
            new Fun('ceil', 'ceil'),
            new Fun('floor', 'floor'),
            new Fun('max', 'max'),
            new Fun('min', 'min'),
            new Fun('round', 'round'),
            new Fun('count', 'count'),
            new Fun('isset', 'isset'),
            new Fun('jsonencode', 'json_encode'),
            new Fun('jsondecode', 'json_decode'),
            new Fun('escapeshellarg', 'escapeshellarg'),
            new Fun('length', 'strlen'),
            new Fun('repeat', 'str_repeat'),
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

    /** type casting **/
    public function funcBool($term)
    {
        return '((bool)' . $term . ')';
    }

    public function funcInt($term)
    {
        return '((int)' . $term . ')';
    }

    public function funcFloat($term)
    {
        return '((float)' . $term . ')';
    }

    public function funcString($term)
    {
        return '((string)' . $term . ')';
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

    // string functions
    public function funcExplode($args)
    {
        return 'new \\Octris\\Core\\Type\\Collection(explode(' . implode(', ', $args) . '))';
    }

    public function funcImplode($args)
    {
        return '(implode(' . $args[0] . ', \\Octris\\Core\\Type::settype(' . $args[1] . ', "array")))';
    }

    public function funcLpad($args)
    {
        $args = $args + array(null, null, ' ');

        return '(str_pad(' . implode(', ', $args) . ', STR_PAD_LEFT))';
    }

    public function funcRpad($args)
    {
        $args = $args + array(null, null, ' ');

        return '(str_pad(' . implode(', ', $args) . ', STR_PAD_RIGHT))';
    }

    public function funcTotitle($args)
    {
        return '\\Octris\\Core\\Type\\String::convert_case(' . $args[0] . ', MB_CASE_TITLE)';
    }

    public function funcConcat($args)
    {
        return '(' . implode(' . ', $args) . ')';
    }

    // array functions
    public function funcArray($args)
    {
        return 'new \\Octris\\Core\\Type\\Collection(array(' . implode(', ', $args) . '))';
    }

    public function funcIn($args)
    {
        return 'in_array(' . $args[0] . ', \\Octris\\Core\\Type::setType(' . $args[1] . ', "array"))';
    }

    // localization functions
    public function funcComify($args)
    {
        return '($this->l10n->comify(' . implode(', ', $args) . '))';
    }

    public function funcEnum($args)
    {
        return '($this->l10n->enum(' . implode(', ', $args) . '))';
    }

    public function funcMonf($args)
    {
        return '($this->l10n->monf(' . implode(', ', $args) . '))';
    }

    public function funcNumf($args)
    {
        return '($this->l10n->numf(' . implode(', ', $args) . '))';
    }

    public function funcPerf($args)
    {
        return '($this->l10n->perf(' . implode(', ', $args) . '))';
    }

    public function funcDatef($args)
    {
        return '($this->l10n->datef(' . implode(', ', $args) . '))';
    }

    public function funcGender($args)
    {
        return '($this->l10n->gender(' . implode(', ', $args) . '))';
    }

    public function funcQuant($args)
    {
        return '($this->l10n->quant(' . implode(', ', $args) . '))';
    }

    public function funcYesno($args)
    {
        return '($this->l10n->yesno(' . implode(', ', $args) . '))';
    }
}
