<?php
	namespace DaybreakStudios\DoctrineQueryDocument;

	use Doctrine\ORM\Query\Expr\Composite;

	interface OperatorInterface {
		/**
		 * @return string
		 */
		public function getKey(): string;

		/**
		 * @param QueryDocumentInterface $document
		 * @param string                 $field
		 * @param mixed                  $value
		 * @param Composite              $parent
		 *
		 * @return void
		 */
		public function process(QueryDocumentInterface $document, string $field, $value, Composite $parent): void;
	}