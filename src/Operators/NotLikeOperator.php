<?php
	namespace DaybreakStudios\DoctrineQueryDocument\Operators;

	use DaybreakStudios\DoctrineQueryDocument\Exception\InvalidFieldValueException;
	use DaybreakStudios\DoctrineQueryDocument\QueryDocumentInterface;
	use Doctrine\ORM\Query\Expr\Composite;

	class NotLikeOperator extends AbstractOperator {
		/**
		 * NotLikeOperator constructor.
		 */
		public function __construct() {
			parent::__construct('nlike');
		}

		/**
		 * {@inheritdoc}
		 */
		protected function validate(string $field, $value): void {
			if (is_string($value))
				return;

			throw new InvalidFieldValueException($field, 'string', $this->getKey());
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
			$document->expr()->notLike($parent, $field, $value);
		}
	}
