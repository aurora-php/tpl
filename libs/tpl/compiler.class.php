<?php

namespace org\octris\core\tpl {
    use \org\octris\core\tpl\compiler as compiler;
    
    /****c* tpl/compiler
     * NAME
     *      compiler
     * FUNCTION
     *      template compiler
     * COPYRIGHT
     *      copyright (c) 2010 by Harald Lapp
     * AUTHOR
     *      Harald Lapp <harald@octris.org>
     ****
     */

    class compiler {
        /****d* compiler/T_...
         * SYNOPSIS
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
        /*
         * FUNCTION
         *      tokens
         ****
         */

        /****v* compiler/$tokens
         * SYNOPSIS
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
        /*
         * FUNCTION
         *      token patterns for tokenizer
         ****
         */

        /****v* compiler/$rules
         * SYNOPSIS
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
        /*
         * FUNCTION
         *      analyzer rules
         ****
         */
        
        /****v* compiler/$tokennames
         * SYNOPSIS
         */
        private static $tokennames = NULL;
        /*
         * FUNCTION
         *      names of tokens to be filled by constructor
         ****
         */
        
        /****v* compiler/$filename
         * SYNOPSIS
         */
        protected $filename = '';
        /*
         * FUNCTION
         *      name of file currently compiled
         ****
         */
        
        /****v* compiler/$searchpath
         * SYNOPSIS
         */
        protected $searchpath = array();
        /*
         * FUNCTION
         *      path to look in for loading templates
         ****
         */

        /****v* compiler/$gettext_callback
         * SYNOPSIS
         */
        protected $gettext_callback;
        /*
         * FUNCTION
         *      callback for looking up gettext message. may be overwritten with
         *      the method ~setGettextCallback~.
         ****
         */
        
        /****m* compiler/__construct
         * SYNOPSIS
         */
        public function __construct()
        /*
         * FUNCTION
         *      constructor
         ****
         */
        {
            if (is_null(self::$tokennames)) {
                $class = new \ReflectionClass($this);
                self::$tokennames = array_flip($class->getConstants());
            }
            
            $this->gettext_callback = function($msg) {
                return $msg;
            };
        }
        
        /****m* compiler/setGettextCallback
         * SYNOPSIS
         */
        public function setGettextCallback($cb)
        /*
         * FUNCTION
         *      set callback for resolving gettext messages
         * INPUTS
         *      * $cb (callback) -- callback to call for messages to translate
         ****
         */
        {
            if (!is_callable($cb)) {
                throw new \Exception('no callback method specified');
            }
            
            $this->gettext_callback = $cb;
        }
        
        /****m* compiler/addSearchPath
         * SYNOPSIS
         */
        public function addSearchPath($path)
        /*
         * FUNCTION
         *      add path to lookup templates in
         * INPUTS
         *      * $path (mixed) -- path to add, string or array of strings
         ****
         */
        {
            if (!is_array($path)) $path = array($path);
            
            $this->searchpath = array_unique(array_merge($this->searchpath, $path));
        }
        
        /****m* compiler/findFile
         * SYNOPSIS
         */
        public function findFile($filename)
        /*
         * FUNCTION
         *      lookup a file in the searchpath 
         * INPUTS
         *      * $filename (string) -- name of file to lookup
         * OUTPUTS
         *      (mixed) -- returns full path of file or false, if file could not be located
         ****
         */
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

        /****m* compiler/getTokenName
         * SYNOPSIS
         */
        protected function getTokenName($token)
        /*
         * FUNCTION
         *      return name of token
         * INPUTS
         *      * $token (int) -- ID of token
         * OUTPUTS
         *      (string) -- name of token
         ****
         */
        {
            return (isset(self::$tokennames[$token])
                    ? self::$tokennames[$token]
                    : 'T_UNKNOWN');
        }
        
        /****m* compiler/getTokenName
         * SYNOPSIS
         */
        protected function getTokenNames(array $tokens)
        /*
         * FUNCTION
         *      return names for tokens
         * INPUTS
         *      * $tokens (array) -- array of tokens
         * OUTPUTS
         *      (string) -- name of token
         ****
         */
        {
            $return = array();
            
            foreach ($tokens as $token) $return[] = $this->getTokenName($token);
            
            return $return;
        }
        
        /****m* compiler/error
         * SYNOPSIS
         */
        protected function error($type, $cline, $line, $token, $payload = NULL)
        /*
         * FUNCTION
         *      trigger an error
         * INPUTS
         *      * $type (string) -- type of error to trigger
         *      * $cline (int) -- error occurred in this line of compiler class
         *      * $line (int) -- error occurred in this line of the template
         *      * $token (int) -- ID of token, that triggered the error
         *      * $payload (mixed) -- (optional) additional information -- either an array of expected token IDs, or an additional 
         *        message
         ****
         */
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
        
        /****m* compiler/tokenize
         * SYNOPSIS
         */
        protected function tokenize($in, $line)
        /*
         * FUNCTION
         *      tokenizer converts template snippet to tokens
         * INPUTS
         *      * $in (string) -- template snippet to tokenize
         *      * $line (int) -- line number of snippet in template 
         * OUTPUTS
         *      (array) -- tokens
         ****
         */
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

