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
            new Fun\Cycle('cycle'),
            new Fun\Trigger('trigger'),
            new Fun\OnChange('onchange'),
            
            new Block\Copy('copy'),
            new Block\Cut('cut')

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
            new Fun('collection', [$this, 'funcCollection']),
            new Fun('now', [$this, 'funcNow']),
            new Fun('uniqid', [$this, 'funcUniqid']),
            new Fun('Let', [$this, 'funcLet']),
            new Fun('ddump', [$this, 'funcDdump']),
            new Fun('dprint', [$this, 'funcDprint']),
            new Fun('error', [$this, 'funcError']),
            new Fun('include', [$this, 'funcInclude']),
            new Fun('explode', [$this, 'funcExplode']),
            new Fun('implode', [$this, 'funcImplode']),
            new Fun('lpad', [$this, 'funcLpad']),
            new Fun('rpad', [$this, 'funcRpad']),
            new Fun('totitle', [$this, 'funcTotitle']),
            new Fun('concat', [$this, 'funcConcat']),
            new Fun('array', [$this, 'funcArray']),
            new Fun('cycle', [$this, 'funcCycle']),
            new Fun('in', [$this, 'funcIn']),
            new Fun('escape', [$this, 'funcEscape']),
            new Fun('comify', [$this, 'funcComify']),
            new Fun('enum', [$this, 'funcEnum']),
            new Fun('monf', [$this, 'funcMonf']),
            new Fun('numf', [$this, 'funcNumf']),
            new Fun('perf', [$this, 'funcPerf']),
            new Fun('datef', [$this, 'funcDatef']),
            new Fun('gender', [$this, 'funcGender']),
            new Fun('quant', [$this, 'funcQuant']),
            new Fun('yesno', [$this, 'funcYesno']),
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

    /** standard functions to register **/
    public function funcIf($args)
    {
        return sprintf(
            '(%s ? %s : %s)',
            $args[0],
            $args[1],
            (count($args) == 3 ? $args[2] : '')
        );
    }

    public function funcIfset($args)
    {
        return sprintf(
            '(isset(%s) ? %s : %s)',
            $args[0],
            $args[1],
            (count($args) == 3 ? $args[2] : '')
        );
    }

    public function funcIfnull($args)
    {
        return sprintf(
            '(is_null(%s) ? %s : %s)',
            $args[0],
            $args[1],
            (count($args) == 3 ? $args[2] : '')
        );
    }

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
    public function funcMod($args)
    {
        return '(' . implode(' % ', $args) . ')';
    }

    public function funcAdd($args)
    {
        return '(' . implode(' + ', $args) . ')';
    }

    public function funcSub($args)
    {
        return '(' . implode(' - ', $args) . ')';
    }

    public function funcIncr($args)
    {
        return sprintf('(%s)', (count($args) == 2 ? $arg[0] . ' += ' + $args[1] : '++' . $args[0]));
    }

    public function funcDecr($args)
    {
        return sprintf('(%s)', (count($args) == 2 ? $arg[0] . ' -= ' + $args[1] : '--' . $args[0]));
    }

    public function funcAnd($args)
    {
        return '(' . implode(' && ', $args) . ')';
    }

    public function funcOr($args)
    {
        return '(' . implode(' || ', $args) . ')';
    }

    public function funcXor($args)
    {
        return sprintf('(%d != %d)', !!$args[0], !!$args[1]);
    }

    public function funcNot($args)
    {
        return '!' . $args[0];
    }

    public function funcLt($args)
    {
        return '(' . implode(' < ', $args) . ')';
    }

    public function funcGt($args)
    {
        return '(' . implode(' > ', $args) . ')';
    }

    public function funcEq($args)
    {
        return '(' . implode(' == ', $args) . ')';
    }

    public function funcLe($args)
    {
        return '(' . implode(' <= ', $args) . ')';
    }

    public function funcGe($args)
    {
        return '(' . implode(' >= ', $args) . ')';
    }

    public function funcNe($args)
    {
        return '(' . implode(' != ', $args) . ')';
    }

    public function funcBool($args)
    {
        return '((bool)' . $args[0] . ')';
    }

    public function funcInt($args)
    {
        return '((int)' . $args[0] . ')';
    }

    public function funcFloat($args)
    {
        return '((float)' . $args[0] . ')';
    }

    public function funcString($args)
    {
        return '((string)' . $args[0] . ')';
    }

    public function funcCollection($args)
    {
        return '\\Octris\\Core\\Type::settype(' . $args[0] . ', "collection")';
    }

    public function funcNow()
    {
        return '(time())';
    }

    public function funcUniqid()
    {
        return '(uniqid(mt_rand()))';
    }

    public function funcLet($args)
    {
        return '(' . implode(' = ', $args) . ')';
    }

    public function funcDdump($args)
    {
        return '\\Octris\\Debug::getInstance()->ddump(' . implode(', ', $args) . ')';
    }

    public function funcDprint($args)
    {
        return '\\Octris\\Debug::getInstance()->dprint(' . implode(', ', $args) . ')';
    }

    public function funcError($args)
    {
        return '$this->error(' . implode(', ', $args) . ', __LINE__)';
    }

    public function funcInclude($args)
    {
        return '$this->includetpl(' . implode('', $args) . ')';
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

    public function funcCycle($args)
    {
        return '($this->cycle("' . self::getUniqId() . '", ' . implode(', ', $args) . '))';
    }

    public function funcIn($args)
    {
        return 'in_array(' . $args[0] . ', \\Octris\\Core\\Type::setType(' . $args[1] . ', "array"))';
    }

    // misc functions
    public function funcEscape($args)
    {
        return '($this->escape(' . implode(', ', $args) . '))';
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
