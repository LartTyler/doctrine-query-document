<?php
	namespace DaybreakStudios\DoctrineQueryDocument;

	use DaybreakStudios\DoctrineQueryDocument\Exception\DocumentAlreadyAppliedException;
	use DaybreakStudios\DoctrineQueryDocument\Exception\UnknownOperatorException;
	use Doctrine\ORM\Query\Expr\Andx;
	use Doctrine\ORM\Query\Expr\Composite;
	use Doctrine\ORM\QueryBuilder;
	use Doctrine\Persistence\ObjectManager;

	class QueryDocument implements QueryDocumentInterface {
		protected ResolverInterface $resolver;
		protected Composite $rootComposite;
		protected Expr $expr;
		protected int $processDepth = 0;
		protected bool $applied = false;

		public function __construct(
			protected QueryManagerInterface $queryManager,
			protected ObjectManager $objectManager,
			protected QueryBuilder $queryBuilder,
		) {
			$this->resolver = new Resolver($objectManager, $queryBuilder);
			$this->rootComposite = new Andx();
			$this->expr = new Expr($queryBuilder, $this->resolver, $this->rootComposite);
		}

		public function getQueryManager(): QueryManagerInterface {
			return $this->queryManager;
		}

		public function getResolver(): ResolverInterface {
			return $this->resolver;
		}

		public function expr(): Expr {
			return $this->expr;
		}

		public function isApplied(): bool {
			return $this->applied;
		}

		public function process(array $query, Composite $parent = null): void {
			if ($this->isApplied())
				throw new DocumentAlreadyAppliedException();

			++$this->processDepth;

			$parent = $parent ?? $this->rootComposite;

			foreach ($query as $key => $value) {
				if (str_starts_with($key, '$'))
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

		protected function invokeOperator(Composite $parent, string $operatorKey, string $field, mixed $value): void {
			if (!str_starts_with($operatorKey, '$'))
				throw new \InvalidArgumentException('Invalid key in filter document for ' . $operatorKey);

			$operator = $this->getQueryManager()->getOperator($operatorKey);

			if (!$operator)
				throw new UnknownOperatorException($operatorKey);

			$operator->process($this, $field, $value, $parent);
		}
	}
