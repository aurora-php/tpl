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
         * Known tokens.
         *
         * @octdoc  d:grammar/T_...
         * @type    string
         */
        const T_START           = '<start>';
        const T_TYPE            = '<type>';
        const T_PARAMETER       = '<parameter>';
        
        const T_BLOCK_OPEN      = '<block-open>';
        const T_BLOCK_CLOSE     = '<block-close>';
        const T_IF_OPEN         = '<if-open>';
        const T_IF_ELSE         = '<if-else>';
        const T_BRACE_OPEN      = '(';
        const T_BRACE_CLOSE     = ')';
        const T_PSEPARATOR      = ',';
    
        const T_METHOD          = '<method>';
        const T_LET             = '<let>';
        const T_VARIABLE        = '<variable>';
        const T_CONSTANT        = '<constant>';
        const T_MACRO           = '<macro>';
        const T_GETTEXT         = '<gettext>';
        const T_ESCAPE          = '<escape>';
    
        const T_STRING          = '<string>';
        const T_NUMBER          = '<number>';
        const T_BOOL            = '<bool>';
        const T_NULL            = '<null>';
        
        const T_WHITESPACE      = '<whitespace>';
        /**/
                
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
            $this->addToken(self::T_IF_OPEN,     '#if');
            $this->addToken(self::T_IF_ELSE,     '#else');
            $this->addToken(self::T_BLOCK_CLOSE, '#end');
            $this->addToken(self::T_BLOCK_OPEN,  '#[a-z][a-z-0-9_]*');
            $this->addToken(self::T_BRACE_OPEN,  '\(');
            $this->addToken(self::T_BRACE_CLOSE, '\)');
            $this->addToken(self::T_PSEPARATOR,  '\,');
            $this->addToken(self::T_ESCAPE,      'escape(?=\()');
            $this->addToken(self::T_LET,         'let(?=\()');
            $this->addToken(self::T_GETTEXT,     '_(?=\()');
            $this->addToken(self::T_METHOD,      '[a-z_][a-z0-9_]*(?=\()');
            $this->addToken(self::T_BOOL,        '(true|false)');
            $this->addToken(self::T_VARIABLE,    '\$[a-z_][a-z0-9_]*(:\$?[a-z_][a-z0-9_]*|)+');
            $this->addToken(self::T_CONSTANT,    "%[_a-z][_a-z0-9]*");
            $this->addToken(self::T_MACRO,       "@[_a-z][_a-z0-9]*");
            $this->addToken(self::T_STRING,      "(?:(?:\"(?:\\\\\"|[^\"])*\")|(?:\'(?:\\\\\'|[^\'])*\'))");
            $this->addToken(self::T_NUMBER,      '[+-]?[0-9]+(\.[0-9]+|)');
            $this->addToken(self::T_NULL,        'null');
            $this->addToken(self::T_WHITESPACE,  '\s+');
            
            // define grammar rules
            $this->addRule(self::T_TYPE, ['$alternation' => [
                self::T_BOOL, self::T_NULL, self::T_NUMBER, self::T_STRING
            ]]);
            $this->addRule(self::T_PARAMETER, ['$alternation' => [
                self::T_METHOD, self::T_VARIABLE, self::T_CONSTANT, '<type>'
            ]]);
            $this->addRule(self::T_START, ['$alternation' => [
                self::T_BLOCK_OPEN, self::T_BLOCK_CLOSE, self::T_CONSTANT, self::T_ESCAPE,
                self::T_GETTEXT, self::T_IF_OPEN, self::T_IF_ELSE, self::T_LET,
                self::T_MACRO, self::T_METHOD, self::T_VARIABLE
            ]], true);
            $this->sddRule(self::T_BLOCK_OPEN, ['$concatenation' => [
                self::T_BRACE_OPEN,
                ['$option' => [
                    ['$concatenation' => [
                        self::T_PARAMETER,
                        ['$repeat' => [
                            ['$concatenation' => [
                                self::T_PSEPARATOR,
                                self::T_PARAMETER
                            ]]
                        ]]
                    ]]
                ]],
                self::T_BRACE_CLOSE
            ]]);
            $this->addRule(self::T_IF_OPEN, ['$concatenation' => [
                self::T_BRACE_OPEN,
                self::T_PARAMETER,
                ['$repeat' => [
                    ['$concatenation' => [
                        self::T_PSEPARATOR,
                        self::T_PARAMETER
                    ]]
                ]],
                self::T_BRACE_CLOSE
            ]]);
            $this->addRule(self::T_METHOD, ['$concatenation' => [
                self::T_BRACE_OPEN,
                ['$option' => [
                    ['$concatenation' => [
                        self::T_PARAMETER,
                        ['$repeat' => [
                            ['$concatenation' => [
                                self::T_PSEPARATOR,
                                self::T_PARAMETER
                            ]]
                        ]]
                    ]]
                ]],
                self::T_BRACE_CLOSE
            ]]);
            $this->addRule(self::T_ESCAPE, ['$concatenation' => [
                self::T_BRACE_OPEN,
                ['$alternation' => [
                    self::T_VARIABLE,
                    self::T_CONSTANT,
                    self::T_STRING
                ]],
                self::T_PSEPARATOR,
                self::T_CONSTANT,
                self::T_BRACE_CLOSE
            ]]);
            $this->addRule(self::T_LET, ['$concatenation' => [
                self::T_BRACE_OPEN,
                self::T_VARIABLE,
                self::T_PSEPARATOR,
                self::T_PARAMETER,
                self::T_BRACE_CLOSE
            ]]);
            $this->addRule(self::T_GETTEXT, ['$concatenation' => [
                self::T_BRACE_OPEN,
                ['$alternation' => [
                    self::T_CONSTANT,
                    self::T_STRING,
                    self::T_VARIABLE,
                ]],
                self::T_BRACE_CLOSE
            ]]);
            $this->addRule(self::T_MACRO, ['$concatenation' => [
                self::T_BRACE_OPEN,
                ['$option' => [
                    ['$concatenation' => [
                        ['$alternation' => [
                            self::T_CONSTANT,
                            self::T_TYPE
                        ]],
                        ['$repeat' => [
                            ['$concatenation' => [
                                self::T_PSEPARATOR,
                                ['$alternation' => [
                                    self::T_CONSTANT,
                                    self::T_TYPE
                                ]]
                            ]]
                        ]]
                    ]]
                ]],
                self::T_BRACE_CLOSE
            ]]);            
        }
    }
}
