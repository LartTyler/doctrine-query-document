<?php
	namespace DaybreakStudios\DoctrineQueryDocument\Operators;

	use DaybreakStudios\DoctrineQueryDocument\Exception\InvalidFieldValueException;
	use DaybreakStudios\DoctrineQueryDocument\QueryDocumentInterface;
	use Doctrine\ORM\Query\Expr\Composite;

	class LikeOperator extends AbstractOperator {
		public function __construct() {
			parent::__construct('like');
		}

		protected function validate(string $field, $value): void {
			if (is_string($value))
				return;

			throw new InvalidFieldValueException($field, 'string', $this->getKey());
		}

		protected function doProcess(
			QueryDocumentInterface $document,
			string $field,
			$value,
			Composite $parent
		): void {

		}
	}