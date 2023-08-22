<?php
	namespace DaybreakStudios\DoctrineQueryDocument\Operators;

	use DaybreakStudios\DoctrineQueryDocument\Exception\InvalidFieldValueException;
	use DaybreakStudios\DoctrineQueryDocument\QueryDocumentInterface;
	use Doctrine\ORM\Query\Expr\Composite;

	class ExistsOperator extends AbstractOperator {
		public function __construct() {
			parent::__construct('exists');
		}

		protected function validate(string $field, mixed $value): void {
			if (is_bool($value))
				return;

			throw new InvalidFieldValueException($field, 'boolean', $this->getKey());
		}

		protected function doProcess(
			QueryDocumentInterface $document,
			object|string $field,
			mixed $value,
			Composite $parent,
		): void {
			if ($value)
				$document->expr()->neq($parent, $field, null);
			else
				$document->expr()->eq($parent, $field, null);
		}
	}
