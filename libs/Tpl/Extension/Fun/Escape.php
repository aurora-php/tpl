<?php

/*
 * This file is part of the 'octris/tpl' package.
 *
 * (c) Harald Lapp <harald@octris.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Octris\Tpl\Extension\Fun;

/**
 * Escape a value according to the specified escaping context.
 *
 * @copyright   copyright (c) 2018 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
final class Escape extends \Octris\Tpl\Extension\Fun {
    /**
     * Escaper instance.
     *
     * @type    \Zend\Escaper\Escaper
     */
    protected $escaper;

    /**
     * Constructor.
     *
     * @param   string              $name               Name to register extension with.
     * @param   array               $options            Optional options.
     */
    public function __construct($name, array $options = [])
    {
        $code_gen = function($value, $context) {
            return implode(', ', [ $value, $context ]);
        };

        parent::__construct($name, $code_gen, $options);

        $this->escaper = new \Zend\Escaper\Escaper('utf-8');
    }

    /**
     * Implementation for the called function.
     *
     * @param   string          $value          Value to escape.
     * @param   string          $context        Context for escaping.
     * @return  string                          Escaped value.
     */
    public function call($value, $context)
    {
        if (is_null($value)) {
            return '';
        }

        switch ($context) {
            case \Octris\Tpl::ESC_ATTR:
                $value = $this->escaper->escapeHtmlAttr($value);
                break;
            case \Octris\Tpl::ESC_CSS:
                $value = $this->escaper->escapeCss($value);
                break;
            case \Octris\Tpl::ESC_HTML:
                $value = $this->escaper->escapeHtml($value);
                break;
            case \Octris\Tpl::ESC_JS:
                $value = $this->escaper->escapeJs($value);
                break;
            case \Octris\Tpl::ESC_TAG:
                throw new \Exception('Escaping "ESC_TAG" is not implemented!');
                break;
            case \Octris\Tpl::ESC_URI:
                throw new \Exception('Escaping "ESC_URI" is not implemented!');
                break;
        }

        return $value;
    }
}
