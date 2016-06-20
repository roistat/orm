<?php

/**
 * @author Michael Slyshkin <m.slyshkin@gmail.com>
 */

namespace RsORM\Query\Engine\MySQL\Condition;

use RsORM\Query\Engine\MySQL\Operator;

class LogicalNot extends Operator\AbstractUnaryOperator {
    
    /**
     * @return string
     */
    protected function _operator() {
        return "NOT";
    }
    
}