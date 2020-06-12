<?php
	namespace DaybreakStudios\DoctrineQueryDocument\Operators;

	use DaybreakStudios\DoctrineQueryDocument\Exception\InvalidFieldValueException;
	use DaybreakStudios\DoctrineQueryDocument\QueryDocumentInterface;
	use Doctrine\ORM\Query\Expr\Composite;

	class NotMemberOfOperator extends AbstractOperator {
		/**
		 * NotMemberOfOperator constructor.
		 */
		public function __construct() {
			parent::__construct('notMemberOf');
		}

		/**
		 * {@inheritdoc}
		 */
		protected function validate(string $field, $value): void {
			if (is_int($value))
				return;

			throw new InvalidFieldValueException($field, 'integer', $this->getKey());
		}

		/**
		 * {@inheritdoc}
		 */
		protected function doProcess(QueryDocumentInterface $document, $field, $value, Composite $parent): void {
			$document->expr()->notMemberOf($parent, $field, $value);
		}
	}