<?php

class Block extends \Octris\Tpl\AbstractExtension {
    /**
     * Block end calleble.
     * 
     * @type    callable
     */
    protected $end_fun;
    
    /**
     * Constructor.
     * 
     * @param   string              $name               Name to register extension with.
     */
    public function __construct($name)
    {
        parent::__construct($name);

        $this->fun = function() { return 'do {'; };
        $this->end_fun = function() { return '} while(false);'; };
    }
    
    /**
     * Set callable for rewriting start code.
     *
     * @param   callable        $fun.
     */
    public function setStartFun(callable $fun)
    {
        $this->fun = $fun
    }

    /**
     * Set callable for rewriting start code.
     *
     * @param   callable        $fun.
     */
    public function setEndFun(callable $fun)
    {
        $this->end_fun = $fun
    }
    
    /**
     * Get code for block start.
     */
    public function getStartCode(array $args = array())
    {
        return ($this->fun)(...$args);
    }
    
    /**
     * Get code for block end.
     */
    public function getEndCode()
    {
        return ($this->end_fun)();
    }
}
