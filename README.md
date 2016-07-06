# RsORM

[![Build Status](https://travis-ci.org/roistat/php-orm.svg?branch=master)](https://travis-ci.org/roistat/php-orm)

[**Getting started**](#getting-started)  
[**Documentation**](#documentation)  

# Getting started

RsORM — simple and fast set of DB utils. There are no magic methods. All code is typehinted. It could be used in high load projects even with partitioning and sharding. 

There are three tools in this set. You could combine them or use some of them separately.

* State package — responsible for object state management. Prepares data for usage in DB queries.
* Query package — query builder. It could use data from State package or any other sources. 
* Driver package — sends queries to database and parses results. 

It is the simple example of usage ORM, which includes initialization of MySQL driver and state engine, declaration of state entity class and procedures of creating, selecting and deleting objects.

```php
// Initialize MySQL driver and State engine
$driver = new Driver\MySQL();
$state = State\Engine::getInstance();
$query = Query\Engine::mysql();

// Declare class for Client entity
class Client extends State\Entity {
	public $id;
	public $name;
	public $age;
	public static function table() {
		return "user";
	}
	public static function id() {
		return "id";
	}
	public static function name() {
		return "name";
	}
	public static function age() {
		return "age";
	}
}

// Now we can create new client
$client = new Client();
$client->name = "Mike";
$client->age = 30;

// Prepare INSERT statement
$table = new Clause\Into(
	new Argument\Table(Client::table())
);
$diff = $state->diff($client);
// ["name" => "Mike", "age" => 30]
$vals = array_values($diff);
$values = new Clause\Values([
	new Argument\Value($vals[0]),
	new Argument\Value($vals[1]),
]);
$keys = array_keys($diff);
$fields = new Clause\Objects([
	new Argument\Field(new Argument\Column($keys[0])),
	new Argument\Field(new Argument\Column($keys[1])),
]);
$statement = $query->insert($table, $values, $fields);

// Save client into database table
$driver->query($statement);
$state->flush($client);

// Prepare select statement
$fields = new Clause\Objects([
	new Argument\Field(new Argument\Column(Client::name())),
	new Argument\Field(new Argument\Column(Client::age())),
]);
$from = new Clause\From(
	new Argument\Table(Client::table())
);
$filter = new Clause\Filter(
	new Condition\Equal(
		new Argument\Column(Client::id()),
		new Argument\Value(123)
	)
);
$statement = $query->select($fields, $from, $filter);

// Get client with id = 123
$client = $driver->fetchClass($statement, "Client");

// Prepare DELETE statement
$table = new Clause\From(
	new Argument\Table(Client::table())
);
$filter = new Clause\Filter(
	new Condition\Equal(
		new Argument\Column(Client::id()),
		new Argument\Value($client->id)
	)
);
$statement = $query->delete($table, $filter);

// Remove current client from DB
$driver->execute($statement);
```

# Documentation

[**State**](#state)  
[**Query**](#query)  
[**Driver**](#driver)

## State

Namespace: `RsORM\State`

State package consists of `State\Entity` and `State\Engine` classes.  
All entities should be extended from `RsORM\State\Entity` which encapsulates object state data and get/set methods. All actions are going in `RsORM\State\Engine`. Entity class may be extended with methods, which could return table name or field names.  
`State\Engine` class has static method `getInstance`, which initialize new object of `State\Engine` class or get existing and return it.

* `boolean isNew(State\Entity $entity)` - check `$entity` is new.
* `boolean isChanged(State\Entity $entity)` - check `$entity` is changed.
* `flush(State\Entity $entity)` - flush all changes in `$entity`.
* `array diff(State\Entity $entity)` - get all changed properties of `$entity`, return data in form of key-value array.

#### Examples:

```php
class Project extends State\Entity {
    public $id;
    public $name;
    public $user_id;
}
$engine = State\Engine::getInstance();

// You could create objects as usual
$project = new Project();

// New objects it's object without initial state
$isNew = $engine->isNew($project); // true
$isChanged = $engine->isChanged($project); // false

$project->name = "Test";
$project->user_id = 1;

$isNew = $engine->isNew($project); // true
$isChanged = $engine->isChanged($project); // true
$diff = $engine->diff($project); // ['name' => 'Test', 'user_id' => 1]

// Flush changes initial state, object now is not new and not changed
$engine->flush($project);

$isNew = $engine->isNew($project); // false
$isChanged = $engine->isChanged($project); // false
$diff = $engine->diff($project); // []
```

## Query

[**Engine\MySQL**](#enginemysql)  
[**MySQL\Argument**](#mysqlargument)  
[**MySQL\Operator**](#mysqloperator)  
[**MySQL\Condition**](#mysqlcondition)  
[**MySQL\Func**](#mysqlfunc)  
[**MySQL\Flag**](#mysqlflag)  
[**MySQL\Clause**](#mysqlclause)  
[**MySQL\Statement**](#mysqlstatement)

### Query\Engine

Namespace: `RsORM\Query\Engine`

Engine builds SQL statements by using MySQL class.

### Engine\MySQL

Namespace: `RsORM\Query\Engine\MySQL`

MySQL driver builds valid MySQL statements.

`Query\Engine::mysql()->select(...)`

#### Example

```php
$fields = new Clause\Objects([
	new Argument\Field(new Argument\Column("id")),
	new Argument\Field(new Argument\Column("name")),
	new Argument\Field(new Argument\Column("password")),
]);
$table = new Clause\From(new Argument\Table("users"));
$filter = new Clause\Filter(new Condition\Equal(
	new Argument\Column("deleted"),
	new Argument\Value(0)
));
$engine = Query\Engine::mysql();
$stmt = $engine->select($fields, $table, $filter);
$stmt->prepare(); // SELECT `id`, `name`, `password` FROM `table` WHERE `deleted` = ?
$stmt->values(); // [0]
```

### MySQL\Argument

Namespace: `RsORM\Query\Engine\MySQL\Argument`

Argument is a basic entity of MySQL statement. There are several types of them:

- *Any* build SQL identifier for any field
- *Value* build value object in SQL-statement, as ?placeholder
- *NullValue* build NULL object in SQL-statement
- *DefaultValue* build DEFAULT object in SQL-statement
- *Table* build object of table
- *Column* build object of column
- *Alias* build object of alias for table or column objects
- *Field* is a complex object, build field object from column and alias (the last parameter is optional)
- *Asc* is a complex object, build argument for sorting object
- *Desc* is a complex object, build argument for sorting object

#### Examples

```php
// Any
$arg = new Argument\Any();
$arg->prepare(); // *

// Value
$arg = new Argument\Value(123);
$arg->prepare(); // ?
$arg->value(); // 123

// NullValue
$arg = new Argument\NullValue();
$arg->prepare(); // NULL

// DefaultValue
$arg = new Argument\DefaultValue();
$arg->prepare(); // DEFAULT

// Column
$arg = new Argument\Column("id");
$arg->prepare(); // `id`

// Table and alias are same

// Asc and Desc
$arg = new Argument\Asc(new Argument\Column("id"));
$arg->prepare(); // `id` ASC
$arg = new Argument\Desc(new Argument\Column("id"));
$arg->prepare(); // `id` DESC

// Field
$arg = new Argument\Field(
	new Argument\Column("pass"),
	new Argument\Alias("password") // optional
);
$arg->prepare(); // `pass` AS `password`
```

### MySQL\Operator

Namespace: `RsORM\Query\Engine\MySQL\Operator`

Operator is a basic expression in SQL syntax. Operators implement `MultiValueInterface`. There are several types of them:

- Unary operators - operators with only one operand
Syntax: `new Operator($operand)`
- Binary operators - operators with two operands
Syntax: `new Operator($operand1, $operand2)`
- Multiple operators - operators with one or more operands
Syntax: `new Operator([$operand1, $operand2, ...])`
- Custom operators - operators with non-standard structure

Usually operators are the part of filter entity in SQL statements. That`s why, the most part of them are located in the `MySQL\Condition` namespace. Non-logic operators are located here, in `MySQL\Operator`.

#### Example

```php
// Assign
$operator = new Operator\Assign(
	new Argument\Column("id"),
	new Argument\Value(123)
);
$operator->prepare(); // `id` = ?
$operator->values(); // [123]
```

### MySQL\Condition

Namespace: `RsORM\Query\Engine\MySQL\Condition`

Logical expressions consist of operators. Logical expressions are the part of the MySQL engine for query builder. Conditions are built from logical operators and arguments.

Operators:

* Unary operators
    * LogicalNot
* Binary operators
    * Equal, NotEqual
    * Lt, Lte, Gt, Gte
    * Is, IsNot, IsNull, IsNotNull
    * Like
* Multiple operators
    * LogicalAnd
    * LogicalOr
* Custom operators
    * Between
    * In

#### Examples

```php
// Binary operator
$expr = new Condition\Equal(new Argument\Column("id"), new Argument\Value(123));
$expr->prepare(); // `id` = ?
$expr->values(); // [123]

// Unary operator
$expr = new Condition\Equal(new Argument\Column("id"), new Argument\Value(123));
$expr2 = new Condition\Not($expr);
$expr2->prepare(); // NOT (`id` = ?)
$expr2->values(); // [123]

// Multiple operator
$expr = new Condition\LogicalAnd([new Argument\Value(1), new Argument\Value(2), new Argument\Value(3)]);
$expr->prepare(); // ? AND ? AND ?
$expr->values(); // [1, 2, 3]

// Between operator
$expr = new Condition\Between(new Argument\Column("id"), new Argument\Value(10), new Argument\Value(20));
$expr->prepare(); // `id` BETWEEN ? AND ?
$expr->values(); // [10, 20]

// In operator
$expr = new Condition\In(new Argument\Column("id"), [new Argument\Value(1), new Argument\Value(10), new Argument\Value(100)]);
$expr->prepare(); // `id` IN (?, ?, ?)
$expr->values(); // [1, 10, 100]
```

### MySQL\Func

Namespace: `RsORM\Query\Engine\MySQL\Func`

Predefined MySQL functions are part of various MySQL statements.

Functions with optional distinct parameter:

 - Avg
 - Count
 - Sum

Functions with multiple parameters:

 - Concat

#### Examples

```php
// COUNT without DISTINCT
$func = new Func\Count(new Argument\Column("id"));
$func->prepare(); // COUNT(`id`)
$func->values(); // []

// COUNT with DISTINCT
$func = new Func\Count(new Argument\Column("id"), true);
$func->prepare(); // COUNT(DISTINCT `id`)
$func->values(); // []

// CONCAT
$func = new Func\Concat([
	new Argument\Value("qwe"),
	new Argument\Column("infix"),
	new Argument\Value("rty"),
]);
$func->prepare(); // CONCAT(?, `infix`, ?)
$func->values(); // ["qwe", "rty"]

// Select with function example
$func = new Func\Concat([
	new Argument\Value("prefix"),
	new Argument\Value("postfix"),
]);
$fields = new Clause\Objects([$func]);
$stmt = Query\Engine::mysql()->select($fields);
$stmt->prepare(); // SELECT CONCAT(?, ?)
$stmt->values(); // ["prefix", "postfix"]
```

### MySQL\Flag

Namespace: `RsORM\Query\Engine\MySQL\Flag`

Flag is a part of clause Flags, which is part of different SQL-statements. All flags implement basic `ObjectInterface` and have only one public method `prepare`. Constructor has no parameters. Here is all available flags:

* All
* Delayed
* Distinct
* DistinctRow
* HighPriority
* Ignore
* LowPriority
* Quick
* SQLBigResult
* SQLBufferResult
* SQLCache
* SQLCalcFoundRows
* SQLNoCache
* SQLSmallResult
* StraightJoin

#### Examples

```php
$flag = new Flag\SQLSmallResult();
$flag->prepare(); // SQL_SMALL_RESULT
```

### MySQL\Clause

Namespace: `RsORM\Query\Engine\MySQL\Clause`

Clause is a part of SQL-statement. It builds from arguments, operators, conditions, SQL-expressions. All clauses implement `MultiValueInterface`.

#### Examples

```php
// Objects
$fields = new Clause\Objects([
	new Argument\Field(new Argument\Column("id")),
	new Argument\Field(new Argument\Column("name")),
]);
$fields->prepare(); // `id`, `name`
$fields->values(); // []

// Fields
$fields = new Clause\Fields([
	new Argument\Field(new Argument\Column("id")),
	new Argument\Field(new Argument\Column("name")),
]);
$fields->prepare(); // (`id`, `name`)
$fields->values(); // []

// From
$target = new Clause\From(new Argument\Table("table"));
$target->prepare(); // FROM `table`
$target->values(); // []

// Into
$target = new Clause\Into(new Argument\Table("table"));
$target->prepare(); // INTO `table`
$target->values(); // []

// Target
$target = new Clause\Target(new Argument\Table("table"));
$target->prepare(); // `table`
$target->values(); // []

// Filter
$filter = new Clause\Filter(
	new Condition\And([
		new Condition\Equal(
			new Argument\Column("id"),
			new Argument\Value(123);
		),
		new Condition\Equal(
			new Argument\Column("alive"),
			new Argument\Value(1);
		)
	])
);
$filter->prepare(); // WHERE `id` = ? AND `alive` = ?
$filter->values(); // [123, 1]

// Having
$having = new Clause\Having(
	new Condition\And([
		new Condition\Equal(
			new Argument\Column("id"),
			new Argument\Value(123);
		),
		new Condition\Equal(
			new Argument\Column("alive"),
			new Argument\Value(1);
		)
	])
);
$having->prepare(); // HAVING `id` = ? AND `alive` = ?
$having->values(); // [123, 1]

// Set
$set = new Clause\Set([
	new Operator\Assign(
		new Argument\Column("id"),
		new Argument\Value(123)
	),
	new Operator\Assign(
		new Argument\Column("name"),
		new Argument\Value("Mike")
	)
]);
$set->prepare(); // SET `id` = ?, `name` = ?
$set->values(); // [123, "Mike"]

// Values
$values = new Clause\Values([
	new Argument\Value(123),
	new Argument\Value("Mike")
]);
$values->prepare(); // VALUES (?, ?)
$values->values(); // [123, "Mike"]

// Group
$group = new Clause\Group([
	new Argument\Column("id"),
	new Argument\Column("name")
]);
$group->prepare(); // GROUP BY `id`, `name`
$group->values(); // []

// Order
$order = new Clause\Order([
	new Argument\Asc(new Argument\Column("id")),
	new Argument\Desc(new Argument\Column("name"))
]);
$order->prepare(); // ORDER BY `id` ASC, `name` DESC
$order->values(); // []

// Limit
$limit = new Clause\Limit(
	new Argument\Value(5),
	new Argument\Value(10)
);
$limit->prepare(); // LIMIT ?, ?
$limit->values(); // [5, 10]

// Flags
$flags = new Clause\Flags([
	new Flag\Ignore(),
	new Flag\SQLNoCache(),
]);
$flags->prepare(); // IGNORE SQL_NO_CACHE
```

### MySQL\Statement

Namespace: `RsORM\Query\Engine\MySQL\Statement`

SQL statements implement `MultiValueInterface` and are built from `MySQL\Clause` objects.

```php
Select::__construct(
	Clause\Objects $fields,
	Clause\From $table = null,
	Clause\Filter $filter = null,
	Clause\Group $group = null,
	Clause\Having $having = null,
	Clause\Order $order = null,
	Clause\Limit $limit = null,
	Clause\Flags $flags = null
);
```

- `$fields` - set of fields for Select statement, required parameter
- `$table` - target table, optional parameter
- `$filter` - condition for select statement
- `$group` - grouping
- `$having` - having condition
- `$order` - ordering (it can be asc or desc, asc by default)
- `$limit` - limiting
- `$flags` - flags in the beginning of the statement

```php
Delete::__construct(
	Clause\From $table,
	Clause\Filter $filter = null,
	Clause\Order $order = null,
	Clause\Limit $limit = null,
	Clause\Flags $flags = null
);
```

- `$table` - required parameter
- `$filter`, `$order`, `$limit`, `$flags` - are the same as in select statement

```php
Insert::__construct(
	Clause\Into $table,
	Clause\Values $values,
	Clause\Fields $fields = null,
	Clause\Flags $flags = null
);
```

- `$table` - required parameter
- `$values` - required parameter, set values
- `$fields` - optional parameter, set of inserted fields
- `$flags` - flags in the beginning of the statement

```php
Update::__construct(
	Clause\Target $table,
	Clause\Set $set,
	Clause\Filter $filter = null,
	Clause\Order $order = null,
	Clause\Limit $limit = null,
	Clause\Flags $flags = null
);
```

- `$table` - required parameter
- `$set` - also required parameter, set of key-value
- `$filter`, `$order`, `$limit`, `$flags` - are the same as in select statement

#### Examples

```php
// Select
$fields = new Clause\Objects([
	new Argument\Field(new Argument\Column("id")),
	new Argument\Field(new Argument\Column("name")),
]);
$table = new Clause\From(new Argument\Table("table"));
$filter = new Clause\Filter(new Condition\LogicalOr([
	new Condition\Equal(
		new Argument\Column("id"),
		new Argument\Value(10)),
	new Condition\Equal(
		new Argument\Column("id"),
		new Argument\Value(20)
	),
]));
$group = new Clause\Group([new Argument\Column("id")]);
$having = new Clause\Having(
	new Condition\Equal(
		new Argument\Column("alive"),
		new Argument\Value(true)
	)
);
$order = new Clause\Order([new Argument\Desc(new Argument\Column("id"))]);
$limit = new Clause\Limit(new Argument\Value(5), new Argument\Value(10));
$flags = new Clause\Flags([
	new Flag\Distinct(),
	new Flag\HighPriority(),
	new Flag\SQLNoCache(),
]);
$stmt = new Statement\Select($fields, $table, $filter, $group, $having, $order, $limit);
$stmt->prepare(); // SELECT DISTINCT HIGH_PRIORITY SQL_NO_CACHE `id`, `name` FROM `table` WHERE (`id` = ?) OR (`id` = ?) GROUP BY `id` HAVING `alive` = ? ORDER BY `id` DESC LIMIT ?, ?
$stmt->values(); // [10, 20, 1, 5, 10]

// Delete
$table = new Clause\From(new Argument\Table("table"));
$filter = new Clause\Filter(new Condition\LogicalOr([
	new Condition\Equal(new Argument\Column("id"), new Argument\Value(10)),
	new Condition\Equal(new Argument\Column("id"), new Argument\Value(20)),
]));
$order = new Clause\Order([new Argument\Desc(new Argument\Column("id"))]);
$limit = new Clause\Limit(new Argument\Value(5), new Argument\Value(10));
$flags = new Clause\Flags([
	new Flag\LowPriority(),
	new Flag\Quick(),
	new Flag\Ignore(),
]);
$stmt = new Statement\Delete($table, $filter, $order, $limit);
$stmt->prepare(); // DELETE LOW_PRIORITY QUICK IGNORE FROM `table` WHERE (`id` = ?) OR (`id` = ?) ORDER BY `id` DESC LIMIT ?, ?
$stmt->values(); // [10, 20, 5, 10]

// Insert
$fields = new Clause\Fields([
	new Argument\Column("id"),
	new Argument\Column("name"),
	new Argument\Column("qwe"),
]);
$table = new Clause\Into(new Argument\Table("table"));
$values = new Clause\Values([
	new Argument\Value(1),
	new Argument\Value("Mike"),
	new Argument\NullValue(),
]);
$flags = new Clause\Flags([
	new Flag\Delayed(),
	new Flag\Ignore(),
]);
$stmt = new Statement\Insert($table, $values, $fields);
$stmt->prepare(); // INSERT DELAYED IGNORE INTO `table` (`id`, `name`, `qwe`) VALUES (?, ?, NULL)
$stmt->values(); // [1, "Mike"]

// Update
$table = new Clause\Target(new Argument\Table("table"));
$set = new Clause\Set([
	new Operator\Assign(new Argument\Column("id"), new Argument\Value(1)),
	new Operator\Assign(new Argument\Column("name"), new Argument\Value("Mike")),
	new Operator\Assign(new Argument\Column("qwerty"), new Argument\NullValue()),
]);
$filter = new Clause\Filter(new Condition\LogicalOr([
	new Condition\Equal(new Argument\Column("id"), new Argument\Value(10)),
	new Condition\Equal(new Argument\Column("id"), new Argument\Value(20)),
]));
$order = new Clause\Order([new Argument\Desc(new Argument\Column("id"))]);
$limit = new Clause\Limit(new Argument\Value(5), new Argument\Value(10));
$flags = new Clause\Flags([
	new Flag\LowPriority(),
	new Flag\Ignore(),
]);
$stmt = new Statement\Update($table, $set, $filter, $order, $limit);
$stmt->prepare(); // "UPDATE LOW_PRIORITY IGNORE `table` SET `id` = ?, `name` = ?, `qwerty` = NULL WHERE (`id` = ?) OR (`id` = ?) ORDER BY `id` DESC LIMIT ?, ?
$stmt->values(); // [1, "Mike", 10, 20, 5, 10]
```

### MySQL\Statement\Builder

Statement builder is used to build statements with ease

#### Examples

```php
$driver = new Driver\Mysql();
$builder = Query\Engine\MySQL\Statement\Builder::getInstance();
$selectFirstTen = $builder->select()->from('user')->limit(10)->build();
$firstTenUsers = $driver->fetchClass($selectFirstTen, 'User');
$selectSecondTen = $builder->limit(10, 10)->build();
$tenToTwentyUsers = $driver->fetchClass($selectSecondTen, 'User');
```

## Driver

### Driver\MySQL

Namespace: `RsORM\Driver\MySQL`

PDO abstract layer. Connection is initialized by first prepare / execute.

 - `__construct(string $host, int $port, string $user, string $pass, string $dbname)` All parameters are optional.
 - `setCharset(string $charset)` Charset are specified by constants. For example, `Driver\MySQL::UTF8`
 - `setOptions(array $options)` Set valid PDO options.
 - `fetchAssoc(Statement\AbstractStatement $statement)` Prepare, execute SQL-statement and return associated array (row).
 - `fetchAllAssoc(Statement\AbstractStatement $statement)` Prepare, execute SQL-statement and return associated array (rows).
 - `fetchClass(Statement\AbstractStatement $statement, string $class)` Prepare, execute SQL-statement and return object of specified class.
 - `fetchAllClass(Statement\AbstractStatement $statement, string $class)` Prepare, execute SQL-statement and return specified class object array.
 - `query(Statement\AbstractStatement $statement)` Prepare and execute SQL-statement.
 - `getLastInsertId()` Return last insert ID.

#### Example

```php
$dbh = new Driver\MySQL("127.0.0.1", 3306, "root", "123456", "main_db");
$dbh->setCharset(Driver\MySQL::UTF8);
$dbh->setOptions([
	\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
]);
$stmt = Query\Engine::mysql()->select(...);
$dbh->fetchAssoc($stmt); // return row
$dbh->fetchAllAssoc($stmt); // return array
$dbh->fetchClass($stmt, "User"); // return object of User class
$dbh->fetchAllClass($stmt, "User"); // return array of User objects
$stmt = Query\Engine::mysql()->insert(...);
$dbh->query($stmt); // true on success and false on failure
$dbh->getLastInsertId(); // return last insert ID
```

# License

MIT
