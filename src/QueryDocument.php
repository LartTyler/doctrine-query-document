<?php
	namespace DaybreakStudios\DoctrineQueryDocument;

	use DaybreakStudios\DoctrineQueryDocument\Exception\DocumentAlreadyAppliedException;
	use DaybreakStudios\DoctrineQueryDocument\Exception\UnknownOperatorException;
	use Doctrine\ORM\Query\Expr\Andx;
	use Doctrine\ORM\Query\Expr\Composite;
	use Doctrine\ORM\QueryBuilder;
	use Doctrine\Persistence\ObjectManager;

	class QueryDocument implements QueryDocumentInterface {
		/**
		 * @var QueryManagerInterface
		 */
		protected $queryManager;

		/**
		 * @var ObjectManager
		 */
		protected $objectManager;

		/**
		 * @var QueryBuilder
		 */
		protected $queryBuilder;

		/**
		 * @var ResolverInterface
		 */
		protected $resolver;

		/**
		 * @var Composite
		 */
		protected $rootComposite;

		/**
		 * @var Expr
		 */
		protected $expr;

		/**
		 * @var int
		 */
		protected $processDepth = 0;

		/**
		 * @var bool
		 */
		protected $applied = false;

		/**
		 * QueryDocument constructor.
		 *
		 * @param QueryManagerInterface $queryManager
		 * @param ObjectManager         $objectManager
		 * @param QueryBuilder          $queryBuilder
		 */
		public function __construct(
			QueryManagerInterface $queryManager,
			ObjectManager $objectManager,
			QueryBuilder $queryBuilder
		) {
			$this->queryManager = $queryManager;
			$this->objectManager = $objectManager;
			$this->queryBuilder = $queryBuilder;

			$this->resolver = new Resolver($objectManager, $queryBuilder);
			$this->rootComposite = new Andx();
			$this->expr = new Expr($queryBuilder, $this->resolver, $this->rootComposite);
		}

		/**
		 * {@inheritdoc}
		 */
		public function getQueryManager(): QueryManagerInterface {
			return $this->queryManager;
		}

		/**
		 * {@inheritdoc}
		 */
		public function getResolver(): ResolverInterface {
			return $this->resolver;
		}

		/**
		 * {@inheritdoc}
		 */
		public function expr(): Expr {
			return $this->expr;
		}

		/**
		 * {@inheritdoc}
		 */
		public function isApplied(): bool {
			return $this->applied;
		}

		/**
		 * {@inheritdoc}
		 */
		public function process(array $query, Composite $parent = null): void {
			if ($this->isApplied())
				throw new DocumentAlreadyAppliedException();

			++$this->processDepth;

			$parent = $parent ?? $this->rootComposite;

			foreach ($query as $key => $value) {
				if (strpos($key, '$') === 0)
					$this->invokeOperator($parent, $key, $key, $value);
				else {
					if (is_array($value)) {
						foreach ($value as $itemKey => $item)
							$this->invokeOperator($parent, $itemKey, $key, $item);
					} else
						$this->expr()->eq($parent, $key, $value);
				}
			}

			if (--$this->processDepth === 0)
				$this->applied = true;
		}

		/**
		 * @param Composite $parent
		 * @param string    $operatorKey
		 * @param string    $field
		 * @param mixed     $value
		 *
		 * @return void
		 */
		protected function invokeOperator(Composite $parent, string $operatorKey, string $field, $value): void {
			if (strpos($operatorKey, '$') !== 0)
				throw new \InvalidArgumentException('Invalid key in filter document for ' . $operatorKey);

			$operator = $this->getQueryManager()->getOperator($operatorKey);

			if (!$operator)
				throw new UnknownOperatorException($operatorKey);

			$operator->process($this, $field, $value, $parent);
		}
	}
