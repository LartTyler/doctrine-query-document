<?php
	namespace DaybreakStudios\DoctrineQueryDocument;

	use Doctrine\ORM\QueryBuilder;

	interface QueryManagerInterface {
		/**
		 * @return OperatorInterface[]
		 */
		public function getOperators(): array;

		/**
		 * @param string $key
		 *
		 * @return OperatorInterface|null
		 */
		public function getOperator(string $key): ?OperatorInterface;

		/**
		 * @param OperatorInterface[] $operators
		 *
		 * @return $this
		 */
		public function setOperators(array $operators);

		/**
		 * @param OperatorInterface $operator
		 *
		 * @return $this
		 */
		public function setOperator(OperatorInterface $operator);

		/**
		 * @param string $key
		 *
		 * @return $this
		 */
		public function removeOperator(string $key);

		/**
		 * @param QueryBuilder $qb
		 *
		 * @return QueryDocumentInterface
		 */
		public function create(QueryBuilder $qb): QueryDocumentInterface;

		/**
		 * @param QueryBuilder $qb
		 * @param array        $query
		 *
		 * @return void
		 */
		public function apply(QueryBuilder $qb, array $query): void;
	}