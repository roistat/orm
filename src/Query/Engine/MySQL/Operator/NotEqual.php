<?php

/**
 * @author Michael Slyshkin <m.slyshkin@gmail.com>
 */

namespace RSDB\Query\Engine\MySQL\Operator;

class NotEqual extends AbstractPairOperator {
    
    /**
     * @return string
     */
    protected function _operator() {
        return "<>";
    }
    
}
