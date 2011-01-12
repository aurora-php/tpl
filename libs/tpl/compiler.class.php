<?php

namespace org\octris\core\tpl {
    use \org\octris\core\tpl\compiler as compiler;
    
    /**
     * Implementation of template compiler.
     *
     * @octdoc      c:tpl/compiler
     * @copyright   copyright (c) 2010-2011 by Harald Lapp
     * @author      Harald Lapp <harald@octris.org>
     */
    class compiler
    /**/
    {
        /**
         * Parser tokens.
         * 
         * @octdoc  d:compiler/T_...
         */
        const T_START           = 1;
        const T_END             = 2;
        const T_BLOCK_OPEN      = 3;
        const T_BLOCK_CLOSE     = 4;
        const T_IF_OPEN         = 5;
        const T_IF_ELSE         = 6;
    
        const T_BRACE_OPEN      = 10;
        const T_BRACE_CLOSE     = 11;
        const T_PSEPARATOR      = 12;
    
        const T_METHOD          = 20;
        const T_LET             = 21;
        const T_VARIABLE        = 22;
        const T_CONSTANT        = 23;
        const T_MACRO           = 24;
        const T_GETTEXT         = 25;
    
        const T_STRING          = 30;
        const T_NUMBER          = 31;
        const T_BOOL            = 32;
        
        const T_WHITESPACE      = 40;
        const T_NEWLINE         = 41;
        /**/

        /**
         * Regular expression patterns for parser tokens.
         *
         * @octdoc  v:compiler/$tokens
         * @var     array
         */
        private static $tokens = array(
            self::T_IF_OPEN     => '#if',
            self::T_IF_ELSE     => '#else',
            
            self::T_BLOCK_CLOSE => '#end',
            self::T_BLOCK_OPEN  => '#[a-z][a-z-0-9_]*',
            
            self::T_BRACE_OPEN  => '\(',
            self::T_BRACE_CLOSE => '\)',
            self::T_PSEPARATOR  => '\,',

            self::T_LET         => 'let',
            self::T_GETTEXT     => '_',
            self::T_METHOD      => '[a-z_][a-z0-9_]*',
            self::T_VARIABLE    => '\$[a-z_][a-z0-9_]*(:\$?[a-z_][a-z0-9_]*|)+',
            self::T_CONSTANT    => "%[_a-z][_a-z0-9]*",
            self::T_MACRO       => "@[_a-z][_a-z0-9]*",
        
            self::T_STRING      => "([\"']).*?(?!\\\\)\\2",
            self::T_NUMBER      => '[+-]?[0-9]+(\.[0-9]+|)',
            self::T_BOOL        => '(true|false)',
            
            self::T_WHITESPACE  => '\s+',
            self::T_NEWLINE     => '\n+',
        );
        /**/

        /**
         * Template analyzer rules.
         *
         * @octdoc  v:compiler/$rules
         * @octdoc  private static $rules = array(...);
         * @var     array
         */
        private static $rules = array(
            self::T_START   => array(
                self::T_END     => true,
            
                /* T_BLOCK_OPEN */
                self::T_BLOCK_OPEN  => array(
                    self::T_BRACE_OPEN  => array(
                        self::T_LET         => NULL,
                        self::T_METHOD      => NULL, 
                        self::T_VARIABLE    => array(
                            self::T_PSEPARATOR  => array(
                                self::T_LET         => NULL,
                                self::T_METHOD      => NULL,
                                self::T_VARIABLE    => NULL,
                                self::T_CONSTANT    => NULL, 
                                self::T_STRING      => NULL, 
                                self::T_NUMBER      => NULL,
                                self::T_BOOL        => NULL,
                            ), 
                            self::T_BRACE_CLOSE => NULL
                        ), 
                        self::T_CONSTANT    => array(
                            self::T_PSEPARATOR  => array(
                                self::T_LET         => NULL,
                                self::T_METHOD      => NULL,
                                self::T_VARIABLE    => NULL,
                                self::T_CONSTANT    => NULL, 
                                self::T_STRING      => NULL, 
                                self::T_NUMBER      => NULL,
                                self::T_BOOL        => NULL,
                            ), 
                            self::T_BRACE_CLOSE => NULL
                        ),
                        self::T_STRING      => array(
                            self::T_PSEPARATOR  => array(
                                self::T_LET         => NULL,
                                self::T_METHOD      => NULL,
                                self::T_VARIABLE    => NULL,
                                self::T_CONSTANT    => NULL, 
                                self::T_STRING      => NULL, 
                                self::T_NUMBER      => NULL,
                                self::T_BOOL        => NULL,
                            ), 
                            self::T_BRACE_CLOSE => NULL
                        ),
                        self::T_NUMBER      => array(
                            self::T_PSEPARATOR  => array(
                                self::T_LET         => NULL,
                                self::T_METHOD      => NULL,
                                self::T_VARIABLE    => NULL,
                                self::T_CONSTANT    => NULL, 
                                self::T_STRING      => NULL, 
                                self::T_NUMBER      => NULL,
                                self::T_BOOL        => NULL,
                            ), 
                            self::T_BRACE_CLOSE => NULL
                        ),
                        self::T_BOOL        => array(
                            self::T_PSEPARATOR  => array(
                                self::T_LET         => NULL,
                                self::T_METHOD      => NULL,
                                self::T_VARIABLE    => NULL,
                                self::T_CONSTANT    => NULL, 
                                self::T_STRING      => NULL, 
                                self::T_NUMBER      => NULL,
                                self::T_BOOL        => NULL,
                            ), 
                            self::T_BRACE_CLOSE => NULL
                        ),
                        self::T_BRACE_CLOSE => array(
                            self::T_BRACE_CLOSE => NULL, 
                            self::T_PSEPARATOR  => NULL, 
                            self::T_END         => NULL
                        )
                    )
                ),

                /* T_IF_OPEN */
                self::T_IF_OPEN  => array(
                    self::T_BRACE_OPEN  => array(
                        self::T_LET         => NULL,
                        self::T_METHOD      => NULL, 
                        self::T_VARIABLE    => array(
                            self::T_PSEPARATOR  => array(
                                self::T_LET         => NULL,
                                self::T_METHOD      => NULL,
                                self::T_VARIABLE    => NULL,
                                self::T_CONSTANT    => NULL, 
                                self::T_STRING      => NULL, 
                                self::T_NUMBER      => NULL,
                                self::T_BOOL        => NULL,
                            ), 
                            self::T_BRACE_CLOSE => NULL
                        ), 
                        self::T_CONSTANT    => array(
                            self::T_PSEPARATOR  => array(
                                self::T_LET         => NULL,
                                self::T_METHOD      => NULL,
                                self::T_VARIABLE    => NULL,
                                self::T_CONSTANT    => NULL, 
                                self::T_STRING      => NULL, 
                                self::T_NUMBER      => NULL,
                                self::T_BOOL        => NULL,
                            ), 
                            self::T_BRACE_CLOSE => NULL
                        ),
                        self::T_STRING      => array(
                            self::T_PSEPARATOR  => array(
                                self::T_LET         => NULL,
                                self::T_METHOD      => NULL,
                                self::T_VARIABLE    => NULL,
                                self::T_CONSTANT    => NULL, 
                                self::T_STRING      => NULL, 
                                self::T_NUMBER      => NULL,
                                self::T_BOOL        => NULL,
                            ), 
                            self::T_BRACE_CLOSE => NULL
                        ),
                        self::T_NUMBER      => array(
                            self::T_PSEPARATOR  => array(
                                self::T_LET         => NULL,
                                self::T_METHOD      => NULL,
                                self::T_VARIABLE    => NULL,
                                self::T_CONSTANT    => NULL, 
                                self::T_STRING      => NULL, 
                                self::T_NUMBER      => NULL,
                                self::T_BOOL        => NULL,
                            ), 
                            self::T_BRACE_CLOSE => NULL
                        ),
                        self::T_BOOL        => array(
                            self::T_PSEPARATOR  => array(
                                self::T_LET         => NULL,
                                self::T_METHOD      => NULL,
                                self::T_VARIABLE    => NULL,
                                self::T_CONSTANT    => NULL, 
                                self::T_STRING      => NULL, 
                                self::T_NUMBER      => NULL,
                                self::T_BOOL        => NULL,
                            ), 
                            self::T_BRACE_CLOSE => NULL
                        ),
                        self::T_BRACE_CLOSE => array(
                            self::T_BRACE_CLOSE => NULL, 
                            self::T_PSEPARATOR  => NULL, 
                            self::T_END         => NULL
                        )
                    )
                ),
            
                // T_BLOCK_CLOSE, T_IF_ELSE, T_VARIABLE, T_CONSTANT, T_STRING, T_NUMBER, T_BOOL
                self::T_BLOCK_CLOSE => array(self::T_END => NULL),
                self::T_IF_ELSE     => array(self::T_END => NULL),
                self::T_VARIABLE    => array(self::T_END => NULL),
                self::T_CONSTANT    => array(self::T_END => NULL),
            
                // method : method(... [, ...])
                self::T_METHOD  => array(
                    self::T_BRACE_OPEN  => array(
                        self::T_LET         => NULL,
                        self::T_METHOD      => NULL, 
                        self::T_VARIABLE    => array(
                            self::T_PSEPARATOR  => array(
                                self::T_LET         => NULL,
                                self::T_METHOD      => NULL,
                                self::T_VARIABLE    => NULL,
                                self::T_CONSTANT    => NULL, 
                                self::T_STRING      => NULL, 
                                self::T_NUMBER      => NULL,
                                self::T_BOOL        => NULL,
                            ), 
                            self::T_BRACE_CLOSE => NULL
                        ), 
                        self::T_CONSTANT    => array(
                            self::T_PSEPARATOR  => array(
                                self::T_LET         => NULL,
                                self::T_METHOD      => NULL,
                                self::T_VARIABLE    => NULL,
                                self::T_CONSTANT    => NULL, 
                                self::T_STRING      => NULL, 
                                self::T_NUMBER      => NULL,
                                self::T_BOOL        => NULL,
                            ), 
                            self::T_BRACE_CLOSE => NULL
                        ),
                        self::T_STRING      => array(
                            self::T_PSEPARATOR  => array(
                                self::T_LET         => NULL,
                                self::T_METHOD      => NULL,
                                self::T_VARIABLE    => NULL,
                                self::T_CONSTANT    => NULL, 
                                self::T_STRING      => NULL, 
                                self::T_NUMBER      => NULL,
                                self::T_BOOL        => NULL,
                            ), 
                            self::T_BRACE_CLOSE => NULL
                        ),
                        self::T_NUMBER      => array(
                            self::T_PSEPARATOR  => array(
                                self::T_LET         => NULL,
                                self::T_METHOD      => NULL,
                                self::T_VARIABLE    => NULL,
                                self::T_CONSTANT    => NULL, 
                                self::T_STRING      => NULL, 
                                self::T_NUMBER      => NULL,
                                self::T_BOOL        => NULL,
                            ), 
                            self::T_BRACE_CLOSE => NULL
                        ),
                        self::T_BOOL        => array(
                            self::T_PSEPARATOR  => array(
                                self::T_LET         => NULL,
                                self::T_METHOD      => NULL,
                                self::T_VARIABLE    => NULL,
                                self::T_CONSTANT    => NULL, 
                                self::T_STRING      => NULL, 
                                self::T_NUMBER      => NULL,
                                self::T_BOOL        => NULL,
                            ), 
                            self::T_BRACE_CLOSE => NULL
                        ),
                        self::T_BRACE_CLOSE => array(
                            self::T_BRACE_CLOSE => NULL, 
                            self::T_PSEPARATOR  => array(
                                self::T_LET         => NULL,
                                self::T_METHOD      => NULL,
                                self::T_VARIABLE    => NULL,
                                self::T_CONSTANT    => NULL, 
                                self::T_STRING      => NULL, 
                                self::T_NUMBER      => NULL,
                                self::T_BOOL        => NULL,
                            ), 
                            self::T_END         => NULL
                        )
                    )
                ),
        
                // let : let($..., ...)
                self::T_LET  => array(
                    self::T_BRACE_OPEN  => array(
                        self::T_VARIABLE    => array(
                            self::T_PSEPARATOR  => array(
                                self::T_LET         => NULL,
                                self::T_METHOD      => NULL,
                                self::T_VARIABLE    => NULL,
                                self::T_CONSTANT    => array(
                                    self::T_PSEPARATOR  => array(
                                        self::T_LET         => NULL,
                                        self::T_METHOD      => NULL,
                                        self::T_VARIABLE    => NULL,
                                        self::T_CONSTANT    => NULL, 
                                        self::T_STRING      => NULL, 
                                        self::T_NUMBER      => NULL,
                                        self::T_BOOL        => NULL,
                                    ), 
                                    self::T_BRACE_CLOSE => NULL
                                ),
                                self::T_STRING      => array(
                                    self::T_PSEPARATOR  => array(
                                        self::T_LET         => NULL,
                                        self::T_METHOD      => NULL,
                                        self::T_VARIABLE    => NULL,
                                        self::T_CONSTANT    => NULL, 
                                        self::T_STRING      => NULL, 
                                        self::T_NUMBER      => NULL,
                                        self::T_BOOL        => NULL,
                                    ), 
                                    self::T_BRACE_CLOSE => NULL
                                ),
                                self::T_NUMBER      => array(
                                    self::T_PSEPARATOR  => array(
                                        self::T_LET         => NULL,
                                        self::T_METHOD      => NULL,
                                        self::T_VARIABLE    => NULL,
                                        self::T_CONSTANT    => NULL, 
                                        self::T_STRING      => NULL, 
                                        self::T_NUMBER      => NULL,
                                        self::T_BOOL        => NULL,
                                    ), 
                                    self::T_BRACE_CLOSE => NULL
                                ),
                                self::T_BOOL        => array(
                                    self::T_PSEPARATOR  => array(
                                        self::T_LET         => NULL,
                                        self::T_METHOD      => NULL,
                                        self::T_VARIABLE    => NULL,
                                        self::T_CONSTANT    => NULL, 
                                        self::T_STRING      => NULL, 
                                        self::T_NUMBER      => NULL,
                                        self::T_BOOL        => NULL,
                                    ), 
                                    self::T_BRACE_CLOSE => NULL
                                ),
                            ), 
                            self::T_BRACE_CLOSE => NULL
                        ), 
                        self::T_BRACE_CLOSE => array(
                            self::T_BRACE_CLOSE => NULL, 
                            self::T_PSEPARATOR  => array(
                                self::T_LET         => NULL,
                                self::T_METHOD      => NULL,
                                self::T_VARIABLE    => NULL,
                                self::T_CONSTANT    => array(
                                    self::T_PSEPARATOR  => array(
                                        self::T_LET         => NULL,
                                        self::T_METHOD      => NULL,
                                        self::T_VARIABLE    => NULL,
                                        self::T_CONSTANT    => NULL, 
                                        self::T_STRING      => NULL, 
                                        self::T_NUMBER      => NULL,
                                        self::T_BOOL        => NULL,
                                    ), 
                                    self::T_BRACE_CLOSE => NULL
                                ),
                                self::T_STRING      => array(
                                    self::T_PSEPARATOR  => array(
                                        self::T_LET         => NULL,
                                        self::T_METHOD      => NULL,
                                        self::T_VARIABLE    => NULL,
                                        self::T_CONSTANT    => NULL, 
                                        self::T_STRING      => NULL, 
                                        self::T_NUMBER      => NULL,
                                        self::T_BOOL        => NULL,
                                    ), 
                                    self::T_BRACE_CLOSE => NULL
                                ),
                                self::T_NUMBER      => array(
                                    self::T_PSEPARATOR  => array(
                                        self::T_LET         => NULL,
                                        self::T_METHOD      => NULL,
                                        self::T_VARIABLE    => NULL,
                                        self::T_CONSTANT    => NULL, 
                                        self::T_STRING      => NULL, 
                                        self::T_NUMBER      => NULL,
                                        self::T_BOOL        => NULL,
                                    ), 
                                    self::T_BRACE_CLOSE => NULL
                                ),
                                self::T_BOOL        => array(
                                    self::T_PSEPARATOR  => array(
                                        self::T_LET         => NULL,
                                        self::T_METHOD      => NULL,
                                        self::T_VARIABLE    => NULL,
                                        self::T_CONSTANT    => NULL, 
                                        self::T_STRING      => NULL, 
                                        self::T_NUMBER      => NULL,
                                        self::T_BOOL        => NULL,
                                    ), 
                                    self::T_BRACE_CLOSE => NULL
                                ),
                            ), 
                            self::T_END         => NULL
                        )
                    )
                ),
        
                // gettext : _([$... | "..." | %...], ...)
                self::T_GETTEXT  => array(
                    self::T_BRACE_OPEN  => array(
                        self::T_VARIABLE    => array(
                            self::T_PSEPARATOR  => array(
                                self::T_LET         => NULL,
                                self::T_METHOD      => NULL,
                                self::T_VARIABLE    => NULL,
                                self::T_CONSTANT    => array(
                                    self::T_PSEPARATOR  => array(
                                        self::T_LET         => NULL,
                                        self::T_METHOD      => NULL,
                                        self::T_VARIABLE    => NULL,
                                        self::T_CONSTANT    => NULL, 
                                        self::T_STRING      => NULL, 
                                        self::T_NUMBER      => NULL,
                                        self::T_BOOL        => NULL,
                                    ), 
                                    self::T_BRACE_CLOSE => NULL
                                ),
                                self::T_STRING      => array(
                                    self::T_PSEPARATOR  => array(
                                        self::T_LET         => NULL,
                                        self::T_METHOD      => NULL,
                                        self::T_VARIABLE    => NULL,
                                        self::T_CONSTANT    => NULL, 
                                        self::T_STRING      => NULL, 
                                        self::T_NUMBER      => NULL,
                                        self::T_BOOL        => NULL,
                                    ), 
                                    self::T_BRACE_CLOSE => NULL
                                ),
                                self::T_NUMBER      => array(
                                    self::T_PSEPARATOR  => array(
                                        self::T_LET         => NULL,
                                        self::T_METHOD      => NULL,
                                        self::T_VARIABLE    => NULL,
                                        self::T_CONSTANT    => NULL, 
                                        self::T_STRING      => NULL, 
                                        self::T_NUMBER      => NULL,
                                        self::T_BOOL        => NULL,
                                    ), 
                                    self::T_BRACE_CLOSE => NULL
                                ),
                                self::T_BOOL        => array(
                                    self::T_PSEPARATOR  => array(
                                        self::T_LET         => NULL,
                                        self::T_METHOD      => NULL,
                                        self::T_VARIABLE    => NULL,
                                        self::T_CONSTANT    => NULL, 
                                        self::T_STRING      => NULL, 
                                        self::T_NUMBER      => NULL,
                                        self::T_BOOL        => NULL,
                                    ), 
                                    self::T_BRACE_CLOSE => NULL
                                ),
                            ), 
                            self::T_BRACE_CLOSE => NULL
                        ), 
                        self::T_CONSTANT    => array(
                            self::T_PSEPARATOR  => array(
                                self::T_LET         => NULL,
                                self::T_METHOD      => NULL,
                                self::T_VARIABLE    => NULL,
                                self::T_CONSTANT    => array(
                                    self::T_PSEPARATOR  => array(
                                        self::T_LET         => NULL,
                                        self::T_METHOD      => NULL,
                                        self::T_VARIABLE    => NULL,
                                        self::T_CONSTANT    => NULL, 
                                        self::T_STRING      => NULL, 
                                        self::T_NUMBER      => NULL,
                                        self::T_BOOL        => NULL,
                                    ), 
                                    self::T_BRACE_CLOSE => NULL
                                ),
                                self::T_STRING      => array(
                                    self::T_PSEPARATOR  => array(
                                        self::T_LET         => NULL,
                                        self::T_METHOD      => NULL,
                                        self::T_VARIABLE    => NULL,
                                        self::T_CONSTANT    => NULL, 
                                        self::T_STRING      => NULL, 
                                        self::T_NUMBER      => NULL,
                                        self::T_BOOL        => NULL,
                                    ), 
                                    self::T_BRACE_CLOSE => NULL
                                ),
                                self::T_NUMBER      => array(
                                    self::T_PSEPARATOR  => array(
                                        self::T_LET         => NULL,
                                        self::T_METHOD      => NULL,
                                        self::T_VARIABLE    => NULL,
                                        self::T_CONSTANT    => NULL, 
                                        self::T_STRING      => NULL, 
                                        self::T_NUMBER      => NULL,
                                        self::T_BOOL        => NULL,
                                    ), 
                                    self::T_BRACE_CLOSE => NULL
                                ),
                                self::T_BOOL        => array(
                                    self::T_PSEPARATOR  => array(
                                        self::T_LET         => NULL,
                                        self::T_METHOD      => NULL,
                                        self::T_VARIABLE    => NULL,
                                        self::T_CONSTANT    => NULL, 
                                        self::T_STRING      => NULL, 
                                        self::T_NUMBER      => NULL,
                                        self::T_BOOL        => NULL,
                                    ), 
                                    self::T_BRACE_CLOSE => NULL
                                ),
                            ), 
                            self::T_BRACE_CLOSE => NULL
                        ), 
                        self::T_STRING    => array(
                            self::T_PSEPARATOR  => array(
                                self::T_LET         => NULL,
                                self::T_METHOD      => NULL,
                                self::T_VARIABLE    => NULL,
                                self::T_CONSTANT    => array(
                                    self::T_PSEPARATOR  => array(
                                        self::T_LET         => NULL,
                                        self::T_METHOD      => NULL,
                                        self::T_VARIABLE    => NULL,
                                        self::T_CONSTANT    => NULL, 
                                        self::T_STRING      => NULL, 
                                        self::T_NUMBER      => NULL,
                                        self::T_BOOL        => NULL,
                                    ), 
                                    self::T_BRACE_CLOSE => NULL
                                ),
                                self::T_STRING      => array(
                                    self::T_PSEPARATOR  => array(
                                        self::T_LET         => NULL,
                                        self::T_METHOD      => NULL,
                                        self::T_VARIABLE    => NULL,
                                        self::T_CONSTANT    => NULL, 
                                        self::T_STRING      => NULL, 
                                        self::T_NUMBER      => NULL,
                                        self::T_BOOL        => NULL,
                                    ), 
                                    self::T_BRACE_CLOSE => NULL
                                ),
                                self::T_NUMBER      => array(
                                    self::T_PSEPARATOR  => array(
                                        self::T_LET         => NULL,
                                        self::T_METHOD      => NULL,
                                        self::T_VARIABLE    => NULL,
                                        self::T_CONSTANT    => NULL, 
                                        self::T_STRING      => NULL, 
                                        self::T_NUMBER      => NULL,
                                        self::T_BOOL        => NULL,
                                    ), 
                                    self::T_BRACE_CLOSE => NULL
                                ),
                                self::T_BOOL        => array(
                                    self::T_PSEPARATOR  => array(
                                        self::T_LET         => NULL,
                                        self::T_METHOD      => NULL,
                                        self::T_VARIABLE    => NULL,
                                        self::T_CONSTANT    => NULL, 
                                        self::T_STRING      => NULL, 
                                        self::T_NUMBER      => NULL,
                                        self::T_BOOL        => NULL,
                                    ), 
                                    self::T_BRACE_CLOSE => NULL
                                ),
                            ), 
                            self::T_BRACE_CLOSE => NULL
                        ), 
                        self::T_BRACE_CLOSE => array(
                            self::T_BRACE_CLOSE => NULL, 
                            self::T_PSEPARATOR  => array(
                                self::T_LET         => NULL,
                                self::T_METHOD      => NULL,
                                self::T_VARIABLE    => NULL,
                                self::T_CONSTANT    => array(
                                    self::T_PSEPARATOR  => array(
                                        self::T_LET         => NULL,
                                        self::T_METHOD      => NULL,
                                        self::T_VARIABLE    => NULL,
                                        self::T_CONSTANT    => NULL, 
                                        self::T_STRING      => NULL, 
                                        self::T_NUMBER      => NULL,
                                        self::T_BOOL        => NULL,
                                    ), 
                                    self::T_BRACE_CLOSE => NULL
                                ),
                                self::T_STRING      => array(
                                    self::T_PSEPARATOR  => array(
                                        self::T_LET         => NULL,
                                        self::T_METHOD      => NULL,
                                        self::T_VARIABLE    => NULL,
                                        self::T_CONSTANT    => NULL, 
                                        self::T_STRING      => NULL, 
                                        self::T_NUMBER      => NULL,
                                        self::T_BOOL        => NULL,
                                    ), 
                                    self::T_BRACE_CLOSE => NULL
                                ),
                                self::T_NUMBER      => array(
                                    self::T_PSEPARATOR  => array(
                                        self::T_LET         => NULL,
                                        self::T_METHOD      => NULL,
                                        self::T_VARIABLE    => NULL,
                                        self::T_CONSTANT    => NULL, 
                                        self::T_STRING      => NULL, 
                                        self::T_NUMBER      => NULL,
                                        self::T_BOOL        => NULL,
                                    ), 
                                    self::T_BRACE_CLOSE => NULL
                                ),
                                self::T_BOOL        => array(
                                    self::T_PSEPARATOR  => array(
                                        self::T_LET         => NULL,
                                        self::T_METHOD      => NULL,
                                        self::T_VARIABLE    => NULL,
                                        self::T_CONSTANT    => NULL, 
                                        self::T_STRING      => NULL, 
                                        self::T_NUMBER      => NULL,
                                        self::T_BOOL        => NULL,
                                    ), 
                                    self::T_BRACE_CLOSE => NULL
                                ),
                            ), 
                            self::T_END         => NULL
                        )
                    )
                ),

                // macro : @macro(... [, ...])
                self::T_MACRO   => array(
                    self::T_BRACE_OPEN  => array(
                        self::T_CONSTANT    => array(
                            self::T_PSEPARATOR  => array(
                                self::T_CONSTANT => NULL, 
                                self::T_STRING   => NULL, 
                                self::T_NUMBER   => NULL, 
                                self::T_BOOL     => NULL
                            ), 
                            self::T_BRACE_CLOSE => NULL
                        ),
                        self::T_STRING      => array(
                            self::T_PSEPARATOR  => array(
                                self::T_CONSTANT => NULL, 
                                self::T_STRING   => NULL, 
                                self::T_NUMBER   => NULL, 
                                self::T_BOOL     => NULL
                            ), 
                            self::T_BRACE_CLOSE => NULL
                        ), 
                        self::T_NUMBER      => array(
                            self::T_PSEPARATOR  => array(
                                self::T_CONSTANT => NULL, 
                                self::T_STRING   => NULL, 
                                self::T_NUMBER   => NULL, 
                                self::T_BOOL     => NULL
                            ), 
                            self::T_BRACE_CLOSE => NULL
                        ), 
                        self::T_BOOL        => array(
                            self::T_PSEPARATOR  => array(
                                self::T_CONSTANT => NULL, 
                                self::T_STRING   => NULL, 
                                self::T_NUMBER   => NULL, 
                                self::T_BOOL     => NULL
                            ), 
                            self::T_BRACE_CLOSE => NULL
                        ), 
                        self::T_BRACE_CLOSE => array(
                            self::T_END => NULL
                        )
                    )
                )
            )
        );
        /**/
        
        /**
         * Names of tokens. This array gets build the first time the constructor is called.
         *
         * @octdoc  v:compiler/$tokennames
         * @var     array
         */
        private static $tokennames = NULL;
        /**/

        /**
         * Name of file currently compiled.
         *
         * @octdoc  v:compiler/$filename
         * @var     string
         */
        protected $filename = '';
        /**/

        /**
         * Stores pathes to look into when searching for template to load.
         *
         * @octdoc  v:compiler/$searchpath
         * @var     array
         */
        protected $searchpath = array();
        /**/

        /**
         * Instance of locale class.
         *
         * @octdoc  v:compiler/$l10n
         * @var     \org\octris\core\l10n
         */
        protected $l10n;
        /**/

        /**
         * Constructor.
         *
         * @octdoc  m:compiler/__construct
         */
        public function __construct()
        /**/
        {
            if (is_null(self::$tokennames)) {
                $class = new \ReflectionClass($this);
                self::$tokennames = array_flip($class->getConstants());
            }
        }

        /**
         * Set l10n dependency.
         *
         * @octdoc  m:compiler/setL10n
         * @param   \org\octris\core\l10n       $l10n       Instance of l10n class.
         */
        public function setL10n(\org\octris\core\l10n $l10n)
        /**/
        {
            $this->l10n = $l10n;
        }

        /**
         * Register pathname for looking up templates in.
         *
         * @octdoc  m:compiler/addSearchPath
         * @param   mixed       $pathname       Name of path to register.
         */
        public function addSearchPath($pathname)
        /**/
        {
            if (!in_array($pathname, $this->searchpath)) {
                $this->searchpath[] = $pathname;
            }
        }
        
        /**
         * Lookup a template file in the configured searchpathes.
         *
         * @octdoc  m:compiler/findFile
         * @param   string      $filename       Name of file to lookup.
         */
        public function findFile($filename)
        /**/
        {
            $return = false;
            
            foreach ($this->searchpath as $path) {
                $test = $path . '/' . $filename;
                
                if (file_exists($test) && is_readable($test)) {
                    if (($dir = dirname($filename)) !== '') {
                        // add complete path of file for future relativ path lookups
                        $this->addSearchPath($path . '/' . $dir);
                    }
                    
                    $return = $test;
                    break;
                }
            }
            
            return $return;
        }

        /**
         * Return name of token.
         *
         * @octdoc  m:compiler/getTokenName
         * @param   int     $token      ID of token.
         * @return  string              Name of token.
         */
        protected function getTokenName($token)
        /**/
        {
            return (isset(self::$tokennames[$token])
                    ? self::$tokennames[$token]
                    : 'T_UNKNOWN');
        }

        /**
         * Return names of multiple tokens.
         *
         * @octdoc  m:compiler/getTokenNames
         * @param   array       $tokens     Array of token IDs.
         * @return  array                   Names of tokens.
         */
        protected function getTokenNames(array $tokens)
        /**/
        {
            $return = array();
            
            foreach ($tokens as $token) $return[] = $this->getTokenName($token);
            
            return $return;
        }
        
        /**
         * Trigger an error and halt execution.
         *
         * @octdoc  m:compiler/error
         * @param   string      $type       Type of error to trigger.
         * @param   int         $cline      Line in compiler class error was triggered from.
         * @param   int         $line       Line in template the error was triggered for.
         * @param   int         $token      ID of token that triggered the error.
         * @param   mixed       $payload    Optional additional information. Either an array of expected token IDs or an additional message to output.
         */
        protected function error($type, $cline, $line, $token, $payload = NULL)
        /**/
        {
            printf("\n** ERROR: %s(%d) **\n", $type, $cline);
            printf("   line :    %d\n", $line);
            printf("   file:     %s\n", $this->filename);
            printf("   token:    %s\n", $this->getTokenName($token));
            
            if (is_array($payload)) {
                printf("   expected: %s\n", implode(', ', $this->getTokenNames(array_keys($payload))));
            } elseif (isset($payload)) {
                printf("   message:  %s\n", $payload);
            }
         
            die();
        }

        /**
         * Tokenizer converts template snippets to tokens.
         *
         * @octdoc  m:compiler/tokenize
         * @param   string      $in         Template snippet to tokenize.
         * @param   int         $line       Line number of template the snippet was taken from.
         * @return  array                   Tokens parsed from snippet.
         */
        protected function tokenize($in, $line)
        /**/
        {
            $out = array();
            $in  = stripslashes($in);

            while (strlen($in) > 0) {
                foreach (self::$tokens as $token => $regexp) {
                    if (preg_match('/^(' . $regexp . ')/i', $in, $m)) {
                        if ($token != self::T_WHITESPACE) {
                            // spaces between tokens are ignored
                            $out[] = array(
                                'token' => $token,
                                'value' => $m[1],
                                'file'  => $this->filename,
                                'line'  => $line
                            );
                        }

                        $in = substr($in, strlen($m[1]));
                        continue 2;
                    }
                }
                
                $this->error(__FUNCTION__, __LINE__, $line, 0, sprintf('parse error at "%s"', $in));
            }

            if (count($out) > 0) {
                array_unshift($out, array(
                    'token' => self::T_START,
                    'value' => '',
                    'file'  => $this->filename,
                    'line'  => $line
                ));
                array_push($out, array(
                    'token' => self::T_END,
                    'value' => '',
                    'file'  => $this->filename,
                    'line'  => $line
                ));
            }

            return $out;
        }

        /**
         * Token analyzer. The analyzer applies rulesets to tokens and checks if
         * the rules are fulfilled.
         *
         * @octdoc  m:compiler/analyze
         * @param   array       $tokens     Tokens to analyze.
         * @param   array       $blocks     Block information required by analyzer / compiler.
         * @return  bool                    Returns true if token analysis succeeded.
         */
        protected function analyze(array $tokens, array &$blocks)
        /**/
        {
            $braces  = 0;               // brace level
            $current = null;            // current token
            
            $rule    = self::$rules;
            $stack   = array();
            
            /*
             * retrieve next rule
             */
            $get_next_rule = function($rule, $token) use (&$stack) {
                $return = false;
                
                if (is_array($rule) && array_key_exists($token, $rule)) {
                    // valid token, because it's in current ruleset
                    if (is_array($rule[$token])) {
                        // push current rule on stack and get child rule
                        $stack[] = $rule;
                        $return  = $rule[$token];
                    } elseif (is_null($rule[$token])) {
                        // ruleset is null -> try to get it from parent rules
                        while (($return = array_pop($stack)) && !isset($return[$token]));

                        if (is_array($return)) {
                            $stack[] = $return;
                            $return  = $return[$token];
                        }
                    }
                }

                return $return;
            };
            
            foreach ($tokens as $current) {
                extract($current);
                
                switch ($token) {
                case self::T_BRACE_OPEN:
                    // opening '(' brace
                    ++$braces;
                    break;
                case self::T_BRACE_CLOSE:
                    // closing ')' brace -- only allowed, if a brace was opened previously
                    if ($braces == 0) {
                        $this->error(__FUNCTION__, __LINE__, $line, $token);
                    } else {
                        --$braces;
                    }
                    break;
                case self::T_PSEPARATOR:
                    // ',' is only allowed to separate arguments
                    if ($braces == 0) $this->error(__FUNCTION__, __LINE__, $line, $token);
                    break;
                case self::T_IF_OPEN:
                    // opening if
                    
                    /** FALL THRU **/
                case self::T_BLOCK_OPEN:
                    // opening block
                    $blocks['analyzer'][] = $current;
                    break;
                case self::T_BLOCK_CLOSE:
                    // closing block only allowed is a block is open
                    if (!($block = array_pop($blocks['analyzer']))) {
                        $this->error(__FUNCTION__, __LINE__, $line, $token, 'there is no open block');
                    }
                    break;
                case self::T_IF_ELSE:
                    // else is only allowed within an 'if' block
                    if ((($cnt = count($blocks['analyzer'])) > 0 && $blocks['analyzer'][$cnt - 1]['token'] != self::T_IF_OPEN)) {
                        $this->error(__FUNCTION__, __LINE__, $line, $token, 'only allowed inside an "if" block');
                    } else {
                        $blocks['analyzer'][$cnt - 1]['token'] = self::T_IF_ELSE;
                    }
                    break;
                }

                if (!($tmp = $get_next_rule($rule, $token))) {
                    $this->error(__FUNCTION__, __LINE__, $line, $token, $rule);
                }
                
                $rule = $tmp;
            }

            return true;
        }

        /**
         * Implementation of gettext compiler.
         *
         * @octdoc  m:compiler/gettext
         * @param   array       $args       Arguments for gettext.
         * @return  string                  Compiled code for gettext.
         */
        protected function gettext($args)
        /**/
        {
            if (preg_match('/^(["\'])(.*?)\1$/', $args[0], $match)) {
                $pattern = '/\[(?:(_\d+)|(?:([^,]+))(?:,(.*?))?(?<!\\\))\]/s';

                $chr = $match[1];                           // quotation character
                $txt = $this->l10n->lookup($match[2]);      // get translated text
                
                $txt = $chr . addcslashes($txt, ($chr == '"' ? '"' : "'")) . $chr;
                
                array_shift($args);
                
                if (count($args) > 0) {
                    $txt = preg_replace_callback($pattern, function($m) use ($args, $chr) {
                        $cmd = (isset($m[2]) ? $m[2] : '');
                        $tmp = preg_split('/(?<!\\\),/', array_pop($m));
                        $par = array();

                        foreach ($tmp as $t) {
                            $par[] = (($t = trim($t)) && preg_match('/^_(\d+)$/', $t, $m)
                                        ? $args[($m[1] - 1)]
                                        : '\'' . $t . '\'');
                        }

                        $code = ($cmd != '' 
                                 ? $chr . ' . $this->' . $cmd . '(' . implode(',', $par) . ') . ' . $chr
                                 : $chr . ' . ' . array_shift($par) . ' . ' . $chr);

                        return $code;
                    }, $txt, -1, $cnt = 0);
                }
                
                $return = $txt;
            } else {
                $return = '$this->_(' . implode('', $args) . ')';
            }
            
            return $return;
        }
        
        /**
         * Compile tokens to PHP code.
         *
         * @octdoc  m:compiler/compile
         * @param   array       $tokens     Array of tokens to compile.
         * @param   array       $blocks     Block information required by analyzer / compiler.
         * @return  string                  Generated PHP code.
         */
        protected function compile(&$tokens, &$blocks)
        /**/
        {
            $stack = array();
            $code  = array();
            
            $last_tokens = array();
            
            $getNextToken = function(&$tokens) use (&$last_tokens) {
                if (($current = array_shift($tokens))) {
                    $last_tokens[] = $current['token'];
                }

                return $current;
            };
            $getLastToken = function($tokens, $idx) {
                if (($tmp = array_slice($tokens, $idx, 1))) {
                    $return = array_pop($tmp);
                } else {
                    $return = 0;
                }

                return $return;
            };

            while (($current = $getNextToken($tokens))) {
                extract($current);
            
                switch ($token) {
                case self::T_IF_OPEN:
                case self::T_BLOCK_OPEN:
                    // replace/rewrite block call
                    $value = strtolower($value);
                    
                    list($_start, $_end) = compiler\rewrite::$value(array_reverse($code));

                    $code = array($_start);
                    $blocks['compiler'][] = $_end;
                
                    if (($err = compiler\rewrite::getError()) != '') {
                        $this->error(__FUNCTION__, __LINE__, $line, $token, $err);
                    }
                    break;
                case self::T_IF_ELSE:
                    $code[] = '} else {';
                    break;
                case self::T_BLOCK_CLOSE:
                    $code[] = array_pop($blocks['compiler']);
                    break;
                case self::T_BRACE_CLOSE:
                    array_push($stack, $code);
                    $code = array();
                    break;
                case self::T_GETTEXT:
                    // gettext handling
                    $code = array($this->gettext(array_reverse($code)));
                    break;
                case self::T_LET:
                case self::T_METHOD:
                    // replace/rewrite method call
                    $value = strtolower($value);
                    $code  = array(compiler\rewrite::$value(array_reverse($code)));
                    
                    if (($err = compiler\rewrite::getError()) != '') {
                        $this->error(__FUNCTION__, __LINE__, $line, $token, $err);
                    }
                    
                    if (($tmp = array_pop($stack))) $code = array_merge($tmp, $code);
                    break;
                case self::T_MACRO:
                    // resolve macro
                    $value = strtolower(substr($value, 1));
                    $file  = substr($code[0], 1, -1);
                    $code  = array(compiler\macro::execMacro($value, array($file), array('compiler' => $this)));

                    if (($err = compiler\macro::getError()) != '') {
                        $this->error(__FUNCTION__, __LINE__, $line, $token, $err);
                    }
                    
                    $code[] = implode(', ', array_pop($stack));
                    break;
                case self::T_CONSTANT:
                    $value = strtolower(substr($value, 1));
                    $tmp   = comiler\constant::getConstant($value);
                
                    if (($err = compiler\constant::getError()) != '') {
                        $this->error(__FUNCTION__, __LINE__, $line, $token, $err);
                    }
                
                    $code[] = (is_string($tmp) ? '"' . $tmp . '"' : (int)$tmp);
                    break;
                case self::T_VARIABLE:
                    $code[] = sprintf(
                        '$this->data["%s"]', 
                        implode('"]["', explode(':', strtolower(substr($value, 1))))
                    );
                    break;
                case self::T_STRING:
                case self::T_NUMBER:
                    $code[] = $value;
                    break;
                case self::T_START:
                    $last_token = $getLastToken($last_tokens, -2);
                    
                    if ($last_token == self::T_LET) {
                        $code = array('<?php ' . implode('', $code) . '; ?>');
                    } elseif (in_array($last_token, array(self::T_CONSTANT, self::T_MACRO))) {
                        $code = array(implode('', $code));
                    } elseif (!in_array($last_token, array(self::T_BLOCK_OPEN, self::T_BLOCK_CLOSE, self::T_IF_OPEN, self::T_IF_ELSE))) {
                        $code = array('<?php $this->write(' . implode('', $code) . '); ?>');
                    } else {
                        $code = array('<?php ' . implode('', $code) . ' ?>');
                    }
                    break;
                case self::T_PSEPARATOR:
                case self::T_BRACE_OPEN:
                case self::T_END:
                    // nothing to do for these tokens
                    break;
                default:
                    $this->error(__FUNCTION__, __LINE__, $line, $token, 'unknown token');
                    break;
                }
            }
            
            return $code;
        }
        
        /**
         * Execute compiler toolchain for a template snippet.
         *
         * @octdoc  m:compiler/toolchain
         * @param   string      $snippet        Template snippet to process.
         * @param   int         $line           Line in template processed.
         * @param   array       $blocks         Block information required by analyzer / compiler.
         * @return  string                      Processed / compiled snippet.
         */
        protected function toolchain($snippet, $line, array &$blocks)
        /**/
        {
            $tokens = $this->tokenize($snippet, $line);
            $code   = '';

            if (count($tokens) > 0) {
                if ($this->analyze($tokens, $blocks) !== false) {
                    $tokens = array_reverse($tokens);
                    $code   = implode('', $this->compile($tokens, $blocks));
                }
            }
            
            return $code;
        }
        
        /**
         * Parse template and extract all template functionality to compile.
         *
         * @octdoc  m:compiler/parse
         * @param   string      $filename       Name of file to process.
         * @return  string                      Processed / compiled template
         */
        protected function parse($filename)
        /**/
        {
            $blocks = array('analyzer' => array(), 'compiler' => array());

            $tpl = file_get_contents($filename);

            // rewrite php open-/close-tags
            $pattern = '/(\{\{(.*?)\}\}|<\?php|\?>)/s';
            $offset  = 0;

            while (preg_match($pattern, $tpl, $m, PREG_OFFSET_CAPTURE, $offset)) {
                if ($m[1][0] == '<?php' || $m[1][0] == '?>') {
                    // de-activate php code by replacing tags with template snippets
                    $rpl = '{{string("' . $m[1][0] . '")}}';
                    $tpl = substr_replace($tpl, $rpl, $m[1][1], strlen($m[1][0]));
                    $len = strlen($rpl);
                } else {
                    $len = strlen($m[1][0]);
                }
                
                $offset = $m[1][1] + $len;
            }
            
            // process template snippets
            $pattern = '/(\{\{(.*?)\}\})/s';

            while (preg_match($pattern, $tpl, $m, PREG_OFFSET_CAPTURE)) {
                $crc  = crc32($tpl);
                $line = substr_count(substr($tpl, 0, $m[2][1]), "\n") + 1;

                // not sure why 'nl' is required, but \n and \r are removed,
                // when template snippet is at the end of a line -- so we
                // have to add the newline again.
                $nl = substr($tpl, $m[1][1] + strlen($m[1][0]), 1);
                $nl = ($nl == "\n" || $nl == "\r" ? $nl : '');

                $tpl = substr_replace($tpl, $this->toolchain(trim($m[2][0]), $line, $blocks) . $nl, $m[1][1], strlen($m[1][0]));
                
                if ($crc == crc32($tpl)) {
                    $this->error(__FUNCTION__, __LINE__, $line, 0, 'endless loop detected');
                }
            }

            if (count($blocks['analyzer']) > 0) {
                $this->error(__FUNCTION__, __LINE__, $line, 0, sprintf('missing %s for %s',
                    $this->getTokenName(self::T_BLOCK_CLOSE),
                    implode(', ', array_map(function($v) {
                        return $v['value'];
                    }, array_reverse($blocks['analyzer'])))
                ));
            }
            
            return $tpl;
        }
        
        /**
         * Process a template.
         *
         * @octdoc  m:compiler/process
         * @param   string      $filename       Name of template file to process.
         * @return  string                      Compiled template.
         */
        public function process($filename)
        /**/
        {
            $this->filename = $filename;

            return $this->parse($filename);
        }
    }
}
