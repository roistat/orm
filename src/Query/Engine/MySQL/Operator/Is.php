<?php

/**
 * @author Michael Slyshkin <m.slyshkin@gmail.com>
 */

namespace RSDB\Query\Engine\MySQL\Operator;

class Is extends AbstractPairOperand {
    
    /**
     * @return string
     */
    protected function _operator() {
        return "IS";
    }
    
}
