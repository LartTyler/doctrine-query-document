<?php
	namespace DaybreakStudios\DoctrineQueryDocument\Operators;

	use DaybreakStudios\DoctrineQueryDocument\Exception\InvalidFieldValueException;
	use DaybreakStudios\DoctrineQueryDocument\QueryDocumentInterface;
	use Doctrine\ORM\Query\Expr\Composite;

	class LikeOperator extends AbstractOperator {
		/**
		 * LikeOperator constructor.
		 */
		public function __construct() {
			parent::__construct('like');
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
			$document->expr()->like($parent, $field, $value);
		}
	}
