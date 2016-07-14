<?php

/**
 * @author Michael Slyshkin <m.slyshkin@gmail.com>
 */

namespace RsORM\Query\Builder;

use RsORM\Query\Engine\MySQL\Clause;

trait TraitHaving {
    
    /**
     * @var Filter
     */
    private $_havingFilter;
    
    /**
     * @param Filter $filter
     * @return AbstractBuilder
     */
    public function having(Filter $filter) {
        $this->_havingFilter = $filter;
        return $this;
    }
    
    /**
     * @return Clause\Having
     */
    protected function _buildHaving() {
        $filter = $this->_havingFilter === null ? null : $this->_havingFilter->build();
        return $filter === null ? null : new Clause\Having($filter);
    }
}
