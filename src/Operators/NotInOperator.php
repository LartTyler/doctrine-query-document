<?php
	namespace DaybreakStudios\DoctrineQueryDocument\Operators;

	use DaybreakStudios\DoctrineQueryDocument\Exception\InvalidFieldValueException;
	use DaybreakStudios\DoctrineQueryDocument\QueryDocumentInterface;
	use Doctrine\ORM\Query\Expr\Composite;

	class NotInOperator extends AbstractOperator {
		/**
		 * NotInOperator constructor.
		 */
		public function __construct() {
			parent::__construct('nin');
		}

		/**
		 * {@inheritdoc}
		 */
		protected function validate(string $field, $value): void {
			if (is_array($value))
				return;

			throw new InvalidFieldValueException($field, 'array', $this->getKey());
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
			$document->expr()->notIn($parent, $field, $value);
		}
	}
