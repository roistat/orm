<?php

/**
 * @author Michael Slyshkin <m.slyshkin@gmail.com>
 */

namespace RSDB\Query\Engine\MySQL\Operator;

class LogicalOr extends AbstractMultipleOperator {
    
    /**
     * @return string
     */
    protected function _operator() {
        return "OR";
    }
    
}
