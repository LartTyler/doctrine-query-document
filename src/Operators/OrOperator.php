<?php
	namespace DaybreakStudios\DoctrineQueryDocument\Operators;

	use DaybreakStudios\DoctrineQueryDocument\Exception\InvalidFieldValueException;
	use DaybreakStudios\DoctrineQueryDocument\QueryDocumentInterface;
	use Doctrine\ORM\Query\Expr\Andx;
	use Doctrine\ORM\Query\Expr\Composite;
	use Doctrine\ORM\Query\Expr\Orx;

	class OrOperator extends AbstractOperator {
		public function __construct() {
			parent::__construct('or');
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
			$orX = new Orx();

			foreach ($value as $item) {
				$document->process($item, $node = new Andx());

				$orX->add($node);
			}

			$parent->add($orX);
		}
	}
