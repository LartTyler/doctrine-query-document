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
`DaybreakStudios\DoctrineQueryDocument\QueryManager`.

Since operators are based on MongoDB's query operators, please see Mongo's documentation for information on using the
operators.

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

You may choose to skip registering built-in operators when creating your query manager by passing `false` as the third
argument in the constructor.
