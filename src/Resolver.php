<?php
	namespace DaybreakStudios\DoctrineQueryDocument;

	use DaybreakStudios\DoctrineQueryDocument\Exception\UnknownFieldException;
	use Doctrine\Common\Persistence\ObjectManager;
	use Doctrine\DBAL\Types\Type;
	use Doctrine\ORM\Mapping\ClassMetadata;
	use Doctrine\ORM\QueryBuilder;

	class Resolver implements ResolverInterface {
		use MappedFieldsTrait;

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
		public function resolve(string $field): string {
			if (isset($this->resolveCache[$field]))
				return $this->resolveCache[$field];

			$next = $node = LinkedList::fromArray(explode('.', $field));

			$metadata = $this->rootMetadata;
			$alias = $this->rootAlias;

			do {
				$node = $next;
				$part = $node->getValue();

				if ($mapped = $this->getMappedField($metadata->getName(), $part)) {
					$next = $node->getNext();

					$mappedParts = explode('.', $mapped);
					$node = $tail = new LinkedList(array_shift($mappedParts));

					foreach ($mappedParts as $mappedPart)
						$tail->setNext($tail = new LinkedList($mappedPart));

					$tail->setNext($next);
					$part = $node->getValue();
				}

				if ($metadata->getTypeOfField($part) === Type::JSON) {
					$items = [];

					if ($next = $node->getNext()) {
						do {
							$items[] = $next->getValue();
						} while ($next = $next->getNext());
					}

					$jsonKey = implode('.', $items);

					return sprintf("JSON_UNQUOTE(JSON_EXTRACT(%s.%s, '\$.%s'))", $alias, $part, $jsonKey);
				} else if ($metadata->hasField($part))
					break;
				else if (!$metadata->hasAssociation($part))
					throw new UnknownFieldException($field);

				$metadata = $this->manager->getClassMetadata($metadata->getAssociationTargetClass($part));
				$alias = $this->getJoinAlias($alias, $part);
			} while ($next = $node->getNext());

			$actualField = $node->getValue();

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