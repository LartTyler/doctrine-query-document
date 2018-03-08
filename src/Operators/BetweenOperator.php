<?php
	namespace DaybreakStudios\DoctrineQueryDocument\Operators;

	use DaybreakStudios\DoctrineQueryDocument\Exception\InvalidFieldValueException;
	use DaybreakStudios\DoctrineQueryDocument\QueryDocumentInterface;
	use Doctrine\ORM\Query\Expr\Composite;

	class BetweenOperator extends AbstractOperator {
		/**
		 * BetweenOperator constructor.
		 */
		public function __construct() {
			parent::__construct('between');
		}

		/**
		 * {@inheritdoc}
		 */
		protected function validate(string $field, $value): void {
			if (is_array($value) && sizeof($value) === 2)
				return;

			throw new InvalidFieldValueException($field, 'array with two elements', $this->getKey());
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
			$value = array_values($value);

			$document->expr()->between($parent, $field, $value[0], $value[1]);
		}
	}