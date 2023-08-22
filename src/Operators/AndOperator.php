<?php
	namespace DaybreakStudios\DoctrineQueryDocument\Operators;

	use DaybreakStudios\DoctrineQueryDocument\Exception\InvalidFieldValueException;
	use DaybreakStudios\DoctrineQueryDocument\QueryDocumentInterface;
	use Doctrine\ORM\Query\Expr\Andx;
	use Doctrine\ORM\Query\Expr\Composite;

	class AndOperator extends AbstractOperator {
		public function __construct() {
			parent::__construct('and');
		}

		protected function validate(string $field, mixed $value): void {
			if (is_array($value))
				return;

			throw new InvalidFieldValueException($field, 'array', $this->getKey());
		}

		protected function doProcess(
			QueryDocumentInterface $document,
			object|string $field,
			mixed $value,
			Composite $parent,
		): void {
			$andX = new Andx();

			foreach ($value as $item)
				$document->process($item, $andX);

			$parent->add($andX);
		}
	}
