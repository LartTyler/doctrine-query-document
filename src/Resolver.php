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
		 * @var string[][]
		 */
		protected $mappedFields = [];

		/**
		 * Resolver constructor.
		 *
		 * @param ObjectManager $manager
		 * @param QueryBuilder  $qb
		 * @param string[][]    $mappedFields
		 */
		public function __construct(ObjectManager $manager, QueryBuilder $qb, array $mappedFields = []) {
			$this->manager = $manager;
			$this->qb = $qb;

			$this->rootMetadata = $manager->getClassMetadata($qb->getRootEntities()[0]);
			$this->rootAlias = $qb->getRootAliases()[0];

			foreach ($mappedFields as $class => $fields)
				$this->setMappedFields($class, $fields);
		}

		/**
		 * {@inheritdoc}
		 */
		public function getMappedField(string $class, string $field): ?string {
			return $this->mappedFields[$class][$field] ?? null;
		}

		/**
		 * {@inheritdoc}
		 */
		public function setMappedFields(string $class, array $mappedFields) {
			$this->mappedFields[$class] = [];

			foreach ($mappedFields as $field => $mappedField)
				$this->setMappedField($class, $field, $mappedField);
		}

		/**
		 * {@inheritdoc}
		 */
		public function setMappedField(string $class, string $field, string $target) {
			if (!isset($this->mappedFields[$class]))
				$this->mappedFields[$class] = [];

			$this->mappedFields[$class][$field] = $target;

			return $this;
		}

		/**
		 * {@inheritdoc}
		 */
		public function removeMappedField(string $class, string $field) {
			if (!isset($this->mappedFields[$class]))
				return $this;

			unset($this->mappedFields[$class][$field]);

			return $this;
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
				// If $actualField isn't an association, it's a concrete field, so we can short circuit and return
				// early
				if (!$this->rootMetadata->hasAssociation($actualField))
					return $this->rootAlias . '.' . $actualField;

				// Otherwise, it IS an association, but since Doctrine doesn't let us query associations by their
				// ID directly, we set $parts to the field itself, and $actualField to "id" so we can query against
				// that.
				$parts = [$actualField];
				$actualField = 'id';
			}

			$node = LinkedList::fromArray($parts);

			$metadata = $this->rootMetadata;
			$alias = $this->rootAlias;

			do {
				$part = $node->getValue();

				if ($metadata->getTypeOfField($part) === Type::JSON) {
					$items = [];

					if ($next = $node->getNext()) {
						do {
							$items[] = $next->getValue();
						} while ($next = $next->getNext());
					}

					$items[] = $actualField;

					$jsonKey = implode('.', $items);

					return sprintf("JSON_UNQUOTE(JSON_EXTRACT(%s.%s, '\$.%s'))", $alias, $part, $jsonKey);
				} else if (!$metadata->hasAssociation($part))
					throw new UnknownFieldException($field);

				$metadata = $this->manager->getClassMetadata($metadata->getAssociationTargetClass($part));
				$alias = $this->getJoinAlias($alias, $part);
			} while ($node = $node->getNext());

			if (!$metadata->hasField($actualField))
				throw new UnknownFieldException($field);

			$resolved = $alias . '.' . $actualField;

			return $this->resolveCache[$field] = $resolved;
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