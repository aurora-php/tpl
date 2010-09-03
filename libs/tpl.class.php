<?php

namespace org\octris\core {
    use \org\octris\core\tpl\compiler as compiler;
    
    /****c* core/tpl
     * NAME
     *      tpl
     * FUNCTION
     *      template engine main class
     * COPYRIGHT
     *      copyright (c) 2010 by Harald Lapp
     * AUTHOR
     *      Harald Lapp <harald@octris.org>
     ****
     */

    class tpl {
        /****v* tpl/$sandbox
         * SYNOPSIS
         */
        protected $sandbox;
        /*
         * FUNCTION
         *      sandbox for executing template in
         ****
         */
        
        /****v* tpl/$use_cache
         * SYNOPSIS
         */
        protected $use_cache = false;
        /*
         * FUNCTION
         *      whether to fetch compiled template from cache
         ****
         */
        
        /****v* tpl/$searchpath
         * SYNOPSIS
         */
        protected $searchpath = array();
        /*
         * FUNCTION
         *      path to look in for loading templates
         ****
         */
        
        /****v* tpl/$path
         * SYNOPSIS
         */
        protected $path = array(
            'tpl'   => '/tmp',      // output path for compiled templates
            'js'    => '/tmp',      // output path for compressed javascript
            'css'   => '/tmp'       // output path for compressed css
        );
        /*
         * FUNCTION
         *      output path for various file types
         ****
         */
        
        /****m* tpl/__construct
         * SYNOPSIS
         */
        public function __construct()
        /*
         * FUNCTION
         *      constructor
         ****
         */
        {
            $this->sandbox = new tpl\sandbox();
        }
        
        /****m* tpl/setValues
         * SYNOPSIS
         */
        public function setValues($array)
        /*
         * FUNCTION
         *      set values wort multiple variables
         * INPUTS
         *      * $array (array) -- key/value array with values
         ****
         */
        {
            $this->sandbox->setValues($array);
        }
        
        /****m* tpl/setValue
         * SYNOPSIS
         */
        public function setValue($name, $value)
        /*
         * FUNCTION
         *      set template value
         * INPUTS
         *      * $name (string) -- name to set
         *      * $value (mixed) -- value to set
         ****
         */
        {
            $this->sandbox->setValue($name, $value);
        }

        /****m* sandbox/registerMethod
         * SYNOPSIS
         */
        public function registerMethod($name, $callback, array $args)
        /*
         * FUNCTION
         *      register a custom template method
         * INPUTS
         *      * $name (string) -- name of macro to register
         *      * $callback (mixed) -- callback to call when macro is executed
         *      * $args (array) -- for testing arguments
         ****
         */
        {
            $this->sandbox->registerMethod($name, $callback, $args);
        }
        
        /****m* tpl/addSearchPath
         * SYNOPSIS
         */
        public function addSearchPath($pathname) 
        /*
         * FUNCTION
         *      register pathname to look for templates in
         * INPUTS
         *      * $pathname (mixed) -- name of path or array of path names to register
         ****
         */
        {
            if (!in_array($pathname, $this->searchpath)) {
                $this->searchpath[] = $pathname;
            }
        }
        
        /****m* tpl/setOutputPath
         * SYNOPSIS
         */
        public function setOutputPath($ext, $pathname)
        /*
         * FUNCTION
         *      set output path for compiled templates / compressed files
         * INPUTS
         *      * $ext (string) -- extension of file (filetype)
         *      * $pathname (string) -- pathname to set for extension
         * OUTPUTS
         *      
         ****
         */
        {
            if (array_key_exists($type, $this->path) && is_writable($path)) {
                $this->path[$type] = $path;
            }
        }
        
        /****m* tpl/compile
         * SYNOPSIS
         */
        protected function compile($inp, $out)
        /*
         * FUNCTION
         *      executes template compiler and css/javascript compressor
         * INPUTS
         *      * $inp (string) -- input filename
         *      * $out (string) -- output filename
         ****
         */
        {
            // tpl\compiler\constant::setConstants($this->constants);

            $c = new tpl\compiler();
            $c->addSearchPath($this->searchpath);

            if (($filename = $c->findFile($inp)) !== false) {
                $out = $c->process($filename);
            
                $c = new tpl\compress();
                $out = $c->process($out, $this->path['js'], $this->path['css']);
                
                // TODO: output
                // file_put_contents($out, $tpl);
            } else {
                die('unable to locate file!');
            }
        }
        
        /****m* tpl/render
         * SYNOPSIS
         */
        public function render($filename)
        /*
         * FUNCTION
         *      render a template and send output to stdout
         * INPUTS
         *      * $filename (string) -- filename of template to render
         ****
         */
        {
            $inp = ltrim(preg_replace('/\/\/+/', '/', preg_replace('/\.\.?\/', '/', $filename)), '/');
            $out = preg_replace('/[\s\.]/', '_', $inp) . '.php';

            if (!$this->use_cache) {
                // do not use cache -- first compile template
                $this->compile($inp, $out);
            }

            $this->sandbox->render($out, tpl\sandbox::T_CONTEXT_HTML);
        }
        
        /****m* tpl/fetch
         * SYNOPSIS
         */
        public function fetch($filename) 
        /*
         * FUNCTION
         *      render a template and return it as string
         * INPUTS
         *      * $filename (string) -- filename of template to render
         * OUTPUTS
         *      (string) -- rendered template
         ****
         */
        {
            ob_start();

            $this->render($filename);

            $return = ob_get_contents();
            ob_end_clean();

            return $return;
        }

        /****m* tpl/save
         * SYNOPSIS
         */
        function save($filename, $savename)
        /*
         * FUNCTION
         *      render a template and write it into a file
         * INPUTS
         *      * $filename (string) -- filename of template to render
         *      * $savename (string) -- savename
         ****
         */
        {
            file_put_contents($savename, $this->fetch($filename));
        }
    }
}
