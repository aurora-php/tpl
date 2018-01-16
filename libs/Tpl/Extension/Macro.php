<?php

class Macro extends \Octris\Tpl\AbstractExtension {
    /**
     * Set callable for rewriting code.
     *
     * @param   callable        $fun        Callable for return code to rewrite template function with.
     */
    public function setFun(callable $fun)
    {
        $this->fun = $fun
    }
    
    /**
     * Call defined rewrite function and return result.
     * 
     * @return  string                                  Template code.
     */
    public function getCode(array $args = [])
    {
        return ($this->fun)(...$args);
    }
}
