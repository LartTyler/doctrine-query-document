<?php
	namespace DaybreakStudios\DoctrineQueryDocument;

	use Doctrine\ORM\QueryBuilder;

	interface QueryManagerInterface {
		/**
		 * @param array $query
		 *
		 * @return QueryDocumentInterface
		 */
		public function create(array $query): QueryDocumentInterface;

		/**
		 * @param QueryBuilder $qb
		 * @param array        $query
		 *
		 * @return void
		 */
		public function apply(QueryBuilder $qb, array $query): void;
	}