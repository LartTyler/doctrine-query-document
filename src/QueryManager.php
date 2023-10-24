<?php
	namespace DaybreakStudios\DoctrineQueryDocument;

	use Doctrine\ORM\QueryBuilder;
	use Doctrine\Persistence\ObjectManager;

	class QueryManager implements QueryManagerInterface {
		use MappedFieldsTrait;

		private const BUILTIN_OPERATORS = [
			Operators\GreaterThanEqualOperator::class,
			Operators\GreaterThanOperator::class,
			Operators\InOperator::class,
			Operators\LessThanEqualOperator::class,
			Operators\LessThanOperator::class,
			Operators\LikeOperator::class,
			Operators\EqualOperator::class,
			Operators\NotEqualOperator::class,
			Operators\NotInOperator::class,
			Operators\NotLikeOperator::class,
			Operators\OrOperator::class,
			Operators\AndOperator::class,
			Operators\ExistsOperator::class,
			Operators\SizeOperator::class,
			Operators\ContainsOperator::class,
			Operators\NotContainsOperator::class,
		];

		/**
		 * @var OperatorInterface[]
		 */
		protected array $operators;

		public function __construct(
			protected ObjectManager $objectManager,
			array $operators = [],
			bool $useBuiltin = true,
		) {
			if ($operators)
				$this->setOperators($operators);

			if ($useBuiltin) {
				foreach (self::BUILTIN_OPERATORS as $class)
					$this->setOperator(new $class());
			}
		}

		public function getOperators(): array {
			return $this->operators;
		}

		public function setOperators(array $operators): static {
			$this->operators = [];

			foreach ($operators as $operator)
				$this->setOperator($operator);

			return $this;
		}

		public function getOperator(string $key): ?OperatorInterface {
			if (str_starts_with($key, '$'))
				$key = substr($key, 1);

			if (isset($this->operators[$key]))
				return $this->operators[$key];

			return null;
		}

		public function setOperator(OperatorInterface $operator): static {
			$this->operators[$operator->getKey()] = $operator;
			return $this;
		}

		public function removeOperator(string $key): static {
			if (str_starts_with($key, '$'))
				$key = substr($key, 1);

			unset($this->operators[$key]);

			return $this;
		}

		public function create(QueryBuilder $qb): QueryDocumentInterface {
			$document = new QueryDocument($this, $this->objectManager, $qb);
			$document->getResolver()->setAllMappedFields($this->mappedFields);

			return $document;
		}

		public function apply(QueryBuilder $qb, array $query): void {
			// If the query document is empty, we can just skip processing. Otherwise, we'll end up with an empty WHERE
			// clause inserted into the query builder, which will break things.
			if (count($query) === 0)
				return;

			$this->create($qb)->process($query);
		}
	}
