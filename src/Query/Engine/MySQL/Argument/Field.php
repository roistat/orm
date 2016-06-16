<?php

/**
 * @author Michael Slyshkin <m.slyshkin@gmail.com>
 */

namespace RsORM\Query\Engine\MySQL\Argument;

use RsORM\Query\Engine\MySQL;

class Field extends MySQL\AbstractExpression {
    
    /**
     * @param MySQL\ExpressionInterface $expression
     * @param Alias $alias
     */
    public function __construct(MySQL\ExpressionInterface $expression, Alias $alias = null) {
        parent::__construct([$expression, $alias]);
    }
    
    /**
     * @return string
     */
    public function prepare() {
        return implode(" AS ", $this->_prepareArguments());
    }
    
}
