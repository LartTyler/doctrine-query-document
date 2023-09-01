# Installation
```
$ composer require dbstudios/doctrine-query-document
```

# Basic Usage
Simply create a query builder, and (at minimum) add the `from` and `select` statements. These are _required_ in order
for the query document to work. You may also add any other clauses (such as `where` or `orderBy` clauses) that you would
like to have in the resulting query. The query manager will append the query to the existing clauses in the query
builder.

```php
<?php
    // $objectManager should be an instance of Doctrine\Common\Persistence\ObjectManager

    $manager = new QueryManager($objectManager);
    $queryBuilder = $objectManager->createQueryBuilder()
        ->from('App\Entity\MyEntity', 'e')
        ->select('e');

    $manager->apply($queryBuilder, [
        'field' => 'value',
        'otherField' => [
            '$gt' => 10,
        ],
    ]);

    echo $queryBuilder->getDQL();

    // SELECT e FROM App\Entity\MyEntity e WHERE field = ?0 AND otherField > ?1
```

Any values passed in the second argumemnt to `apply()` will automatically be transformed to positional parameters and
will be set as a parameter on the query builder.

## Traversing Relationships
Fields on related entities may be queried using dot notation, in the form `relationshipField.field`. Imagine that your `MyEntity` class is related to `OtherEntity` through the field `otherEntity`.

```php
<?php
    // $objectManager should be an instance of Doctrine\Common\Persistence\ObjectManager

    $manager = new QueryManager($objectManager);
    $queryBuilder = $objectManager->createQueryBuilder()
        ->from('App\Entity\MyEntity', 'e')
        ->select('e');

    $manager->apply($queryBuilder, [
        'otherEntity.field' => 'value',
    ]);

    echo $queryBuilder->getDQL();

    // SELECT e FROM App\Entity\MyEntity e JOIN e.otherEntity join_1 WHERE join_1.field = ?0
```

## Traversing JSON Objects
MySQL 5.7 added support for the JSON type. For any fields whose type is `JSON`, dot-notated fields will automatically be
extracted. Imagine that your `MyEntity` class has a JSON field named `attributes`, which looks like this.

```json
{
    "myField": "value"
}
```

You could query for it like so.

```php
<?php
    // $objectManager should be an instance of Doctrine\Common\Persistence\ObjectManager

    $manager = new QueryManager($objectManager);
    $queryBuilder = $objectManager->createQueryBuilder()
        ->from('App\Entity\MyEntity', 'e')
        ->select('e');

    $manager->apply($queryBuilder, [
        'attributes.myField' => 'value',
    ]);

    echo $queryBuilder->getDQL();

    // SELECT e FROM App\Entity\MyEntity e WHERE JSON_UNQUOTE(JSON_EXTRACT(attributes, '$.myField')) = ?0
```

# Field Mapping
In some cases, it may make sense to map shortened field names to fields on related entities. For example, assume the
`otherEntity` field is a relation that contains a `name` field.

```php
<?php
    // $objectManager should be an instance of Doctrine\Common\Persistence\ObjectManager

    $manager = new QueryManager($objectManager);
    $queryBuilder = $objectManager->createQueryBuilder()
        ->from('App\Entity\MyEntity', 'e')
        ->select('e');

    $manager->apply($queryBuilder, [
        'otherEntityName' => 'value',
    ]);
```

Normally, the above example would fail, because `MyEntity` does not have a `otherEntityName` field. However, we can
alias the field like so.

```php
<?php
    $manager->setMappedField('App\\Entity\\MyEntity', 'otherEntityName', 'otherEntity.name');

    $manager->apply($queryBuilder, [
        'otherEntityName' => 'value',
    ]);
```

This can also work in reverse, allowing you to query a field that appears to be on a related object (or, more usefully, an array of related objects). For example, assume `MyEntity` has a one-to-many relationship with `MyOtherEntity`. `MyEntity` has a field named `otherEntitiesLength`, which holds a count of the number of elements in the `otherEntities` collection.

```php
<?php
    $manager->setMappedField('App\\Entity\\MyEntity', 'otherEntities.length', 'otherEntitiesLength');
    
    $manager->apply($queryBuilder, [
        'otherEntities.length' => [
            '$gte' => 1,
        ],
    ]);
```

# Field Projection
This library ships with a handy utility class that allows you to easily apply projections to result sets.

```php
<?php
    use DaybreakStudios\DoctrineQueryDocument\Projection\Projection;

    // $objectManager should be an instance of Doctrine\Common\Persistence\ObjectManager
    
    $manager = new QueryManager($objectManager);
    $queryBuilder = $objectManager->createQueryBuilder()
        ->from('App\Entity\MyEntity', 'e')
        ->select('e');
    
    $manager->apply($queryBuilder, [
    	'field' => 'value',
    ]);
    
    $projection = new Projection([
    	'id' => true,
    	'field' => true,
    	'otherEntity' => [
    		'id' => true,
    		'someField' => true,
        ],
    ]);
    
    echo json_encode(array_map(function(MyEntity $entity) use ($projection): array {
    	$data = [
            'id' => $entity->getId(),
            'name' => $entity->getName(),
            'field' => $entity->getField(),
        ];
    	
    	if ($projection->isAllowed('otherEntity')) {
    		$other = $entity->getOtherEntity();
    		
    		$data['otherEntity'] = [
    			'id' => $other->getId(),
    			'someField' => $other->getSomeField(),
    			'someOtherField' => $other->getSomeOtherField(),
            ];
    	}
    	
    	return $projection->filter($data);
    }, $queryBuilder->getQuery()->getResult()));
    
    // [{"id": 1, "field": "value", "otherEntity": {"id": 1, "someField": "value"}}, ...]
```

