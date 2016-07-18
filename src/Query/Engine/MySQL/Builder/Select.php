<?php

/**
 * @author Michael Slyshkin <m.slyshkin@gmail.com>
 */

namespace RsORM\Query\Engine\MySQL\Builder;

use RsORM\Query;
use RsORM\Query\Engine\MySQL;
use RsORM\Query\Engine\MySQL\Flag;
use RsORM\Query\Engine\MySQL\Clause;

/**
 * @method Select table(string $name)
 * @method Select where(Filter $filter)
 * @method Select group(array $fields)
 * @method Select having(Filter $filter)
 * @method Select order(array $fields)
 * @method Select limit(int $offset, int $count)
 * @method Select flags(Flag\AbstractFlag[] $flags)
 */
class Select implements BuilderInterface {
    
    use TraitObjects, TraitTable, TraitGroup, TraitLimit, TraitOrder, TraitFlags,
            TraitWhere, TraitHaving;
    
    /**
     * @param array $objects
     */
    public function __construct(array $objects = []) {
        $this->_setObjects($objects);
    }
    
    /**
     * @return MySQL\AbstractExpression
     */
    public function build() {
        return Query\Engine::mysql()->select(
                $this->_buildObjects(),
                $this->_buildTable(),
                $this->_buildWhere(),
                $this->_buildGroup(),
                $this->_buildHaving(),
                $this->_buildOrder(),
                $this->_buildLimit(),
                $this->_buildFlags()
                );
    }
    
    /**
     * @return string
     */
    protected function _targetClass() {
        return Clause\From::getClassName();
    }
    
}
