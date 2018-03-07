<?php
	namespace DaybreakStudios\DoctrineQueryDocument\Operators;

	use DaybreakStudios\DoctrineQueryDocument\Exception\InvalidFieldValueException;
	use DaybreakStudios\DoctrineQueryDocument\QueryDocumentInterface;
	use Doctrine\ORM\Query\Expr\Composite;

	class LessThanOperator extends AbstractOperator {
		/**
		 * LessThanOperator constructor.
		 */
		public function __construct() {
			parent::__construct('lt');
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
			$document->expr()->lt($parent, $field, $value);
		}
	}