Projections can also be inverted by supplying `false` as the value of each node in the constructor argument. If you want
to explicitly control the default matching behavior, you can also provide the optional `$default` argument to the
constructor. Otherwise, default matching behavior will be inferred from the first element of the projection: allow if
the first element is `false` (the projection is a deny-list), or deny if the first element is `true` (the projection is
an allow-list).

There's also a static convenience method on `Projection` that allows you to build the projection object from a flat
object of string paths, like so. Like the constructor, you can also pass a second `$default` argument to explicitly set
the default matching behavior of the projection.

```php
<?php
    $input = [
    	'id' => true,
    	'field' => true,
    	'otherEntity.id' => true,
    	'otherEntity.someField' => true,
    ];
    
    $projection = Projection::fromFields($input);
```

In the above example, the resulting `Projection` object would be the same as the one in the original example. In some
cases, it may be more convenient to supply a flat map of paths, instead of a potentially deep array of paths (i.e. when
the projected fields are coming in from an API input).

You can also use the match-all operator "*" to control matching behavior for all fields in a group.

```php
<?php
    $input = [
        'child.*' => false,
        'child.id' => true,
        'child.name' => true,
    ];

    $projection = Projection::fromFields($input);

    assert($projection->isAllowed('someParentField'));
    assert($projection->isAllowed('child'));
    assert($projection->isAllowed('child.id'));
    assert($projection->isAllowed('child.name'));
    assert($projection->isDenied('child.foo'));
```

Projections can also differentiate between default or explicit allow/deny behavior. For example, consider the following
projection.

```php
<?php
    $projection = new Projection([
        'id' => true,
        'name' => false,
    ], true);

    // Both are the "explicit" variants of their respective behavior, since the keys are present in the list.
    assert($projection->isAllowedExplicitly('id'));
    assert($projection->isDeniedExplicitly('name'));
    
    // The Projection is configured to allow by default (the second constructor argument), so fields not present in the
    // list are allowed.
    assert($projection->isAllowed('foo'));

    // However, the default allow behavior is not an explicit allow, as the key is _not_ in the list.
    assert(!$projection->isAllowedExplicitly('foo'));
```

This can be useful if you have a field that is expensive to compute or serialize, and you _only_ want to include if the
projection specifically calls for it to be included.

You can also directly inspect the underlying value that projections use to repesent results using the
`Projection::query()` method, which returns an integer value that describes that result. Additionally, the
`QueryResult::describe()` method can be used to get a plain-english representation of a result for debugging or logging
purposes.

```php
<?php
    use DaybreakStudios\DoctrineQueryDocument\Projection\QueryResult;

    $projection = new Projection([
        'id' => true,
    ]);

    $result = $projection->query('id');
    
    assert($result === QueryResult::allow(true));
    assert(QueryResult::isAllow($result));
    assert(QueryResult::isExplicit($result))
    assert(QueryResult::isExplicitAllow($result));

    echo QueryResult::describe($result); // "explicit allow"
    echo QueryResult::describe($projection->query('foo')); // "deny"
```

A potential "gotcha" when working with projections relates to how the system determines what should and should not be
considered "explicit" versus "implicit" when querying a field. Consider the following projection and query.

```php
$projection = new Projection([
    'foo' => [
        '*' => false,
        'bar' => true,
    ]
]);

echo QueryResult::describe($projection->query('foo.bar')); // "explicit allow"
echo QueryResult::describe($projection->query('foo.baz')); // "explicit deny"

// Makes sense so far. But what about querying the parent node?
echo QueryResult::describe($projection->query('foo')); // "explicit allow"
```

While `foo` isn't given an allow or deny modifier (has a value of `true` or `false`), it _does_ show up as a member of
our projection, and should be given the "explicit" flag. Additionally, in order to make queries against child nodes, it
is assumed to be "allowed," as it wouldn't make sense for a query to mark `foo` as denied but still mark `foo.bar` as
allowed: how can a child be allowed when its parent wasn't?

# Custom Operators
You can add custom operator classes by implementing `DaybreakStudios\DoctrineQueryDocument\OperatorInterface`, or by
extending `DaybreakStudios\DoctrineQueryDocument\Operators\AbstractOperator`.

For example, you could implement the `$eq` symbol using the following class.

