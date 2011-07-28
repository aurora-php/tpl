<?php

/*
 * This file is part of the 'org.octris.core' package.
 *
 * (c) Harald Lapp <harald@octris.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace org\octris\core {
    use \org\octris\core\tpl\compiler as compiler;
    
    /**
     * Main class of template engine.
     *
     * @octdoc      c:core/tpl
     * @copyright   copyright (c) 2010-2011 by Harald Lapp
     * @author      Harald Lapp <harald@octris.org>
     */
    class tpl
    /**/
    {
        /**
         * Instance of sandbox for executing template in.
         *
         * @octdoc  v:tpl/$sandbox
         * @var     \org\octris\core\tpl\sandbox
         */
        protected $sandbox;
        /**/

        /**
         * Whether to fetch compiled template from cache.
         *
         * @octdoc  v:tpl/$use_cache
         * @var     bool
         */
        protected $use_cache = false;
        /**/

        /**
         * Stores pathes to look into when searching for template to load.
         *
         * @octdoc  v:tpl/$searchpath
         * @var     array
         */
        protected $searchpath = array();
        /**/

        /**
         * Instance of locale class.
         *
         * @octdoc  v:tpl/$l10n
         * @var     \org\octris\core\l10n
         */
        protected $l10n;
        /**/

        /**
         * Output path for various file types.
         *
         * @octdoc  v:tpl/$path
         * @var     array
         */
        protected $path = array(
            'tpl'   => '/tmp',      // output path for compiled templates
            'js'    => '/tmp',      // output path for compressed javascript
            'css'   => '/tmp'       // output path for compressed css
        );
        /**/

        /**
         * Constructor.
         *
         * @octdoc  m:tpl/__construct
         */
        public function __construct()
        /**/
        {
            $this->sandbox = new tpl\sandbox();
        }

        /**
         * Set l10n dependency.
         *
         * @octdoc  m:tpl/setL10n
         * @param   \org\octris\core\l10n       $l10n       Instance of l10n class.
         */
        public function setL10n(\org\octris\core\l10n $l10n)
        /**/
        {
            $this->sandbox->setL10n($l10n);
            $this->l10n = $l10n;
        }

        /**
         * Set values for multiple template variables.
         *
         * @octdoc  m:tpl/setValues
         * @param   array       $array      Key/value array with values.
         */
        public function setValues($array)
        /**/
        {
            $this->sandbox->setValues($array);
        }

        /**
         * Set value for one template variable.
         *
         * @octdoc  m:tpl/setValue
         * @param   string      $name       Name of template variable to set value of.
         * @param   mixed       $value      Value to set for template variable.
         */
        public function setValue($name, $value)
        /**/
        {
            $this->sandbox->setValue($name, $value);
        }

        /**
         * Register a custom template method.
         *
         * @octdoc  m:sandbox/registerMethod
         * @param   string      $name       Name of template method to register.
         * @param   mixed       $callback   Callback to map to template method.
         * @param   array       $args       For specifying min/max number of arguments required for callback method.
         */
        public function registerMethod($name, $callback, array $args)
        /**/
        {
            $this->sandbox->registerMethod($name, $callback, $args);
        }
        
        /**
         * Register pathname for looking up templates in.
         *
         * @octdoc  m:tpl/addSearchPath
         * @param   mixed       $pathname       Name of path to register.
         */
        public function addSearchPath($pathname)
        /**/
        {
            if (is_array($pathname)) {
                foreach ($pathname as $path) $this->addSearchPath($path);
            } else {
                if (!in_array($pathname, $this->searchpath)) {
                    $this->searchpath[] = $pathname;
                }
            }
        }

        /**
         * Set output path for compiled templates and compressed files.
         *
         * @octdoc  m:tpl/setOutputPath
         * @param   string      $ext        Extension of file to set path for.
         * @param   string      $pathname   Name of path to register.
         */
        public function setOutputPath($ext, $pathname)
        /**/
        {
            if (array_key_exists($ext, $this->path) && is_writable($pathname)) {
                $this->path[$ext] = rtrim($pathname, '/');
            }
        }

        /**
         * Executes template toolchain -- compiler and compressors.
         *
         * @octdoc  m:tpl/process
         * @param   string      $inp        Input filename.
         * @param   string      $out        Output filename.
         */
        protected function process($inp, $out)
        /**/
        {
            // tpl\compiler\constant::setConstants($this->constants);
            $sandbox = $this->sandbox;

            $c = new tpl\compiler();
            $c->setL10n($this->l10n);
            $c->addSearchPath($this->searchpath);

            if (($filename = $c->findFile($inp)) !== false) {
                $tpl = $c->process($filename);
            
                $c = new tpl\compress();
                $tpl = $c->process($tpl, $this->path['js'], $this->path['css']);
                $out = $this->path['tpl'] . '/' . str_replace('/', '-', $out);
                
                file_put_contents($out, $tpl);
            } else {
                die(sprintf(
                    'unable to locate file "%s" in "%s"', 
                    $inp,
                    implode(':', $this->searchpath)
                ));
            }
            
            return $out;
        }
        
        /**
         * Compile template and return compiled template as string.
         *
         * @octdoc  m:tpl/compile
         * @param   string      $filename       Name of template file to compile.
         * @return  string                      Compiled template.
         */
        public function compile($filename)
        /**/
        {
            $inp = ltrim(preg_replace('/\/\/+/', '/', preg_replace('/\.\.?\//', '/', $filename)), '/');
            $tpl = '';
            
            $sandbox = $this->sandbox;

            $c = new tpl\compiler();
            $c->setL10n($this->l10n);
            $c->addSearchPath($this->searchpath);

            if (($filename = $c->findFile($inp)) !== false) {
                $tpl = $c->process($filename);
            } else {
                die(sprintf(
                    'unable to locate file "%s" in "%s"', 
                    $inp,
                    implode(':', $this->searchpath)
                ));
            }
            
            return $tpl;
        }
        
        /**
         * Render a template and send output to stdout.
         *
         * @octdoc  m:tpl/render
         * @param   string      $filename       Filename of template to render.
         * @param   int         $context        Rendering context.
         */
        public function render($filename, $context = null)
        /**/
        {
            $context = (is_null($context) ? tpl\sandbox::T_CONTEXT_HTML : $context);
            
            $inp = ltrim(preg_replace('/\/\/+/', '/', preg_replace('/\.\.?\//', '/', $filename)), '/');
            $out = preg_replace('/[\s\.]/', '_', $inp) . '.php';

            if (!$this->use_cache) {
                // do not use cache -- first process template using
                // template compiler and javascript/css compressor
                $out = $this->process($inp, $out);
            }
            
            $this->sandbox->render($out);
        }
        
        /**
         * Render a template and return output as string.
         *
         * @octdoc  m:tpl/fetch
         * @param   string      $filename       Filename of template to render.
         * @param   int         $context        Rendering context.
         * @return  string                      Rendered template.
         */
        public function fetch($filename, $context = null)
        /**/
        {
            ob_start();

            $this->render($filename, $context);

            $return = ob_get_contents();
            ob_end_clean();

            return $return;
        }

        /**
         * Render a template and save output to a file.
         *
         * @octdoc  m:tpl/save
         * @param   string      $savename       Filename to save output to.
         * @param   string      $filename       Filename of template to render.
         * @param   int         $context        Rendering context.
         */
        public function save($savename, $filename, $context = null)
        /**/
        {
            file_put_contents($savename, $this->fetch($filename, $context));
        }
    }
}
