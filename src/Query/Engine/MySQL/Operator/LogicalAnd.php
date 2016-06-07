<?php

/**
 * @author Michael Slyshkin <m.slyshkin@gmail.com>
 */

namespace RSDB\Query\Engine\MySQL\Operator;

class LogicalAnd extends AbstractMultipleOperator {
    
    /**
     * @return string
     */
    protected function _operator() {
        return "AND";
    }
    
}
