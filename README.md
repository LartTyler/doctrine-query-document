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