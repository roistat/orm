<?php

/**
 * @author Michael Slyshkin <m.slyshkin@gmail.com>
 */

namespace RsORM\Query\Engine\MySQL\Builder;

use RsORM\Query\Engine\MySQL\Argument;
use RsORM\Query\Engine\MySQL\Clause;

trait TraitTable {
    
    /**
     * @var Argument\Table
     */
    private $_table;
    
    /**
     * @return string
     */
    abstract protected function _targetClass();

    /**
     * @param string $name
     * @return $this
     */
    public function table($name) {
        $this->_table = new Argument\Table($name);
        return $this;
    }
    
    /**
     * @return Clause\Target|Clause\From|Clause\Into
     */
    protected function _buildTable() {
        $class = $this->_targetClass();
        return $this->_table === null ? null : new $class($this->_table);
    }
}
