<?php

/**
 * @author Michael Slyshkin <m.slyshkin@gmail.com>
 */

namespace RsORM\Query\Builder;

use RsORM\Query;
use RsORM\Query\Engine\MySQL;
use RsORM\Query\Engine\MySQL\Flag;

/**
 * @method Delete table(string $name)
 * @method Delete where(Filter $filter)
 * @method Delete order(array $fields)
 * @method Delete limit(int $offset, int $count)
 * @method Delete flags(Flag\AbstractFlag[] $flags)
 */
class Delete implements BuilderInterface {
    
    use TraitTarget, TraitLimit, TraitOrder, TraitFlags,
            TraitWhere;
    
    /**
     * @return MySQL\AbstractExpression
     */
    public function build() {
        return Query\Engine::mysql()->delete(
                $this->_buildTarget(),
                $this->_buildWhere(),
                $this->_buildOrder(),
                $this->_buildLimit(),
                $this->_buildFlags());
    }
    
    /**
     * @return string
     */
    protected function _targetClass() {
        return MySQL\Clause\From::getClassName();
    }
    
}
