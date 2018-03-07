<?php
	namespace DaybreakStudios\DoctrineQueryDocument\Operators;

	use DaybreakStudios\DoctrineQueryDocument\Exception\InvalidFieldValueException;
	use DaybreakStudios\DoctrineQueryDocument\QueryDocumentInterface;
	use Doctrine\ORM\Query\Expr\Composite;

	class GreaterThanOperator extends AbstractOperator {
		/**
		 * GreaterThanOperator constructor.
		 */
		public function __construct() {
			parent::__construct('gt');
		}

		/**
		 * {@inheritdoc}
		 */
		protected function validate(string $field, $value): void {
			if (is_numeric($value))
				return;

			throw new InvalidFieldValueException($field, 'number', $this->getKey());
		}

		protected function doProcess(
			QueryDocumentInterface $document,
			string $field,
			$value,
			Composite $parent
		): void {
			$document->expr()->gt($parent, $field, $value);
		}
	}