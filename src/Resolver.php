<?php
	namespace DaybreakStudios\DoctrineQueryDocument;

	use DaybreakStudios\DoctrineQueryDocument\Exception\CannotDirectlySearchRelationshipException;
	use DaybreakStudios\DoctrineQueryDocument\Exception\UnknownFieldException;
	use Doctrine\Common\Persistence\ObjectManager;
	use Doctrine\DBAL\Types\Type;
	use Doctrine\ORM\Mapping\ClassMetadata;
	use Doctrine\ORM\QueryBuilder;

	class Resolver implements ResolverInterface {
		/**
		 * @var ObjectManager
		 */
		protected $manager;

		/**
		 * @var QueryBuilder
		 */
		protected $qb;

		/**
		 * @var ClassMetadata
		 */
		protected $rootMetadata;

		/**
		 * @var string
		 */
		protected $rootAlias;

		/**
		 * @var string[]
		 */
		protected $joins = [];

		/**
		 * @var array
		 */
		protected $resolveCache = [];

		/**
		 * Resolver constructor.
		 *
		 * @param ObjectManager $manager
		 * @param QueryBuilder  $qb
		 */
		public function __construct(ObjectManager $manager, QueryBuilder $qb) {
			$this->manager = $manager;
			$this->qb = $qb;

			$this->rootMetadata = $manager->getClassMetadata($qb->getRootEntities()[0]);
			$this->rootAlias = $qb->getRootAliases()[0];
		}

		/**
		 * {@inheritdoc}
		 */
		public function resolve(string $field): string {
			if (isset($this->resolveCache[$field]))
				return $this->resolveCache[$field];

			$parts = explode('.', $field);
			$actualField = array_pop($parts);

			if (!sizeof($parts)) {
				if ($this->rootMetadata->hasAssociation($actualField))
					throw new CannotDirectlySearchRelationshipException($actualField);

				return $this->rootAlias . '.' . $actualField;
			}

			$metadata = $this->rootMetadata;
			$alias = $this->rootAlias;

			foreach ($parts as $i => $part) {
				if ($metadata->getTypeOfField($part) === Type::JSON) {
					if (isset($parts[$i + 1]))
						$items = array_slice($parts, $i + 1);
					else
						$items = [];

					$items[] = $actualField;

					$jsonKey = implode('.', $items);

					return sprintf("JSON_UNQUOTE(JSON_EXTRACT(%s.%s, '\$.%s'))", $alias, $part, $jsonKey);
				} else if (!$metadata->hasAssociation($part))
					throw new UnknownFieldException($field);

				$metadata = $this->manager->getClassMetadata($metadata->getAssociationTargetClass($part));
				$alias = $this->getJoinAlias($alias, $part);
			}

			if (!$metadata->hasField($actualField))
				throw new UnknownFieldException($field);

			return $alias . '.' . $actualField;
		}

		/**
		 * @param string $parentAlias
		 * @param string $parentField
		 *
		 * @return string
		 */
		protected function getJoinAlias(string $parentAlias, string $parentField): string {
			$joinKey = $parentAlias . '.' . $parentField;

			if (isset($this->joins[$joinKey]))
				return $this->joins[$joinKey];

			$alias = 'join_' . sizeof($this->joins);

			$this->qb->leftJoin($joinKey, $alias);

			return $this->joins[$joinKey] = $alias;
		}
	}