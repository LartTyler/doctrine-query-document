<?php
	namespace DaybreakStudios\DoctrineQueryDocument\Operators;

	use DaybreakStudios\DoctrineQueryDocument\Exception\InvalidFieldValueException;
	use DaybreakStudios\DoctrineQueryDocument\QueryDocumentInterface;
	use Doctrine\ORM\Query\Expr\Composite;

	class BetweenOperator extends AbstractOperator {
		public function __construct() {
			parent::__construct('between');
		}

		protected function validate(string $field, mixed $value): void {
			if (is_array($value) && sizeof($value) === 2)
				return;

			throw new InvalidFieldValueException($field, 'array with two elements', $this->getKey());
		}

		protected function doProcess(
			QueryDocumentInterface $document,
			object|string $field,
			mixed $value,
			Composite $parent,
		): void {
			$value = array_values($value);
			$document->expr()->between($parent, $field, $value[0], $value[1]);
		}
	}
