<?php
	namespace DaybreakStudios\DoctrineQueryDocument\Operators;

	use DaybreakStudios\DoctrineQueryDocument\OperatorInterface;
	use DaybreakStudios\DoctrineQueryDocument\QueryDocumentInterface;
	use Doctrine\ORM\Query\Expr\Composite;

	abstract class AbstractOperator implements OperatorInterface {
		public function __construct(
			protected string $key,
		) {}

		public function getKey(): string {
			return $this->key;
		}

		public function process(
			QueryDocumentInterface $document,
			object|string $field,
			mixed $value,
			Composite $parent,
		): void {
			$this->validate($field, $value);
			$this->doProcess($document, $field, $value, $parent);
		}

		/**
		 * @param string $field
		 * @param mixed  $value
		 *
		 * @return void
		 * @throws \RuntimeException if the key and / or value do not pass validation
		 */
		protected abstract function validate(string $field, mixed $value): void;

		protected abstract function doProcess(
			QueryDocumentInterface $document,
			string|object $field,
			mixed $value,
			Composite $parent,
		): void;
	}
