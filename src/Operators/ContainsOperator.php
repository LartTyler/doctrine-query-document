<?php
	namespace DaybreakStudios\DoctrineQueryDocument\Operators;

	use DaybreakStudios\DoctrineQueryDocument\Exception\InvalidFieldValueException;
	use DaybreakStudios\DoctrineQueryDocument\Exception\QueryException;
	use DaybreakStudios\DoctrineQueryDocument\QueryDocumentInterface;
	use DaybreakStudios\DoctrineQueryDocument\ResolverContext;
	use Doctrine\DBAL\Types\Type;
	use Doctrine\ORM\Query\Expr\Composite;

	class ContainsOperator extends AbstractOperator {
		/**
		 * ContainsOperator constructor.
		 */
		public function __construct() {
			parent::__construct('contains');

			// {"campaigns":{"$memberOf":1}}
			// {"campaigns":{"$hasMember":1}}

			// {"campaigns":{"$contains":1}} -> ?0 MEMBER OF campaigns
			// {"someJsonField":{"$contains":1}} -> JSON_CONTAINS(someJsonField, ?0)
			// {"someJsonField.embeddedArray":{"$contains":1}} -> JSON_CONTAINS(someJsonField, ?0, '$.embeddedArray')
		}

		/**
		 * {@inheritdoc}
		 */
		protected function validate(string $field, $value): void {
			if (is_scalar($value))
				return;

			throw new InvalidFieldValueException($field, 'scalar', $this->getKey());
		}

		/**
		 * {@inheritdoc}
		 */
		protected function doProcess(QueryDocumentInterface $document, $field, $value, Composite $parent): void {
			$resolved = $document->getResolver()->resolve(
				$field,
				[
					ResolverContext::RESOLVE_ASSOCIATIONS_TO_ID => false,
					ResolverContext::RESOLVE_EMBEDDED_JSON_TO_EXTRACT_FUNC => false,
				]
			);

			$alias = strtok($resolved, '.');
			$targetField = strtok('.');

			// If there's more past the second tokenize attempt, we're targeting an embedded JSON document.
			if ($jsonPath = strtok('')) {
				$parent->add(
					sprintf(
						'JSON_CONTAINS(%s.%s, %s, "$.%s")',
						$alias,
						$targetField,
						$document->expr()->addParameter($value),
						$jsonPath
					)
				);
			} else if ($document->getResolver()->getMetadata($alias)->getTypeOfField($targetField) === Type::JSON) {
				$parent->add(
					sprintf(
						'JSON_CONTAINS(%s, %s)',
						$resolved,
						$document->expr()->addParameter($value)
					)
				);
			} else if ($document->getResolver()->getMetadata($alias)->isCollectionValuedAssociation($targetField)) {
				$parent->add(
					sprintf(
						'%s MEMBER OF %s',
						$document->expr()->addParameter($value),
						$resolved
					)
				);
			} else {
				throw new QueryException(
					'The `$contains` operator can only be used on JSON or a collection valued association'
				);
			}
		}
	}