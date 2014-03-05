<?php

/*
 * This file is part of the 'org.octris.core' package.
 *
 * (c) Harald Lapp <harald@octris.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace org\octris\core\tpl\compiler {
    use \org\octris\core\tpl\compiler as c;
    
    /**
     * Class for defining a template parser grammar.
     *
     * @octdoc      c:compiler/grammar
     * @copyright   copyright (c) 2014 by Harald Lapp
     * @author      Harald Lapp <harald@octris.org>
     */
    class grammar extends \org\octris\core\parser\grammar
    /**/
    {
        /**
         * Constructor.
         *
         * @octdoc  m:grammar/__construct
         */
        public function __construct()
        /**/
        {
            parent::__construct();
            
            // define tokens
            $this->addToken(c::T_IF_OPEN,     '#if');
            $this->addToken(c::T_IF_ELSE,     '#else');
            $this->addToken(c::T_BLOCK_CLOSE, '#end');
            $this->addToken(c::T_BLOCK_OPEN,  '#[a-z][a-z-0-9_]*');
            $this->addToken(c::T_BRACE_OPEN,  '\(');
            $this->addToken(c::T_BRACE_CLOSE, '\)');
            $this->addToken(c::T_PSEPARATOR,  '\,');
            $this->addToken(c::T_ESCAPE,      'escape(?=\()');
            $this->addToken(c::T_LET,         'let(?=\()');
            $this->addToken(c::T_GETTEXT,     '_(?=\()');
            $this->addToken(c::T_METHOD,      '[a-z_][a-z0-9_]*(?=\()');
            $this->addToken(c::T_BOOL,        '(true|false)');
            $this->addToken(c::T_VARIABLE,    '\$[a-z_][a-z0-9_]*(:\$?[a-z_][a-z0-9_]*|)+');
            $this->addToken(c::T_CONSTANT,    "%[_a-z][_a-z0-9]*");
            $this->addToken(c::T_MACRO,       "@[_a-z][_a-z0-9]*");
            $this->addToken(c::T_STRING,      "(?:(?:\"(?:\\\\\"|[^\"])*\")|(?:\'(?:\\\\\'|[^\'])*\'))");
            $this->addToken(c::T_NUMBER,      '[+-]?[0-9]+(\.[0-9]+|)');
            $this->addToken(c::T_NULL,        'null');
            $this->addToken(c::T_WHITESPACE,  '\s+');
            
            // define grammar rules
            $this->addRule(c::T_TYPE, ['$alternation' => [
                c::T_BOOL, c::T_NULL, c::T_NUMBER, c::T_STRING
            ]]);
            $this->addRule(c::T_PARAMETER, ['$alternation' => [
                c::T_METHOD, c::T_VARIABLE, c::T_CONSTANT, '<type>'
            ]]);
            $this->addRule(c::T_START, ['$alternation' => [
                c::T_BLOCK_OPEN, c::T_BLOCK_CLOSE, c::T_CONSTANT, c::T_ESCAPE,
                c::T_GETTEXT, c::T_IF_OPEN, c::T_IF_ELSE, c::T_LET,
                c::T_MACRO, c::T_METHOD, c::T_VARIABLE
            ]], true);
            $this->sddRule(c::T_BLOCK_OPEN, ['$concatenation' => [
                c::T_BRACE_OPEN,
                ['$option' => [
                    ['$concatenation' => [
                        c::T_PARAMETER,
                        ['$repeat' => [
                            ['$concatenation' => [
                                c::T_PSEPARATOR,
                                c::T_PARAMETER
                            ]]
                        ]]
                    ]]
                ]],
                c::T_BRACE_CLOSE
            ]]);
            $this->addRule(c::T_IF_OPEN, ['$concatenation' => [
                c::T_BRACE_OPEN,
                c::T_PARAMETER,
                ['$repeat' => [
                    ['$concatenation' => [
                        c::T_PSEPARATOR,
                        c::T_PARAMETER
                    ]]
                ]],
                c::T_BRACE_CLOSE
            ]]);
            $this->addRule(c::T_METHOD, ['$concatenation' => [
                c::T_BRACE_OPEN,
                ['$option' => [
                    ['$concatenation' => [
                        c::T_PARAMETER,
                        ['$repeat' => [
                            ['$concatenation' => [
                                c::T_PSEPARATOR,
                                c::T_PARAMETER
                            ]]
                        ]]
                    ]]
                ]],
                c::T_BRACE_CLOSE
            ]]);
            $this->addRule(c::T_ESCAPE, ['$concatenation' => [
                c::T_BRACE_OPEN,
                ['$alternation' => [
                    c::T_VARIABLE,
                    c::T_CONSTANT,
                    c::T_STRING
                ]],
                c::T_PSEPARATOR,
                c::T_CONSTANT,
                c::T_BRACE_CLOSE
            ]]);
            $this->addRule(c::T_LET, ['$concatenation' => [
                c::T_BRACE_OPEN,
                c::T_VARIABLE,
                c::T_PSEPARATOR,
                c::T_PARAMETER,
                c::T_BRACE_CLOSE
            ]]);
            $this->addRule(c::T_GETTEXT, ['$concatenation' => [
                c::T_BRACE_OPEN,
                ['$alternation' => [
                    c::T_CONSTANT,
                    c::T_STRING,
                    c::T_VARIABLE,
                ]],
                c::T_BRACE_CLOSE
            ]]);
            $this->addRule(c::T_MACRO, ['$concatenation' => [
                c::T_BRACE_OPEN,
                ['$option' => [
                    ['$concatenation' => [
                        ['$alternation' => [
                            c::T_CONSTANT,
                            c::T_TYPE
                        ]],
                        ['$repeat' => [
                            ['$concatenation' => [
                                c::T_PSEPARATOR,
                                ['$alternation' => [
                                    c::T_CONSTANT,
                                    c::T_TYPE
                                ]]
                            ]]
                        ]]
                    ]]
                ]],
                c::T_BRACE_CLOSE
            ]]);            
        }
    }
}
