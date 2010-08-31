<?php

namespace org\octris\core {
    require_once('tpl/sandbox.class.php');
    
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
        
        /****v* tpl/$path
         * SYNOPSIS
         */
        protected $path = array();
        /*
         * FUNCTION
         *      registered path'
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
        
        /****m* tpl/registerPath
         * SYNOPSIS
         */
        public function registerPath($pathname) 
        /*
         * FUNCTION
         *      register pathname to look for templates in
         * INPUTS
         *      * $pathname (mixed) -- name of path or array of path names to register
         ****
         */
        {
            if (!is_array($pathname)) $pathname = array($pathname);
            
            foreach ($pathname as $p) $t
                $this->path[] = rtrim($pathname, '/') . '/';
            }
        }
        
        /****m* tpl/compile
         * SYNOPSIS
         */
        public function compile($filename) 
        /*
         * FUNCTION
         *      executes template compiler with specified parameters
         * INPUTS
         *      * $params (mixed) -- parameters for compiler
         ****
         */
        {
            require_once('tpl/compiler.class.php');
            require_once('tpl/compiler/compress.class.php');
            
            tpl\compiler::registerPath($this->path);
            
            tpl\compiler\constant::setConstants($this->constants);

            $c   = new tpl\compiler();
            $c->setCompressLevel(1);
            $tpl = $c->parse($filename);
            
            $c   = new tpl\compiler\compress();
            $tpl = $c->process($tpl);
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
            $filename = preg_replace('/\/\/+/', '/', preg_replace('/\.\.?\/', '/', $filename));
            $filename = ltrim($filename, '/');

            if ()

            if (!$this->getCacheUsage()) {
                // use template compiler to compile template, if no cache path was set
                require_once('base/libs/lima_tpl/lima_tpl_compiler.class.php');

                if ($this->lookfirst < 0 || !file_exists($this->path[$this->lookfirst] . $filename)) {
                    $this->lookfirst = -1;

                    for ($i = 0; $i < count($this->path); $i++) {
                        if (file_exists($this->path[$i] . $filename)) {
                            $this->lookfirst = $i;
                        }
                    }           
                }

                if ($this->lookfirst < 0) {
                    throw new Exception(sprintf(
                        'template not found "%s" in directories "%s"!',
                        $filename,
                        implode(':', $this->path)
                    ));
                }

                $c = new lima_tpl_compiler($this);
                $out = $c->execute(array('pathname'  => $this->path[$this->lookfirst],
                                         'filename'  => $filename,
                                         'locale'    => $locale));
            } else {
                $out = $filename;
            }

            $this->sandbox->render($out, $locale);
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
