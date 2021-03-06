<?php
	namespace DaybreakStudios\DoctrineQueryDocument;

	use Doctrine\ORM\Query\Expr\Andx;
	use Doctrine\ORM\Query\Expr\Comparison;
	use Doctrine\ORM\Query\Expr\Composite;
	use Doctrine\ORM\Query\Expr\Func;
	use Doctrine\ORM\Query\Expr\Orx;
	use Doctrine\ORM\QueryBuilder;

	class Expr {
		/**
		 * @var QueryBuilder
		 */
		protected $qb;

		/**
		 * @var ResolverInterface
		 */
		protected $resolver;

		/**
		 * @var Composite
		 */
		protected $rootComposite;

		/**
		 * @var int
		 */
		protected $paramIndex = 0;

		/**
		 * Expr constructor.
		 *
		 * @param QueryBuilder      $qb
		 * @param ResolverInterface $resolver
		 * @param Composite|null    $rootComposite
		 */
		public function __construct(QueryBuilder $qb, ResolverInterface $resolver, ?Composite $rootComposite = null) {
			$this->qb = $qb;
			$this->resolver = $resolver;
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
						]
					);

				$qb->where($this->rootComposite);
			}
		}

		/**
		 * @param Composite|null $node
		 * @param object|string  $x
		 * @param mixed          $y
		 *
		 * @return void
		 */
		public function eq(?Composite $node, $x, $y): void {
			if ($y === null) {
				if (is_string($x))
					$x = $this->resolver->resolve($x);

				($node ?? $this->rootComposite)->add($x . ' IS NULL');
			} else
				$this->addComparison($node, $x, Comparison::EQ, $y);
		}

		/**
		 * @param Composite|null $node
		 * @param object|string  $x
		 * @param mixed          $y
		 *
		 * @return void
		 */
		public function neq(?Composite $node, $x, $y): void {
			if ($y === null) {
				if (is_string($x))
					$x = $this->resolver->resolve($x);

				($node ?? $this->rootComposite)->add($x . ' IS NOT NULL');
			} else
				$this->addComparison($node, $x, Comparison::NEQ, $y);
		}

		/**
		 * @param Composite|null $node
		 * @param object|string  $x
		 * @param mixed          $y
		 *
		 * @return void
		 */
		public function lt(?Composite $node, $x, $y): void {
			$this->addComparison($node, $x, Comparison::LT, $y);
		}

		/**
		 * @param Composite|null $node
		 * @param object|string  $x
		 * @param mixed          $y
		 *
		 * @return void
		 */
		public function lte(?Composite $node, $x, $y): void {
			$this->addComparison($node, $x, Comparison::LTE, $y);
		}

		/**
		 * @param Composite|null $node
		 * @param object|string  $x
		 * @param mixed          $y
		 *
		 * @return void
		 */
		public function gt(?Composite $node, $x, $y): void {
			$this->addComparison($node, $x, Comparison::GT, $y);
		}

		/**
		 * @param Composite|null $node
		 * @param object|string  $x
		 * @param mixed          $y
		 *
		 * @return void
		 */
		public function gte(?Composite $node, $x, $y): void {
			$this->addComparison($node, $x, Comparison::GTE, $y);
		}

		/**
		 * @param Composite|null $node
		 * @param object|string  $x
		 * @param mixed          $y
		 *
		 * @return void
		 */
		public function like(?Composite $node, $x, $y): void {
			$this->addComparison($node, $x, 'LIKE', $y);
		}

		/**
		 * @param Composite|null $node
		 * @param object|string  $x
		 * @param mixed          $y
		 *
		 * @return void
		 */
		public function notLike(?Composite $node, $x, $y): void {
			$this->addComparison($node, $x, 'NOT LIKE', $y);
		}

		/**
		 * @param Composite|null $node
		 * @param string         $x
		 * @param array          $y
		 *
		 * @return void
		 */
		public function in(?Composite $node, string $x, array $y): void {
			$args = [];

			foreach ($y as $item) {
				$args[] = '?' . $this->paramIndex;

				$this->addParameter($item);
			}

			($node ?? $this->rootComposite)->add(new Func($this->resolver->resolve($x) . ' IN', $args));
		}

		/**
		 * @param Composite|null $node
		 * @param string         $x
		 * @param array          $y
		 *
		 * @return void
		 */
		public function notIn(?Composite $node, string $x, array $y): void {
			$args = [];

			foreach ($y as $item) {
				$args[] = '?' . $this->paramIndex;

				$this->addParameter($item);
			}

			($node ?? $this->rootComposite)->add(new Func($this->resolver->resolve($x) . ' NOT IN', $args));
		}

		/**
		 * @param Composite|null $node
		 * @param array          $items
		 *
		 * @return void
		 */
		public function andX(?Composite $node, ...$items) {
			($node ?? $this->rootComposite)->add(new Andx($items));
		}

		/**
		 * @param Composite|null $node
		 * @param array          $args
		 *
		 * @return void
		 */
		public function orX(?Composite $node, ...$args): void {
			($node ?? $this->rootComposite)->add(new Orx($args));
		}

		/**
		 * @param Composite|null   $node
		 * @param string           $x
		 * @param int|float|string $min
		 * @param int|float|string $max
		 *
		 * @return void
		 */
		public function between(?Composite $node, string $x, $min, $max): void {
			$minParam = '?' . $this->paramIndex;
			$this->addParameter($min);

			$maxParam = '?' . $this->paramIndex;
			$this->addParameter($max);

			$node->add(sprintf('%s BETWEEN %s AND %s', $this->resolver->resolve($x), $minParam, $maxParam));
		}

		/**
		 * @param Composite|null $node
		 * @param object|string  $x
		 * @param string         $infix
		 * @param mixed          $y
		 *
		 * @return void
		 */
		public function addComparison(?Composite $node, $x, string $infix, $y): void {
			if (is_string($x))
				$x = $this->resolver->resolve($x);

			$comparison = new Comparison($x, $infix, $this->getParamKey());

			$this->addParameterExpression($node, $comparison, $y);
		}

		/**
		 * @return string
		 */
		public function getParamKey(): string {
			return '?' . $this->paramIndex;
		}

		/**
		 * @param Composite|null $node
		 * @param mixed          $expr
		 * @param mixed          $value
		 *
		 * @return void
		 */
		public function addParameterExpression(?Composite $node, $expr, $value): void {
			($node ?? $this->rootComposite)->add($expr);

			$this->addParameter($value);
		}

		/**
		 * @param mixed $value
		 *
		 * @return string
		 */
		public function addParameter($value): string {
			$key = $this->getParamKey();
			$this->qb->setParameter($this->paramIndex++, $value);

			return $key;
		}
	}
