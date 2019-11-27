<?php
	namespace DaybreakStudios\DoctrineQueryDocument\Operators;

	use DaybreakStudios\DoctrineQueryDocument\Exception\InvalidFieldValueException;
	use DaybreakStudios\DoctrineQueryDocument\QueryDocumentInterface;
	use Doctrine\ORM\Query\Expr\Composite;

	class ExistsOperator extends AbstractOperator {
		/**
		 * ExistsOperator constructor.
		 */
		public function __construct() {
			parent::__construct('exists');
		}

		/**
		 * {@inheritdoc}
		 */
		protected function validate(string $field, $value): void {
			if (is_bool($value))
				return;

			throw new InvalidFieldValueException($field, 'boolean', $this->getKey());
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
			if ($value)
				$document->expr()->neq($parent, $field, null);
			else
				$document->expr()->eq($parent, $field, null);
		}
	}
