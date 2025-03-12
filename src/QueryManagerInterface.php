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
		public function setOperators(array $operators): static;

		/**
		 * @param OperatorInterface $operator
		 *
		 * @return $this
		 */
		public function setOperator(OperatorInterface $operator): static;

		/**
		 * @param string $key
		 *
		 * @return $this
		 */
		public function removeOperator(string $key): static;

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

		/**
		 * Returns a DQL function that can be used to query the value of a discriminator for entities using Doctrine's
		 * inheritance feature.
		 *
		 * As Doctrine does not provide such a feature out-of-the-box, a simple implementation is available in
		 * {@see \DaybreakStudios\DoctrineQueryDocument\Doctrine\TypeFunction}. You can also provide your own, just be
		 * sure call {@see QueryManagerInterface::setDiscriminatorResolverFunctionName()} with the name of the function.
		 *
		 * @return string|null
		 */
		public function getDiscriminatorResolverFunctionName(): ?string;

		public function setDiscriminatorResolverFunctionName(?string $name): void;
	}
