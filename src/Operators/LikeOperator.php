<?php
	namespace DaybreakStudios\DoctrineQueryDocument\Operators;

	use DaybreakStudios\DoctrineQueryDocument\Exception\InvalidFieldValueException;
	use DaybreakStudios\DoctrineQueryDocument\QueryDocumentInterface;
	use Doctrine\ORM\Query\Expr\Composite;

	class LikeOperator extends AbstractOperator {
		public function __construct() {
			parent::__construct('like');
		}

		protected function validate(string $field, mixed $value): void {
			if (is_string($value))
				return;

			throw new InvalidFieldValueException($field, 'string', $this->getKey());
		}

		protected function doProcess(
			QueryDocumentInterface $document,
			object|string $field,
			mixed $value,
			Composite $parent,
		): void {
			$document->expr()->like($parent, $field, $value);
		}
	}
