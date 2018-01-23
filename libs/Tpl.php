<?php

/*
 * This file is part of the 'octris/tpl' package.
 *
 * (c) Harald Lapp <harald@octris.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Octris;

use \Octris\Tpl\Compiler as compiler;

/**
 * Main class of template engine.
 *
 * @copyright   copyright (c) 2010-2018 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
class Tpl
{
    /**
     * Escape types.
     */
    const ESC_NONE        = '';
    const ESC_AUTO        = 'auto';
    const ESC_ATTR        = 'attr';
    const ESC_CSS         = 'css';
    const ESC_HTML        = 'html';
    const ESC_HTMLCOMMENT = 'htmlcomment';
    const ESC_JS          = 'js';
    const ESC_TAG         = 'tag';
    const ESC_URI         = 'uri';

    /**
     * Global template data.
     *
     * @type    array
     */
    protected $data = [];

    /**
     * Configured caching backend.
     *
     * @type    \Octris\Tpl\CacheInterface
     */
    protected $tpl_cache;

    /**
     * Stores pathes to look into when searching for template to load.
     *
     * @type    array
     */
    protected $searchpath = array();

    /**
     * Postprocessors.
     *
     * @type    array
     */
    protected $postprocessors = array();

    /**
     * Extension library.
     *
     * @type    \Octris\Tpl\Library
     */
    protected $library;

    /**
     * Character encoding of template.
     *
     * @type    string
     */
    protected $encoding;

    /**
     * Constructor.
     *
     * @param   string                  $encoding   Character encoding.
     */
    public function __construct($encoding = 'utf-8')
    {
        $this->tpl_cache = new Tpl\Cache\Transient();
        $this->library = new Tpl\Library(
            new class() extends Tpl\Extension\AbstractBundle {
                public function getExtensions() {
                    return [
                        new Tpl\Extension\Internal\BlockFor('for'),
                        new Tpl\Extension\Internal\BlockForeach('foreach'),
                        new Tpl\Extension\Internal\BlockIf('if'),
                        
                        new Tpl\Extension\Internal\FunEscape('escape'),
                        new Tpl\Extension\Internal\FunLet('let'),
                    ]
                }
            }
        );        
    }

    /**
     * Set caching backend.
     *
     * @param   \Octris\Tpl\Cache      $cache      Instance of caching backend.
     */
    public function setCache(\Octris\Tpl\CacheInterface $cache)
    {
        $this->tpl_cache = $cache;
    }

    /**
     * Set values for multiple template variables.
     *
     * @param   iterable        $array      Key/value array with values.
     */
    public function setValues(iterable $array)
    {
        foreach ($array as $k => $v) {
            $this->setValue($k, $v);
        }
    }

    /**
     * Set value for one template variable. Note, that resources are not allowed as values.
     *
     * @param   string      $name       Name of template variable to set value of.
     * @param   mixed       $value      Value to set for template variable.
     */
    public function setValue($name, $value)
    {
        if (is_resource($value)) {
            throw new \InvalidArgumentException('Value of type "resource" is not allowed');
        } else {
            $this->data[$name] = $value;
        }
    }

    /**
     * Add a single extension.
     *
     * @param   \Octris\Tpl\Extension\AbstractExtension $extension          A single extension to add.
     */
    public function addExtension(\Octris\Tpl\Extension\AbstractExtension $extension)
    {
        $this->library->addExtension($extension);
    }

    /**
     * Add an extension bundle.
     *
     * @param   \Octris\Tpl\Extension\AbstractBundle    $bundle             Extension bundle to add.
     */
    public function addExtensionBundle(\Octris\Tpl\Extension\AbstractBundle $bundle)
    {
        $this->library->addExtensionBundle($bundle);
    }

    /**
     * Add a post-processor.
     *
     * @param   \Octris\Tpl\PostprocessInterface       $processor          Instance of class for postprocessing.
     */
    public function addPostprocessor($processor)
    {
        $this->postprocessors[] = $processor;
    }

    /**
     * Register pathname for looking up templates in.
     *
     * @param   mixed       $pathname       Name of path to register.
     */
    public function addSearchPath($pathname)
    {
        if (is_array($pathname)) {
            foreach ($pathname as $path) {
                $this->addSearchPath($path);
            }
        } else {
            if (!in_array($pathname, $this->searchpath)) {
                $this->searchpath[] = $pathname;
            }
        }
    }

    /**
     * Returns iterator for iterating over all templates in all search pathes.
     *
     * @return  \ArrayIterator                                  Instance of ArrayIterator.
     */
    public function getTemplatesIterator()
    {
        foreach ($this->searchpath as $path) {
            $len = strlen($path);

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $filename => $cur) {
                $rel = substr($filename, $len);

                yield $rel => $cur;
            }
        }
    }

    /**
     * Executes template toolchain -- compiler and compressors.
     *
     * @param   string      $tplname    Filename of template to process.
     * @param   string      $escape     Escaping to use.
     * @param   bool        $force      Force compilation, do not fetch from cache.
     * @return  string                  Processed template.
     */
    protected function process($tplname, $escape, $force = false)
    {
        $uri = $this->tpl_cache->getURI($tplname);

        if ($force || ($tpl = $this->tpl_cache->getContents($uri)) === false) {
            $c = new Tpl\Compiler($this->library);
            $c->addSearchPath($this->searchpath);

            if (($filename = $c->findFile($tplname)) !== false) {
                $tpl = $c->process($filename, $escape);

                foreach ($this->postprocessors as $processor) {
                    $tpl = $processor->postProcess($tpl);
                }

                $this->tpl_cache->putContents($uri, $tpl);
            } else {
                die(sprintf(
                    'unable to locate file "%s" in "%s"',
                    $tplname,
                    implode(':', $this->searchpath)
                ));
            }
        }

        return $tpl;
    }

    /**
     * Lint template.
     *
     * @param   string      $filename       Name of template file to compile.
     * @param   string      $escape         Optional escaping to use.
     */
    public function lint($filename, $escape = self::ESC_HTML)
    {
        $inp = ltrim(preg_replace('/\/\/+/', '/', preg_replace('/\.\.?\//', '/', $filename)), '/');

        $c = new Tpl\Lint($this->library);
        $c->addSearchPath($this->searchpath);

        if (($filename = $c->findFile($inp)) !== false) {
            $tpl = $c->process($filename, $escape);
        } else {
            die(sprintf(
                'unable to locate file "%s" in "%s"',
                $inp,
                implode(':', $this->searchpath)
            ));
        }
    }

    /**
     * Compile template.
     *
     * @param   string      $filename       Name of template file to compile.
     * @param   string      $escape         Optional escaping to use.
     */
    public function compile($filename, $escape = self::ESC_HTML)
    {
        $this->process($filename, $escape, true);
    }

    /**
     * Check if a template exists.
     *
     * @param   string      $filename       Filename of template to check.
     * @return  bool                        Returns true if template exists.
     */
    public function templateExists($filename)
    {
        $inp = ltrim(preg_replace('/\/\/+/', '/', preg_replace('/\.\.?\//', '/', $filename)), '/');

        $c = new Tpl\Compiler($this->library);
        $c->addSearchPath($this->searchpath);

        return (($filename = $c->findFile($inp)) !== false);
    }

    /**
     * Return an instance of a template sandbox to render.
     *
     * @param   string      $filename       Filename of template to render.
     * @param   string      $escape         Optional escaping to use.
     * @return  \Octris\Tpl\Sandbox
     */
    public function getSandbox($filename, $escape = self::ESC_HTML)
    {
        $tpl = $this->process($filename, $escape);

        return new Tpl\Sandbox($this->library, $this->encoding, $filename, $tpl, $this->data);
    }
}
