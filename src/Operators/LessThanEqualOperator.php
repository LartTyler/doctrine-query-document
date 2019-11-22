<?php
	namespace DaybreakStudios\DoctrineQueryDocument\Operators;

	use DaybreakStudios\DoctrineQueryDocument\Exception\InvalidFieldValueException;
	use DaybreakStudios\DoctrineQueryDocument\QueryDocumentInterface;
	use Doctrine\ORM\Query\Expr\Composite;

	class LessThanEqualOperator extends AbstractOperator {
		/**
		 * LessThanEqualOperator constructor.
		 */
		public function __construct() {
			parent::__construct('lte');
		}

		/**
		 * {@inheritdoc}
		 */
		protected function validate(string $field, $value): void {
			if (is_numeric($value) || is_string($value))
				return;

			throw new InvalidFieldValueException($field, 'number or string', $this->getKey());
		}

		/**
		 * {@inheritdoc}
		 */
		protected function doProcess(
			QueryDocumentInterface $document,
			$field,
			$value,
			Composite $parent
		): void {
			$document->expr()->lte($parent, $field, $value);
		}
	}
