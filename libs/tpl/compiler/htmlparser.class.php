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
     * Rewrite template code. Rewrite inline function calls and rewrite function calls according to
     * if they are allowed php function calls or calls to functions that have to be registered to 
     * sandbox on template rendering.
     *
     * @octdoc      c:compiler/rewrite
     * @copyright   copyright (c) 2010-2011 by Harald Lapp
     * @author      Harald Lapp <harald@octris.org>
     */
    class htmlparser
    /**/
    {
        /**
         * HTML-Parser tokens.
         *
         * @octdoc  d:htmlparser/T_HTML_...
         */
        const T_DATA        = 1;
        const T_TAG         = 2;
        const T_ATTRIBUTE   = 3;
        
        const T_URL         = 10;

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
                '/<([a-z]+)/i'                  => self::T_TAG
            ),
            
            // tag state
            self::T_TAG => array(
                '/\{\{(.*?)\}\}/'               => self::T_COMMAND,
                "/([a-z]+(?:-[a-z]+|))=[\"']/i" => self::T_ATTRIBUTE,
                '/\/?>/'                        => self::T_DATA
            ),
            
            // attribute state
            self::T_ATTRIBUTE => array(
                '/\{\{(.*?)\}\}/'               => self::T_COMMAND,
                "/[\"']/"                       => self::T_TAG
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
         * Attributes and the context that they should resolve to.
         *
         * @octdoc  p:htmlparser/$attributes
         * @var     array
         */
        protected $attributes = array(
        	
        );
        /**/

        /**
         * HTML-Parser implementation.
         *
         * @octdoc  m:htmlparser/parse
         * @param 	string 			$tpl 			Template to parse.
         * @param 	callback 		$cb 			Callback to execute for parsed content.
         * @parsm
         */
        public function parse($tpl)
        /**/
        {
            $state  = self::T_DATA;
            $offset = 0;
            $len    = strlen($tpl);
            
            $getState = function($state) use ($tpl, &$offset) {
                $match = false;
                
                foreach (self::$patterns[$state] as $pattern => $new_state) {
                    if (preg_match($pattern, $tpl, $m, PREG_OFFSET_CAPTURE, $offset)) {
                        if ($match === false || $m[0][1] < $match['offset']) {
                            $match = array(
                                'offset'  => $m[0][1],
                                'state'   => $new_state,
                                'payload' => (isset($m[1]) ? $m[1][0] : null),
                                'len'     => strlen($m[0][0])
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
                    $tpl = substr_replace(
                        $tpl,
                        $cb(trim($match['payload']), $line, $blocks, $state),
                        $match['offset'], 
                        $match['len']
                    );
                } else {
                    $state = $match['state'];
                }

                $offset = $match['offset'] + $match['len'];
            }

            return $tpl;
            
        }
    }
}
