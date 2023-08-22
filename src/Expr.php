<?php
	namespace DaybreakStudios\DoctrineQueryDocument;

	use Doctrine\ORM\Query\Expr\Andx;
	use Doctrine\ORM\Query\Expr\Comparison;
	use Doctrine\ORM\Query\Expr\Composite;
	use Doctrine\ORM\Query\Expr\Func;
	use Doctrine\ORM\Query\Expr\Orx;
	use Doctrine\ORM\QueryBuilder;

	class Expr {
		protected Composite $rootComposite;
		protected int $paramIndex = 0;

		public function __construct(
			protected QueryBuilder $qb,
			protected ResolverInterface $resolver,
			?Composite $rootComposite = null,
		) {
			$this->rootComposite = $rootComposite ?? new Andx();

			$where = $this->qb->getDQLPart('where');

			if ($where instanceof Composite)
				$where->add($this->rootComposite);
			else {
				if ($where !== null)
					$this->rootComposite = new Andx(
						[
							$where,
							$this->rootComposite,
						],
					);

				$qb->where($this->rootComposite);
			}
		}

		public function eq(?Composite $node, object|string $x, mixed $y): void {
			if ($y === null) {
				if (is_string($x))
					$x = $this->resolver->resolve($x);

				($node ?? $this->rootComposite)->add($x . ' IS NULL');
			} else
				$this->addComparison($node, $x, Comparison::EQ, $y);
		}

		public function neq(?Composite $node, object|string $x, mixed $y): void {
			if ($y === null) {
				if (is_string($x))
					$x = $this->resolver->resolve($x);

				($node ?? $this->rootComposite)->add($x . ' IS NOT NULL');
			} else
				$this->addComparison($node, $x, Comparison::NEQ, $y);
		}

		public function lt(?Composite $node, object|string $x, mixed $y): void {
			$this->addComparison($node, $x, Comparison::LT, $y);
		}

		public function lte(?Composite $node, object|string $x, mixed $y): void {
			$this->addComparison($node, $x, Comparison::LTE, $y);
		}

		public function gt(?Composite $node, object|string $x, mixed $y): void {
			$this->addComparison($node, $x, Comparison::GT, $y);
		}

		public function gte(?Composite $node, object|string $x, mixed $y): void {
			$this->addComparison($node, $x, Comparison::GTE, $y);
		}

		public function like(?Composite $node, object|string $x, mixed $y): void {
			$this->addComparison($node, $x, 'LIKE', $y);
		}

		public function notLike(?Composite $node, object|string $x, mixed $y): void {
			$this->addComparison($node, $x, 'NOT LIKE', $y);
		}

		public function in(?Composite $node, string $x, array $y): void {
			$args = [];

			foreach ($y as $item) {
				$args[] = '?' . $this->paramIndex;

				$this->addParameter($item);
			}

			($node ?? $this->rootComposite)->add(new Func($this->resolver->resolve($x) . ' IN', $args));
		}

		public function notIn(?Composite $node, string $x, array $y): void {
			$args = [];

			foreach ($y as $item) {
				$args[] = '?' . $this->paramIndex;

				$this->addParameter($item);
			}

			($node ?? $this->rootComposite)->add(new Func($this->resolver->resolve($x) . ' NOT IN', $args));
		}

		public function andX(?Composite $node, ...$items): void {
			($node ?? $this->rootComposite)->add(new Andx($items));
		}

		public function orX(?Composite $node, ...$args): void {
			($node ?? $this->rootComposite)->add(new Orx($args));
		}

		public function between(?Composite $node, string $x, int|float|string $min, int|float|string $max): void {
			$minParam = '?' . $this->paramIndex;
			$this->addParameter($min);

			$maxParam = '?' . $this->paramIndex;
			$this->addParameter($max);

			$node->add(sprintf('%s BETWEEN %s AND %s', $this->resolver->resolve($x), $minParam, $maxParam));
		}

		public function addComparison(?Composite $node, object|string $x, string $infix, mixed $y): void {
			if (is_string($x))
				$x = $this->resolver->resolve($x);

			$comparison = new Comparison($x, $infix, $this->getParamKey());

			$this->addParameterExpression($node, $comparison, $y);
		}

		public function getParamKey(): string {
			return '?' . $this->paramIndex;
		}

		public function addParameterExpression(?Composite $node, mixed $expr, mixed $value): void {
			($node ?? $this->rootComposite)->add($expr);

			$this->addParameter($value);
		}

		public function addParameter(mixed $value): string {
			$key = $this->getParamKey();
			$this->qb->setParameter($this->paramIndex++, $value);

			return $key;
		}
	}
