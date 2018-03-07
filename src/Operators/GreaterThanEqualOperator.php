<?php
	namespace DaybreakStudios\DoctrineQueryDocument\Operators;

	use DaybreakStudios\DoctrineQueryDocument\Exception\InvalidFieldValueException;
	use DaybreakStudios\DoctrineQueryDocument\QueryDocumentInterface;
	use Doctrine\ORM\Query\Expr\Composite;

	class GreaterThanEqualOperator extends AbstractOperator {
		/**
		 * GreaterThanEqualOperator constructor.
		 */
		public function __construct() {
			parent::__construct('gte');
		}

		/**
		 * {@inheritdoc}
		 */
		protected function validate(string $field, $value): void {
			if (is_numeric($value))
				return;

			throw new InvalidFieldValueException($field, 'number', $this->getKey());
		}

		/**
		 * {@inheritdoc}
		 */
		protected function doProcess(
			QueryDocumentInterface $document,
			string $field,
			$value,
			Composite $parent
		): void {
			$document->expr()->gte($parent, $field, $value);
		}
	}