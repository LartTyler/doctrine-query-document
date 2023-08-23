<?php
	namespace DaybreakStudios\DoctrineQueryDocument\Operators;

	use DaybreakStudios\DoctrineQueryDocument\Exception\InvalidFieldValueException;
	use DaybreakStudios\DoctrineQueryDocument\QueryDocumentInterface;
	use Doctrine\ORM\Query\Expr\Composite;

	class EqualOperator extends AbstractOperator {
		public function __construct() {
			parent::__construct('eq');
		}

		protected function validate(string $field, mixed $value): void {
			if (is_scalar($value) || $value === null)
				return;

			throw new InvalidFieldValueException($field, 'scalar', $this->getKey());
		}

		protected function doProcess(
			QueryDocumentInterface $document,
			object|string $field,
			mixed $value,
			Composite $parent,
		): void {
			$document->expr()->eq($parent, $field, $value);
		}
	}
