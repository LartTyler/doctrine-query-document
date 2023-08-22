<?php
	namespace DaybreakStudios\DoctrineQueryDocument\Operators;

	use DaybreakStudios\DoctrineQueryDocument\Exception\InvalidFieldValueException;
	use DaybreakStudios\DoctrineQueryDocument\Exception\UnknownOperatorException;
	use DaybreakStudios\DoctrineQueryDocument\QueryDocumentInterface;
	use DaybreakStudios\DoctrineQueryDocument\ResolverContext;
	use Doctrine\DBAL\Types\Types;
	use Doctrine\ORM\Query\Expr\Composite;
	use Doctrine\ORM\Query\Expr\Func;

	class SizeOperator extends AbstractOperator {
		public function __construct() {
			parent::__construct('size');
		}

		protected function validate(string $field, mixed $value): void {
			if (is_numeric($value) || is_array($value))
				return;

			throw new InvalidFieldValueException($field, 'number of a sub-document', $this->getKey());
		}

		protected function doProcess(
			QueryDocumentInterface $document,
			object|string $field,
			mixed $value,
			Composite $parent,
		): void {
			if (!is_array($value))
				$value = ['$eq' => $value];

			$resolved = $document->getResolver()->resolve(
				$field,
				[
					ResolverContext::RESOLVE_ASSOCIATIONS_TO_ID => false,
				],
			);

			if (stripos($resolved, 'JSON_UNQUOTE(JSON_EXTRACT') === 0) {
				// If the resolved field is an embedded JSON field, we can just replace the normal extract functions
				// with a call to `JSON_LENGTH`

				$node = new Composite(['JSON_LENGTH' . substr($resolved, 25, -1)]);
			} else {
				// Otherwise, we either have a field naming the JSON column itself (and not a field embedded in the
				// document, or just a normal column (which we don't need to mess with).

				$alias = strtok($resolved, '.');
				$path = strtok('');

				$metadata = $document->getResolver()->getMetadata($alias);

				if ($metadata->getTypeOfField($path) === Types::JSON)
					$node = new Composite(['JSON_LENGTH(' . $resolved . ')']);
			}

			if (!isset($node)) {
				// If $node isn't defined at this point, the field being matched against isn't a JSON field (embedded
				// or otherwise) and should be treated normally.

				$node = new Func(
					'SIZE',
					[
						$document->getResolver()->resolve(
							$field,
							[
								ResolverContext::RESOLVE_ASSOCIATIONS_TO_ID => false,
							],
						),
					],
				);
			}

			foreach ($value as $key => $item) {
				$operator = $document->getQueryManager()->getOperator($key);

				if (!$operator)
					throw new UnknownOperatorException($key);

				$operator->process($document, $node, $item, $parent);
			}
		}
	}
