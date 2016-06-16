<?php

/**
 * @author Michael Slyshkin <m.slyshkin@gmail.com>
 */

namespace RsORMTest\Query\Engine\MySQL\Expression;

use RsORM\Query\Engine\MySQL\Expression;
use RsORM\Query\Engine\MySQL\Argument;
use RsORM\Query\Engine\MySQL\Clause;
use RsORM\Query\Engine\MySQL\Statement;
use RsORMTest;

class SelectTest extends RsORMTest\Base {

    public function test() {
        $fields = new Clause\Fields([
            new Argument\Field(new Argument\Column("id")),
            new Argument\Field(new Argument\Column("name")),
        ]);
        $table = new Clause\From(new Argument\Table("table"));
        $filter = new Clause\Filter(new Expression\LogicalOr([
            new Expression\Equal(new Argument\Column("id"), new Argument\Value(10)),
            new Expression\Equal(new Argument\Column("id"), new Argument\Value(20)),
        ]));
        $group = new Clause\Group([new Argument\Column("id")]);
        $having = new Clause\Having(new Expression\Equal(new Argument\Column("alive"), new Argument\Value(true)));
        $order = new Clause\Order([new Argument\Desc(new Argument\Column("id"))]);
        $limit = new Clause\Limit(new Argument\Value(5), new Argument\Value(10));
        $stmt = new Statement\Select($fields, $table, $filter, $group, $having, $order, $limit);
        $this->assertSame("SELECT `id`, `name` FROM `table` WHERE (`id` = ?) OR (`id` = ?) GROUP BY `id` HAVING `alive` = ? ORDER BY `id` DESC LIMIT ?, ?", $stmt->prepare());
        $this->assertSame([10, 20, 1, 5, 10], $stmt->values());
    }
    
}
