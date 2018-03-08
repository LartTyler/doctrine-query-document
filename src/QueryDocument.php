<?php
	namespace DaybreakStudios\DoctrineQueryDocument;

	use DaybreakStudios\DoctrineQueryDocument\Exception\UnknownOperatorException;
	use Doctrine\Common\Persistence\ObjectManager;
	use Doctrine\ORM\Query\Expr\Andx;
	use Doctrine\ORM\Query\Expr\Composite;
	use Doctrine\ORM\QueryBuilder;

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
		public function process(array $query, Composite $parent = null): void {
			if ($this->applied)
				return;

			++$this->processDepth;

			$parent = $parent ?? $this->rootComposite;

			foreach ($query as $key => $value) {
				if (strpos($key, '$') === 0) {
					$operator = $this->getQueryManager()->getOperator($key);

					if (!$operator)
						throw new UnknownOperatorException($key);

					$operator->process($this, $key, $value, $parent);
				} else {
					if (is_array($value)) {
						$itemKey = array_keys($value)[0];

						if (strpos($itemKey, '$') !== 0)
							throw new \InvalidArgumentException('You may only pass arrays as search values for ' .
								'operators');

						$operator = $this->getQueryManager()->getOperator($itemKey);

						if (!$operator)
							throw new UnknownOperatorException($key);

						$operator->process($this, $key, array_values($value)[0], $parent);
					} else
						$this->expr()->eq($parent, $key, $value);
				}
			}

			if (--$this->processDepth === 0)
				$this->applied = true;
		}
	}