<?php
	namespace DaybreakStudios\DoctrineQueryDocument;

	use DaybreakStudios\DoctrineQueryDocument\Exception\UnknownFieldException;
	use Doctrine\DBAL\Types\Types;
	use Doctrine\ORM\Mapping\ClassMetadataInfo;
	use Doctrine\ORM\QueryBuilder;
	use Doctrine\Persistence\Mapping\ClassMetadata;
	use Doctrine\Persistence\ObjectManager;

	class Resolver implements ResolverInterface, DiscriminatorAwareResolverInterface {
		use MappedFieldsTrait;

		protected ClassMetadata $rootMetadata;
		protected string $rootAlias;

		/**
		 * @var string[]
		 */
		protected array $joins = [];

		protected array $resolveCache = [];

		/**
		 * @var ClassMetadata[]
		 */
		protected array $loadedMetadata = [];

		protected ?string $discriminatorResolverFn = null;

		/**
		 * Resolver constructor.
		 *
		 * @param ObjectManager $manager
		 * @param QueryBuilder  $qb
		 * @param string[][]    $mappedFields
		 */
		public function __construct(
			protected ObjectManager $manager,
			protected QueryBuilder $qb,
			array $mappedFields = [],
		) {
			$this->rootMetadata = $manager->getClassMetadata($qb->getRootEntities()[0]);
			$this->rootAlias = $qb->getRootAliases()[0];

			foreach ($mappedFields as $class => $fields)
				$this->setMappedFields($class, $fields);
		}

		public function setDiscriminatorResolverFunction(?string $name): void {
			$this->discriminatorResolverFn = $name;
		}

		public function resolve(string $field, array $context = []): string {
			if (isset($this->resolveCache[$field]))
				return $this->resolveCache[$field];
			else if (!($context[ResolverContext::RESOLVE_ASSOCIATIONS_TO_ID] ?? true)) {
				$key = '@' . $field;

				if (isset($this->resolveCache[$key]))
					return $this->resolveCache[$key];
			}

			$next = $node = LinkedList::fromArray(explode('.', $field));

			$metadata = $this->rootMetadata;
			$alias = $this->rootAlias;

			do {
				$this->loadedMetadata[$alias] = $metadata;

				$node = $next;
				$part = $node->getValue();

				if ($mapped = $this->getMappedField($metadata->getName(), $part)) {
					$node->inject(LinkedList::fromArray(explode('.', $mapped)));

					$part = $node->getValue();
				} else if ($matchData = $this->findReverseMappedNode($metadata->getName(), $node)) {
					/**
					 * @var int        $length
					 * @var LinkedList $matched
					 */
					[$length, $matched] = $matchData;

					$node->splice($matched, $length);

					$part = $node->getValue();
				}

				if ($metadata->getTypeOfField($part) === Types::JSON && $node->getNext()) {
					$jsonKey = implode('.', $node->getNext() ? $node->getNext()->all() : []);

					if (!($context[ResolverContext::RESOLVE_EMBEDDED_JSON_TO_EXTRACT_FUNC] ?? true))
						return implode('.', [$alias, $part, $jsonKey]);

					return sprintf("JSON_UNQUOTE(JSON_EXTRACT(%s.%s, '\$.%s'))", $alias, $part, $jsonKey);
				} else if ($metadata->hasField($part))
					break;
				else if ($this->discriminatorResolverFn && $this->isDiscriminatorField($metadata, $part))
					return sprintf('%s(%s)', $this->discriminatorResolverFn, $alias);
				else if (!$metadata->hasAssociation($part))
					throw new UnknownFieldException($field);

				if (!$node->getNext() && !($context[ResolverContext::RESOLVE_ASSOCIATIONS_TO_ID] ?? true))
					return $alias . '.' . $part;

				$alias = $this->getJoinAlias($alias, $part);

				if (isset($this->loadedMetadata[$alias]))
					$metadata = $this->loadedMetadata[$alias];
				else {
					$metadata = $this->manager->getClassMetadata($metadata->getAssociationTargetClass($part));

					$this->loadedMetadata[$alias] = $metadata;
				}

				// If the last field in the list is an association, we need to make sure that we resolve to the
				// association's ID, instead of the field name of the association (which will error out)
				if (!$node->getNext())
					$node->setNext(new LinkedList($metadata->getIdentifierFieldNames()[0]));
			} while ($next = $node->getNext());

			// If we broke out of our do-while, but there are still items on the stack, we've got an embedded object
			if ($node->getNext()) {
				$actualField = implode('.', $node->all());
			} else
				$actualField = $node->getValue();

			if (!$metadata->hasField($actualField))
				throw new UnknownFieldException($field);

			$resolved = $alias . '.' . $actualField;

			return $this->resolveCache[$field] = $resolved;
		}

		public function isDiscriminatorField(ClassMetadata $metadata, string $name): bool {
			if (!$metadata instanceof ClassMetadataInfo || !$metadata->discriminatorColumn)
				return false;

			return $metadata->discriminatorColumn['fieldName'] === $name;
		}

		public function getMetadata(string $alias): ?ClassMetadata {
			return $this->loadedMetadata[$alias] ?? null;
		}

		protected function getJoinAlias(string $parentAlias, string $parentField): string {
			$joinKey = $parentAlias . '.' . $parentField;

			if (isset($this->joins[$joinKey]))
				return $this->joins[$joinKey];

			$alias = 'join_' . sizeof($this->joins);

			$this->qb->leftJoin($joinKey, $alias);

			return $this->joins[$joinKey] = $alias;
		}

		protected function findReverseMappedNode(string $class, LinkedList $current): ?array {
			if (!$current->getNext() || !$this->hasMappedFields($class))
				return null;

			$joined = $current->getValue();
			$length = 1;

			while ($current = $current->getNext()) {
				++$length;

				$joined .= '.' . $current->getValue();

				if ($matched = $this->getMappedField($class, $joined))
					return [$length, LinkedList::fromArray(explode('.', $matched))];
			}

			return null;
		}
	}
