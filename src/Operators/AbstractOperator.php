<?php
	namespace DaybreakStudios\DoctrineQueryDocument\Operators;

	use DaybreakStudios\DoctrineQueryDocument\OperatorInterface;
	use DaybreakStudios\DoctrineQueryDocument\QueryDocumentInterface;
	use Doctrine\ORM\Query\Expr\Composite;

	abstract class AbstractOperator implements OperatorInterface {
		/**
		 * @var string
		 */
		protected $key;

		/**
		 * AbstractOperator constructor.
		 *
		 * @param string $key
		 */
		public function __construct(string $key) {
			$this->key = $key;
		}

		/**
		 * {@inheritdoc}
		 */
		public function getKey(): string {
			return $this->key;
		}

		/**
		 * {@inheritdoc}
		 */
		public function process(
			QueryDocumentInterface $document,
			string $field,
			$value,
			Composite $parent
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
		protected abstract function validate(string $field, $value): void;

		/**
		 * @param QueryDocumentInterface $document
		 * @param string                 $field
		 * @param mixed                  $value
		 * @param Composite              $parent
		 *
		 * @return void
		 */
		protected abstract function doProcess(
			QueryDocumentInterface $document,
			string $field,
			$value,
			Composite $parent
		): void;
	}