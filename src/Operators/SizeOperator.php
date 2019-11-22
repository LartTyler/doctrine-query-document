<?php
	namespace DaybreakStudios\DoctrineQueryDocument\Operators;

	use DaybreakStudios\DoctrineQueryDocument\Exception\InvalidFieldValueException;
	use DaybreakStudios\DoctrineQueryDocument\Exception\UnknownOperatorException;
	use DaybreakStudios\DoctrineQueryDocument\QueryDocumentInterface;
	use DaybreakStudios\DoctrineQueryDocument\ResolverContext;
	use Doctrine\ORM\Query\Expr\Composite;
	use Doctrine\ORM\Query\Expr\Func;

	class SizeOperator extends AbstractOperator {
		/**
		 * SizeOperator constructor.
		 */
		public function __construct() {
			parent::__construct('size');
		}

		/**
		 * {@inheritdoc}
		 */
		protected function validate(string $field, $value): void {
			if (is_numeric($value) || is_array($value))
				return;

			throw new InvalidFieldValueException($field, 'number of a sub-document', $this->getKey());
		}

		/**
		 * {@inheritdoc}
		 */
		protected function doProcess(QueryDocumentInterface $document, $field, $value, Composite $parent): void {
			if (!is_array($value))
				$value = ['$eq' => $value];

			$node = new Func(
				'SIZE',
				[
					$document->getResolver()->resolve(
						$field,
						[
							ResolverContext::RESOLVE_ASSOCIATIONS_TO_ID => false,
						]
					),
				]
			);

			foreach ($value as $key => $item) {
				$operator = $document->getQueryManager()->getOperator($key);

				if (!$operator)
					throw new UnknownOperatorException($key);

				$operator->process($document, $node, $item, $parent);
			}
		}
	}
