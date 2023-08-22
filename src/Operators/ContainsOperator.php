<?php
	namespace DaybreakStudios\DoctrineQueryDocument\Operators;

	use DaybreakStudios\DoctrineQueryDocument\Exception\InvalidFieldValueException;
	use DaybreakStudios\DoctrineQueryDocument\Exception\QueryException;
	use DaybreakStudios\DoctrineQueryDocument\QueryDocumentInterface;
	use DaybreakStudios\DoctrineQueryDocument\ResolverContext;
	use Doctrine\DBAL\Types\Types;
	use Doctrine\ORM\Query\Expr\Composite;

	class ContainsOperator extends AbstractOperator {
		public function __construct() {
			parent::__construct('contains');
		}

		protected function validate(string $field, mixed $value): void {
			if (is_scalar($value))
				return;

			throw new InvalidFieldValueException($field, 'scalar', $this->getKey());
		}

		protected function doProcess(
			QueryDocumentInterface $document,
			object|string $field,
			mixed $value,
			Composite $parent,
		): void {
			$resolved = $document->getResolver()->resolve(
				$field,
				[
					ResolverContext::RESOLVE_ASSOCIATIONS_TO_ID => false,
					ResolverContext::RESOLVE_EMBEDDED_JSON_TO_EXTRACT_FUNC => false,
				],
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
						$jsonPath,
					),
				);
			} else if ($document->getResolver()->getMetadata($alias)->getTypeOfField($targetField) === Types::JSON) {
				$parent->add(
					sprintf(
						'JSON_CONTAINS(%s, %s)',
						$resolved,
						$document->expr()->addParameter($value),
					),
				);
			} else if ($document->getResolver()->getMetadata($alias)->isCollectionValuedAssociation($targetField)) {
				$parent->add(
					sprintf(
						'%s MEMBER OF %s',
						$document->expr()->addParameter($value),
						$resolved,
					),
				);
			} else {
				throw new QueryException(
					'The `$contains` operator can only be used on JSON or a collection valued association',
				);
			}
		}
	}
