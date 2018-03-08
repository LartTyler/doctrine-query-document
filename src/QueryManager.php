<?php
	namespace DaybreakStudios\DoctrineQueryDocument;

	use DaybreakStudios\DoctrineQueryDocument\Operators;
	use Doctrine\Common\Persistence\ObjectManager;
	use Doctrine\ORM\QueryBuilder;

	class QueryManager implements QueryManagerInterface {
		private const BUILTIN_OPERATORS = [
			Operators\GreaterThanEqualOperator::class,
			Operators\GreaterThanOperator::class,
			Operators\InOperator::class,
			Operators\LessThanEqualOperator::class,
			Operators\LessThanOperator::class,
			Operators\LikeOperator::class,
			Operators\NotEqualOperator::class,
			Operators\NotInOperator::class,
			Operators\NotLikeOperator::class,
			Operators\OrOperator::class,
		];

		/**
		 * @var ObjectManager
		 */
		protected $objectManager;

		/**
		 * @var OperatorInterface[]
		 */
		protected $operators;

		/**
		 * QueryManager constructor.
		 *
		 * @param ObjectManager $objectManager
		 * @param array         $operators
		 * @param bool          $useBuiltin
		 */
		public function __construct(ObjectManager $objectManager, array $operators = [], bool $useBuiltin = true) {
			$this->objectManager = $objectManager;

			if ($operators)
				$this->setOperators($operators);

			if ($useBuiltin) {
				foreach (self::BUILTIN_OPERATORS as $class)
					$this->setOperator(new $class());
			}
		}

		/**
		 * {@inheritdoc}
		 */
		public function getOperators(): array {
			return $this->operators;
		}

		/**
		 * {@inheritdoc}
		 */
		public function getOperator(string $key): ?OperatorInterface {
			if (strpos($key, '$') === 0)
				$key = substr($key, 1);

			if (isset($this->operators[$key]))
				return $this->operators[$key];

			return null;
		}

		/**
		 * {@inheritdoc}
		 */
		public function setOperators(array $operators) {
			$this->operators = [];

			foreach ($operators as $operator)
				$this->setOperator($operator);

			return $this;
		}

		/**
		 * {@inheritdoc}
		 */
		public function setOperator(OperatorInterface $operator) {
			$this->operators[$operator->getKey()] = $operator;

			return $this;
		}

		/**
		 * {@inheritdoc}
		 */
		public function removeOperator(string $key) {
			if (strpos($key, '$') === 0)
				$key = substr($key, 1);

			unset($this->operators[$key]);

			return $this;
		}

		/**
		 * {@inheritdoc}
		 */
		public function create(QueryBuilder $qb, array $query): QueryDocumentInterface {
			return new QueryDocument($this, $this->objectManager, $qb);
		}

		/**
		 * @param QueryBuilder $qb
		 * @param array        $query
		 */
		public function apply(QueryBuilder $qb, array $query): void {
			$this->create($qb, $query)->process($query);
		}
	}