        /****m* compiler/analyze
         * SYNOPSIS
         */
        protected function analyze(array $tokens, array &$blocks)
        /*
         * FUNCTION
         *      token analyzer -- applies rulesets to tokens and check if the
         *      rules are fulfilled
         * INPUTS
         *      * $tokens (array) -- tokens to analyz
         *      * $blocks (array) -- block information required by analyzer / compiler
         * OUTPUTS
         *      (array) -- errors
         ****
         */
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
                    if ((($cnt = count($blocks['analyzer'])) > 0 && $blocks['analyzer'][$cnt - 1]['token'] == self::T_IF_OPEN) || $cnt == 0) {
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

        /****m* compiler/gettext
         * SYNOPSIS
         */
        protected function gettext($args)
        /*
         * FUNCTION
         *      gettext compiler
         * INPUTS
         *      * $args (array) -- arguments for gettext
         * OUTPUTS
         *      (string) -- compiled code for gettext
         ****
         */
        {
            if (preg_match('/^(["\'])(.*?)\1$/', $args[0], $match)) {
                // string
                $cb  = $this->gettext_callback;
                
                $chr = $match[1];       // quotation character
                $txt = $cb($match[2]);  // text to translate
                
                $txt = $chr . addcslashes($txt, ($chr == '"' ? '"' : "'")) . $chr;
                
                array_shift($args);
                
                if (count($args) > 0) {
                    $replace = array();
                    $pattern = '/\[(?:(_\d+)|(?:([^,]+))(?:,(.*?))?(?<!\\\))\]/sie';
            
                    if (preg_match_all($pattern, $txt, $match, PREG_SET_ORDER)) {
                        foreach ($match as $m) {
                            $str = $m[0];
                            $cmd = '';
            
                            if (isset($m[2])) {
                                $cmd = $m[2];
                                unset($m[2]);
                            }
                            $par = array_pop($m);
            
                            $params = array();
                            $arr    = preg_split('/(?<!\\\),/', $par);
            
                            foreach ($arr as $a) {
                                $a = trim($a);
            
                                if (preg_match('/^_(\d+)$/', $a, $tmp)) {
                                    $params[] = $args[($tmp[1] - 1)];
                                } else {
                                    $params[] = '\'' . $a . '\'';
                                }
                            }
            
                            if ($cmd && !method_exists($l10n, $cmd)) {
                                die('unknown method ' . $cmd);
                            } elseif ($cmd) {
                                $code = $chr . ' . $this->' . $cmd . '(' . join(',', $params) . ') . ' . $chr;
                            } else {
                                $code = $chr . ' . ' . array_shift($params) . ' . ' . $chr;
                            }
            
                            $txt = str_replace($str, $code, $txt);
                        }
                    }
                    
                }
                
                $return = $txt;
            } else {
                $return = '$this->_(' . implode('', $args) . ')';
            }
            
            return $return;
        }
        
        /****m* compiler/compile
         * SYNOPSIS
         */
        protected function compile(&$tokens, &$blocks)
        /*
         * FUNCTION
         *      compile tokens to php code
         * INPUTS
         *      * $tokens (array) -- array of tokens to compile
         *      * $blocks (array) -- block information required by analyzer / compiler
         * OUTPUTS
         *      (string) -- generated php code
         ****
         */
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
                    $code  = array(compiler\macro::execMacro($value, array($file), array('searchpath' => $this->searchpath)));

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
        
        /****m* compiler/toolchain
         * SYNOPSIS
         */
        protected function toolchain($snippet, $line, array &$blocks)
        /*
         * FUNCTION
         *      execute compiler toolchain for a template snippet
         * INPUTS
         *      * $snippet (string) -- template snippet to process
         *      * $line (int) -- line template to process
         *      * $blocks (array) -- block information required by analyzer / compiler
         * OUTPUTS
         *      (string) -- processed / compiled snippet
         ****
         */
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
        
        /****m* compiler/parse
         * SYNOPSIS
         */
        protected function parse($filename)
        /*
         * FUNCTION
         *      parse template and extract all template functionality to compile
         * INPUTS
         *      * $filename (string) -- name of file to process
         * OUTPUTS
         *      (string) -- processed template
         ****
         */
        {
            $blocks = array('analyzer' => array(), 'compiler' => array());

            $tpl = file_get_contents($filename);

            $pattern = '/(\{\{(.*?)\}\})/s';

            while (preg_match($pattern, $tpl, $m, PREG_OFFSET_CAPTURE)) {
                $crc  = crc32($tpl);
                $line = substr_count(substr($tpl, 0, $m[2][1]), "\n") + 1;
                $tpl  = substr_replace($tpl, $this->toolchain(trim($m[2][0]), $line, $blocks), $m[1][1], strlen($m[1][0]));

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
        
        /****m* compiler/process
         * SYNOPSIS
         */
        public function process($filename)
        /*
         * FUNCTION
         *      start compiler
         * INPUTS
         *      * $filename (string) -- name of file to process
         * OUTPUTS
         *      (string) -- compiled template
         ****
         */
        {
            $this->filename = $filename;

            return $this->parse($filename);
        }
    }
}
