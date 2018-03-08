<?php
	namespace DaybreakStudios\DoctrineQueryDocument;

	use Doctrine\ORM\Query\Expr\Composite;

	interface QueryDocumentInterface {
		/**
		 * @return QueryManagerInterface
		 */
		public function getQueryManager(): QueryManagerInterface;

		/**
		 * @return ResolverInterface
		 */
		public function getResolver(): ResolverInterface;

		/**
		 * @param array          $query
		 * @param Composite|null $parent
		 *
		 * @return void
		 */
		public function process(array $query, Composite $parent = null): void;

		/**
		 * @return Expr
		 */
		public function expr(): Expr;

		/**
		 * @return bool
		 */
		public function isApplied(): bool;
	}