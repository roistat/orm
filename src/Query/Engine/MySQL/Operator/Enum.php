<?php

/**
 * @author Michael Slyshkin <m.slyshkin@gmail.com>
 */

namespace RSDB\Query\Engine\MySQL\Operator;

class Enum extends AbstractComplexOperator {
    
    /**
     * @return string
     */
    public function prepare() {
        return implode(", ", $this->_prepareValues());
    }
    
}
