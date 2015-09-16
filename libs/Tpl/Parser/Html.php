<?php

/*
 * This file is part of the 'octris/core' package.
 *
 * (c) Harald Lapp <harald@octris.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Octris\Core\Tpl\Parser;

/**
 * HTML Parser for auto-escaping functionality.
 *
 * @copyright   copyright (c) 2012-2014 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
class Html extends \Octris\Core\Tpl\Parser
{
    /**
     * Parser states.
     */
    const T_DATA            = 1;
    const T_COMMAND         = 2;

    const T_TAG_START       = 10;
    const T_TAG_END_OPEN    = 11;
    const T_TAG_END_CLOSE   = 12;
    const T_TAG_NAME        = 13;
    const T_TAG_CLOSE       = 14;

    const T_ATTR_START      = 20;
    const T_ATTR_END        = 21;
    const T_ATTR_COMMAND    = 22;

    const T_COMMENT_OPEN    = 30;
    const T_COMMENT_CLOSE   = 31;
    const T_COMMENT_COMMAND = 32;

    const T_CDATA_OPEN      = 40;
    const T_CDATA_CLOSE     = 41;
    const T_CDATA_COMMAND   = 42;

    /**
     * Parser patterns
     *
     * @type    array
     */
    protected static $patterns = array(
        self::T_COMMENT_OPEN    => '/<!--/',
        self::T_COMMENT_CLOSE   => '/-->/',

        self::T_TAG_START       => '/</',
        self::T_TAG_END_OPEN    => '/\s*>/',
        self::T_TAG_END_CLOSE   => '/\s*\/>/',
        self::T_TAG_NAME        => '/(_c_[a-f0-9]+_|(?i:[a-z]+))/',
        self::T_TAG_CLOSE       => '/\/(_c_[a-f0-9]+_|(?i:[a-z]+))>/',

        self::T_ATTR_START      => '/(?<=\s)(_c_[a-f0-9]+_|(?i:[a-z:_][a-z:_.-]*))=\"/',
        self::T_ATTR_END        => '/(?!\\\\)\"/',
        self::T_ATTR_COMMAND    => '/(_c_[a-f0-9]+_)/',

        self::T_CDATA_OPEN      => '/<!\[CDATA\[/i',
        self::T_CDATA_CLOSE     => '/\]\]>/',
        self::T_CDATA_COMMAND   => '/(_c_[a-f0-9]+_)/',

        self::T_COMMAND         => '/(_c_[a-f0-9]+_)/',
        self::T_COMMENT_COMMAND => '/(_c_[a-f0-9]+_)/'
    );

    /**
     * Parser rules.
     *
     * @type    array
     */
    protected static $rules = array(
        self::T_DATA            => array(
            self::T_COMMENT_OPEN,
            self::T_CDATA_OPEN,
            self::T_TAG_START,
            self::T_COMMAND
        ),

        self::T_TAG_START       => array(
            self::T_TAG_NAME,
            self::T_TAG_CLOSE
        ),

        self::T_TAG_NAME        => array(
            self::T_TAG_END_OPEN,
            self::T_TAG_END_CLOSE,
            self::T_ATTR_START,
            self::T_COMMAND
        ),

        self::T_ATTR_START      => array(
            self::T_ATTR_COMMAND,
            self::T_ATTR_END
        ),

        self::T_ATTR_COMMAND    => array(
            self::T_ATTR_COMMAND,
            self::T_ATTR_END
        ),

        self::T_ATTR_END        => array(
            self::T_TAG_END_OPEN,
            self::T_TAG_END_CLOSE,
            self::T_ATTR_START,
            self::T_COMMAND
        ),

        self::T_COMMENT_OPEN    => array(
            self::T_COMMENT_COMMAND,
            self::T_COMMENT_CLOSE
        ),

        self::T_COMMENT_COMMAND => array(
            self::T_COMMENT_COMMAND,
            self::T_COMMENT_CLOSE
        ),

        self::T_CDATA_OPEN      => array(
            self::T_CDATA_COMMAND,
            self::T_CDATA_CLOSE
        ),

        self::T_CDATA_COMMAND   => array(
            self::T_CDATA_COMMAND,
            self::T_CDATA_CLOSE
        )
    );

    /**
     * Attributes and their relevant context information.
     *
     * @type    array
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

    /**
     * Current state of parser in document.
     *
     * @type    int
     */
    protected $state = self::T_DATA;

    /**
     * Stack for escaping modes.
     *
     * @type    array
     */
    protected $escape = array(\Octris\Core\Tpl::ESC_HTML);

    /**
     * Array for storing normalized template commands.
     *
     * @type    array
     */
    protected $commands = array();

    /**
     * Constructor.
     *
     * @param   string                  $filename                   Filename of HTML document to parse. This will only be used for better error reporting and can also be left empty.
     * @param   int                     $flags                      Optional option flags to set.
     */
    public function __construct($filename, $flags = 0)
    {
        parent::__construct($filename, $flags);
    }

    /** Implementation of methods required for Iterator interface **/

    /**
     * Set offset to 0 to parse template again.
     */
    public function rewind()
    {
        $this->offset     = 0;
        $this->new_offset = 0;

        $this->next();
    }

    /**
     * This methods parses the template until a template command is reached. The template command is than evailable as iterator item.
     */
    public function next()
    {
        $current = null;

        while (($state = $this->getNextState())) {
            // parsing in progress
            switch ($state['state']) {
                case self::T_COMMENT_COMMAND:
                    if ($this->ignore_comments) {
                        continue(2);
                    }
                    // FALL THRU
                case self::T_CDATA_COMMAND:
                case self::T_ATTR_COMMAND:
                case self::T_COMMAND:
                    if (!isset($this->commands[$state['payload']])) {
                        $this->error(
                            __FUNCTION__,
                            __LINE__,
                            $state['line'],
                            $state['state'],
                            sprintf(
                                'command with id "%s" is unknown',
                                $state['payload']
                            )
                        );
                    }

                    $current = array(
                        'snippet' => $this->commands[$state['payload']],
                        'escape'  => end($this->escape),
                        'line'    => $state['line'],
                        'offset'  => $state['offset'],
                        'length'  => $state['length']
                    );
                    break(2);
                case self::T_COMMENT_OPEN:
                    array_push($this->escape, \Octris\Core\Tpl::ESC_HTMLCOMMENT);
                    break;
                case self::T_TAG_START:
                    break;
                case self::T_TAG_NAME:
                    if (substr($state['payload'], 0, 3) == '_c_') {
                        $this->error(
                            __FUNCTION__,
                            __LINE__,
                            $state['line'],
                            $state['state'],
                            'template command not allowed as tag-name'
                        );
                    } else {
                        switch (strtolower($state['payload'])) {
                            case 'script':
                                array_push($this->escape, \Octris\Core\Tpl::ESC_JS);
                                break;
                            case 'style':
                                array_push($this->escape, \Octris\Core\Tpl::ESC_CSS);
                                break;
                            default:
                                array_push($this->escape, \Octris\Core\Tpl::ESC_HTML);
                                break;
                        }
                    }
                    break;
                case self::T_CDATA_CLOSE:
                case self::T_COMMENT_CLOSE:
                case self::T_TAG_END_CLOSE:
                    array_pop($this->escape);
                    // FALL THRU
                case self::T_TAG_END_OPEN:
                    $this->state = self::T_DATA;
                    continue(2);
                case self::T_TAG_CLOSE:
                    if (count($this->escape) == 1) {
                        if ($this->escape[0] != \Octris\Core\Tpl::ESC_HTML) {
                            $this->escape[0] = \Octris\Core\Tpl::ESC_HTML;
                        }
                    } else {
                        array_pop($this->escape);
                    }

                    $this->state = self::T_DATA;
                    continue(2);
                case self::T_ATTR_START:
                    if (substr($state['payload'], 0, 3) == '_c_') {
                        $this->error(
                            __FUNCTION__,
                            __LINE__,
                            $state['line'],
                            $state['state'],
                            'template command not allowed as attribute-name'
                        );
                    } else {
                        $name = strtolower($state['payload']);

                        if (in_array($name, self::$attributes['js'])) {
                            array_push($this->escape, \Octris\Core\Tpl::ESC_JS);
                        } elseif (in_array($name, self::$attributes['uri'])) {
                            array_push($this->escape, \Octris\Core\Tpl::ESC_URI);
                        } else {
                            array_push($this->escape, \Octris\Core\Tpl::ESC_ATTR);
                        }
                    }
                    break;
                case self::T_ATTR_END:
                    array_pop($this->escape);
                    break;
            }

            $this->state = $state['state'];
        }

        $this->current = $current;
        $this->valid   = (is_array($current));
    }

    /** Helper methods for parser **/

    protected function getNextState()
    {
        if (!isset(self::$rules[$this->state])) {
            $this->error(
                __FUNCTION__,
                __LINE__,
                $this->getLineNumber($this->offset),
                $this->state,
                'no rule for current token'
            );
        }

        $this->offset = $this->next_offset;

        $match = false;

        foreach (self::$rules[$this->state] as $new_state) {
            $pattern = self::$patterns[$new_state];

            if (preg_match($pattern, $this->tpl, $m, PREG_OFFSET_CAPTURE, $this->offset)) {
                if ($match === false || $m[0][1] < $match['offset']) {
                    $match = array(
                        'offset'    => $m[0][1],
                        'state'     => $new_state,
                        'token'     => $this->getTokenName($new_state),
                        'payload'   => (isset($m[1]) ? $m[1][0] : ''),
                        'escape'    => null,
                        'length'    => strlen($m[0][0]),
                        'line'      => $this->getLineNumber($m[0][1])
                    );

                    if ($this->debug) {
                        $match['match'] = $m[0][0];
                    }
                }
            }
        }

        if ($match !== false) {
            $this->next_offset = $match['offset'] + $match['length'];
        }

        return $match;
    }

    /**
     * Allows to set offset to parse from outside the iterator instance.
     *
     * @param   int         $offset     Offset to set.
     */
    public function setOffset($offset)
    {
        $this->next_offset = $offset;
    }

    /**
     * Search and replace all template commands and insert them in a dictionary for simpler HTML parsing.
     *
     * @param   string                      $tpl                            HTML document to prepare.
     * @return  string                                                      Prepared HTML document.
     */
    protected function prepare($tpl)
    {
        $tpl = parent::prepare($tpl);
        $tpl = preg_replace_callback('/' . self::$snippet_pattern . '/', function ($m) {
            $id = '_c_' . uniqid() . '_';
            $this->commands[$id] = $m[1];

            return $id;
        }, $tpl);

        return $tpl;
    }
}