```php
<?php
    use DaybreakStudios\DoctrineQueryDocument\OperatorInterface;
    use DaybreakStudios\DoctrineQueryDocument\QueryDocumentInterface;
    use Doctrine\ORM\Query\Expr\Composite;

    class EqualsOperator implements OperatorInterface {
        /**
         * {@inheritdoc}
         */
        public function getKey(): string {
            return 'eq';
        }

        /**
         * {@inheritdoc}
         */
        public function process(QueryDocumentInterface $document, string $key, $value, Composite $parent): void {
            $document->expr()->eq($parent, $key, $value);
        }
    }
```

In the example above, the `getKey()` method should return the symbol used by the operator in a query document, without
the leading dollar sign. The `process()` method will be called when the operator is used, and will receive the active
query document object, the raw field name that the operator is being called on, the value of the field, and the
`Doctrine\ORM\Query\Expr\Composite` object that the resulting expression should be applied to.

In order to make changes to the query builder, you must use the `DaybreakStudios\DoctrineQueryDocument\Expr` object
returned from `QueryDocumentInterface::expr()`. Any method that takes a field name will automatically resolve the
dot-notated field name (such as `relationshipField.field`) to an appropriately aliased field name. Values will
automatically be changed to posisitional parameters, and will be added to the query builder's parameter list.

You would then need to register your operator with your query manager, like so.

```php
<?php
    // $objectManager should be an instance of Doctrine\Common\Persistence\ObjectManager

    $manager = new QueryManager($objectManager);
    $manager->setOperator(new EqualsOperator());
```

You may also pass an array of custom operators as the second argument to `QueryDocument`'s constructor.

# Built-in Operators
By default, this packages comes with several operators that will be automatically registered with instances of
`DaybreakStudios\DoctrineQueryDocument\QueryManager`. You may choose to skip registering built-in operators when
creating your query manager by passing `false` as the third argument in the constructor.

Since most operators are based on MongoDB's query operators, please see Mongo's documentation for information on using
the operators.

|Operator|Accepted Argument(s)|Documentation|
|:---|:---|:---|
|`$and`|Array&lt;Query&gt;|[Link](https://docs.mongodb.com/manual/reference/operator/query/and/#op._S_and)|
|`$or`|Array&lt;Query&gt;|[Link](https://docs.mongodb.com/manual/reference/operator/query/or/#op._S_or)|
|`$gt`|Number|[Link](https://docs.mongodb.com/manual/reference/operator/query/gt/#op._S_gt)|
|`$gte`|Number|[Link](https://docs.mongodb.com/manual/reference/operator/query/gte/#op._S_gte)|
|`$lt`|Number|[Link](https://docs.mongodb.com/manual/reference/operator/query/lt/#op._S_lt)|
|`$lte`|Number|[Link](https://docs.mongodb.com/manual/reference/operator/query/lte/#op._S_lte)|
|`$eq`|Any|[Link](https://docs.mongodb.com/manual/reference/operator/query/eq/#op._S_eq)|
|`$neq`|Any|[Link](https://docs.mongodb.com/manual/reference/operator/query/ne/#op._S_ne)|
|`$in`|Array&lt;Any&gt;|[Link](https://docs.mongodb.com/manual/reference/operator/query/in/#op._S_in)|
|`$nin`|Array&lt;Any&gt;|[Link](https://docs.mongodb.com/manual/reference/operator/query/nin/#op._S_nin)|
|`$like`|String|A MySQL style LIKE string ([Link](https://dev.mysql.com/doc/refman/5.7/en/string-comparison-functions.html#operator_like))|
|`$nlike`|String|A negated MySQL LIKE string ([Link](https://dev.mysql.com/doc/refman/5.7/en/string-comparison-functions.html#operator_like))|
|`$exists`|Boolean|[Link](https://docs.mongodb.com/manual/reference/operator/query/exists/#op._S_exists)|
|`$size`|Number or Operators|[Link](https://docs.mongodb.com/manual/reference/operator/query/size/) \[[see below](#size-operator)\]|
|`$contains`|Scalar|[see below](#contains-operator)|
|`$ncontains`|Scalar|[see below](#not-contains-operator)|

### Size Operator
The size operator accepts two different types of values. The first is the same type documented in the MongoDB docs: an
integer to match exact equality to.

```json
{
    "field": {
        "$size": 5
    }
}
```

The second is a more complex form, allowing you to use any other comparison operator to match the collection size.

```json
{
    "collection": {
        "$size": {
            "$gt": 0
        }
    },
    "otherCollection": {
        "$size": {
            "$in": [1, 2, 3]
        }
    }
}
```

This operator utilizes Doctrine's `SIZE` DQL function to retrieve the number of elements in a to-many association.

### Contains Operator
The `$contains` operator allows you to test if a collection or JSON array contains a given value.

When used on collection valued associations, Doctrine's `MEMBER OF` syntax is used to test if the given value is
contained in the collection.

```json
{
    "collection": {
        "$contains": 1
    }
}
```

The `$contains` operator can also be used on JSON fields, or on fields embedded within JSON fields.

```json
{
    "json.nested.field": {
        "$contains": 1
    }
}
```

### Not Contains Operator
This is the negated form of the `$contains` operator. Refer to the documentation for [`$contains`](#contains-operator)
for more info.
