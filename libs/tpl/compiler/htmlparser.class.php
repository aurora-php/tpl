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
    /**
     * HTML Template parser for auto-escaping. Note, that the current parser is very
     * simple and has probably security issues. Auto-escaping is therefore to be
     * considered as experimental.
     * 
     * The following pages hold interesting information for escaping in HTML files:
     * 
     * * http://www.w3.org/TR/html4/index/attributes.html
     * * http://www.w3.org/TR/html4/interact/scripts.html
     * * http://tools.ietf.org/html/draft-hoehrmann-javascript-scheme-03
     * 
     * @octdoc      c:compiler/htmlparser
     * @copyright   copyright (c) 2012 by Harald Lapp
     * @author      Harald Lapp <harald@octris.org>
     */
    class htmlparser
    /**/
    {
        /**
         * HTML-Parser tokens.
         *
         * @octdoc  d:htmlparser/T_...
         */
        const T_DATA        = 1;

        const T_TAG         = 2;
        const T_TAG_INNER   = 3;
        
        const T_ATTRIBUTE   = 4;
        
        const T_URI         = 10;

        const T_JAVASCRIPT	= 30;
        const T_CSS         = 40;
        
        const T_COMMAND     = 50;
        /**/

        /**
         * Patterns for HTML parser.
         *
         * @octdoc  p:htmlparser/$patterns
         * @var     array
         */
        protected static $patterns = array(
            // data state
            self::T_DATA => array(
                '/\{\{(.*?)\}\}/'               => self::T_COMMAND,
                '/<script.*?>/i'                => self::T_JAVASCRIPT,
                '/<style.*?>/i'                 => self::T_CSS,
                '/<([a-z]+)(!? \/|)>/i'         => self::T_DATA,
                '/<\/[a-z]+>/i'                 => self::T_DATA,
                '/<([a-z]+)/i'                  => self::T_TAG_INNER,
                '/<\/?/i'                       => self::T_TAG,
            ),
            
            // tag (name of a tag) state
            self::T_TAG => array(
                '/[a-z]+/i'                     => self::T_TAG_INNER,
                '/\{\{(.*?)\}\}/'               => self::T_COMMAND,
            ),

            // tag-inner (inside a tag) state
            self::T_TAG_INNER => array(
                '/\{\{(.*?)\}\}/'               => self::T_COMMAND,
                "/href=\"(javascript:)[^\/]/i"  => self::T_ATTRIBUTE,
                "/([a-z]+(?:-[a-z]+|))=\"/i"    => self::T_ATTRIBUTE,
                '/\/?>/'                        => self::T_DATA
            ),
            
            // attribute state
            self::T_ATTRIBUTE => array(
                '/\{\{(.*?)\}\}/'               => self::T_COMMAND,
                "/(?!\\\\)\"/"                  => self::T_TAG
            ),
            
            // javascript state
            self::T_JAVASCRIPT => array(
                '/\{\{(.*?)\}\}/'               => self::T_COMMAND,
                '/<\/script>/i'                 => self::T_DATA
            ),
            
            // css state
            self::T_CSS => array(
                '/\{\{(.*?)\}\}/'               => self::T_COMMAND,
                '/<\/style>/i'                  => self::T_DATA
            )
        );
        /**/

        /**
         * State to escape mapping.
         *
         * @octdoc  p:htmlparser/$mapping
         * @var     array
         */
        protected static $mapping = array(
            self::T_ATTRIBUTE  => \org\octris\core\tpl::T_ESC_ATTR,
            self::T_CSS        => \org\octris\core\tpl::T_ESC_CSS,
            self::T_DATA       => \org\octris\core\tpl::T_ESC_HTML,
            self::T_JAVASCRIPT => \org\octris\core\tpl::T_ESC_JS,
            self::T_TAG        => \org\octris\core\tpl::T_ESC_TAG,
            self::T_URI        => \org\octris\core\tpl::T_ESC_URI
        );
        /**/

        /**
         * Attributes and their relevant context information.
         *
         * @octdoc  p:htmlparser/$attributes
         * @var     array
         */
        protected static $attributes = array(
            'js' => array(
                'onload', 'onunload', 'onclick', 'ondblclick', 
                'onmousedown', 'onmouseup', 'onmouseover', 'onmousemove', 'onmouseout', 
                'onfocus', 'onblur', 'onkeypress', 'onkeydown', 'onkeyup',
                'onsubmit', 'onreset', 'onselect', 'onchange'
            ),
            'uri' => array(
                'action', 'background', 'cite', 'classid', 'codebase', 'data',
                'href', 'longdesc', 'profile', 'src', 'usemap'
            )
        );
        /**/

        /**
         * HTML-Parser implementation.
         *
         * @octdoc  m:htmlparser/parse
         * @param 	string 			$tpl 			Template to parse.
         * @param 	callback 		$cb_compile		Callback to execute snippet compiler.
         * @param   callback        $cb_error       Callback for handling parse errors.
         * @parsm
         */
        public static function parse($tpl, $cb_compile, $cb_error)
        /**/
        {
            $escape = $state  = self::T_DATA;
            $offset = 0;
            $len    = strlen($tpl);
            
            $getState = function($state) use ($tpl, &$offset) {
                $match = false;
                
                foreach (self::$patterns[$state] as $pattern => $new_state) {
                    if (preg_match($pattern, $tpl, $m, PREG_OFFSET_CAPTURE, $offset)) {
                        if ($match === false || $m[0][1] < $match['offset']) {
                            $match = array(
                                'offset'     => $m[0][1],
                                'state'      => $new_state,
                                'payload'    => (isset($m[1]) ? $m[1][0] : null),
                                'len'        => strlen($m[0][0])
                            );
                        }
                    }
                }
                
                return $match;
            };

            while ($offset < $len) {
                if (($match = $getState($state)) === false) {
                    break;
                }
                
                $line = substr_count(substr($tpl, 0, $match['offset']), "\n") + 1;

                if ($match['state'] == self::T_COMMAND) {
                    // template parser
                    if (!isset(self::$mapping[$escape])) {
                        // escaping unknown
                        $cb_error(__FUNCTION__, __LINE__, $line, sprintf('unknown escaping context "%d"', $escape));

                        $escape = \org\octris\core\tpl::T_ESC_NONE;
                    }

                    $tpl = substr_replace(
                        $tpl,
                        $cb_compiler(trim($match['payload']), $line, $blocks, $escape),
                        $match['offset'], 
                        $match['len']
                    );

                    if ($state == self::T_TAG) {
                        // template command was a tag-name
                        $state = self::T_TAG_INNER;
                    }
                } else {
                    $escape = $state = $match['state'];

                    if ($match['state'] == self::T_ATTRIBUTE)
                        $payload = strtolower($match['payload']);

                        if ($payload == 'javascript:') {
                            // (href) tag with javascript executable attribute value
                            $escape = self::T_JAVASCRIPT;
                        } elseif (in_array($payload, self::$attributes['js'])) {
                            // attribute that executes javascript
                            $escape = self::T_JAVASCRIPT;
                        } elseif (in_array($payload, self::$attributes['uri'])) {
                            // attribute that contains an URI
                            $escape = self::T_URI;
                        }
                    }
                }

                $offset = $match['offset'] + $match['len'];
            }

            return $tpl;
        }
    }
}
