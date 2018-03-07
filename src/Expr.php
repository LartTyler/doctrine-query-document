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
		 */
		public function __construct(QueryBuilder $qb, ResolverInterface $resolver) {
			$this->qb = $qb;
			$this->resolver = $resolver;
			$this->rootComposite = new Andx();

			$this->qb->andWhere($this->rootComposite);
		}

		/**
		 * @param Composite|null $node
		 * @param string         $x
		 * @param mixed          $y
		 *
		 * @return void
		 */
		public function eq(?Composite $node, string $x, $y): void {
			if ($y === null)
				($node ?? $this->rootComposite)->add($this->resolver->resolve($x) . ' IS NULL');
			else
				$this->addComparison($node, $x, Comparison::EQ, $y);
		}

		/**
		 * @param Composite|null $node
		 * @param string         $x
		 * @param mixed          $y
		 *
		 * @return void
		 */
		public function neq(?Composite $node, string $x, $y): void {
			if ($y === null)
				($node ?? $this->rootComposite)->add($this->resolver->resolve($x) . ' IS NOT NULL');
			else
				$this->addComparison($node, $x, Comparison::NEQ, $y);
		}

		/**
		 * @param Composite|null $node
		 * @param string         $x
		 * @param mixed          $y
		 *
		 * @return void
		 */
		public function lt(?Composite $node, string $x, $y): void {
			$this->addComparison($node, $x, Comparison::LT, $y);
		}

		/**
		 * @param Composite|null $node
		 * @param string         $x
		 * @param mixed          $y
		 *
		 * @return void
		 */
		public function lte(?Composite $node, string $x, $y): void {
			$this->addComparison($node, $x, Comparison::LTE, $y);
		}

		/**
		 * @param Composite|null $node
		 * @param string         $x
		 * @param mixed          $y
		 *
		 * @return void
		 */
		public function gt(?Composite $node, string $x, $y): void {
			$this->addComparison($node, $x, Comparison::GT, $y);
		}

		/**
		 * @param Composite|null $node
		 * @param string         $x
		 * @param mixed          $y
		 *
		 * @return void
		 */
		public function gte(?Composite $node, string $x, $y): void {
			$this->addComparison($node, $x, Comparison::GTE, $y);
		}

		/**
		 * @param Composite|null $node
		 * @param string         $x
		 * @param mixed          $y
		 *
		 * @return void
		 */
		public function like(?Composite $node, string $x, $y): void {
			$this->addComparison($node, $x, 'LIKE', $y);
		}

		/**
		 * @param Composite|null $node
		 * @param string         $x
		 * @param mixed          $y
		 *
		 * @return void
		 */
		public function notLike(?Composite $node, string $x, $y): void {
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
		 * @param Composite|null $node
		 * @param string         $x
		 * @param string         $infix
		 * @param mixed          $y
		 *
		 * @return void
		 */
		protected function addComparison(?Composite $node, string $x, string $infix, $y): void {
			$comparison = new Comparison($this->resolver->resolve($x), $infix, $this->getParamKey());

			$this->addParameterExpression($node, $comparison, $y);
		}

		/**
		 * @return string
		 */
		protected function getParamKey(): string {
			return '?' . $this->paramIndex;
		}

		/**
		 * @param Composite|null $node
		 * @param mixed          $expr
		 * @param mixed          $value
		 *
		 * @return void
		 */
		protected function addParameterExpression(?Composite $node, $expr, $value): void {
			($node ?? $this->rootComposite)->add($expr);

			$this->addParameter($value);
		}

		/**
		 * @param mixed $value
		 *
		 * @return void
		 */
		protected function addParameter($value): void {
			$this->qb->setParameter($this->paramIndex++, $value);
		}
	}