<?php
	namespace DaybreakStudios\DoctrineQueryDocument\Projection;

	class Projection implements ProjectionInterface {
		/**
		 * @var bool[]
		 */
		protected array $nodes;

		/**
		 * @var bool
		 */
		protected bool $default;

		/**
		 * @var ProjectionPathCache
		 */
		protected ProjectionPathCache $cache;

		/**
		 * Projection constructor.
		 *
		 * @param array     $nodes
		 * @param bool|null $default
		 */
		public function __construct(array $nodes, bool $default = null) {
			$this->nodes = $nodes;
			$this->cache = new ProjectionPathCache();

			if ($default !== null)
				$this->default = $default;
			else if (count($nodes) === 0)
				$this->default = true; // For projections with no nodes, all paths are allowed
			else {
				// If an element is not matched, the default behavior is the opposite of the value of the first element.
				// For example, for an include projection, any element not found in $nodes should be rejected (the
				// opposite of the value of the include projections, whose values are all true).

				$current = reset($nodes);

				while (is_array($current))
					$current = reset($current);

				$this->default = !(bool)$current;
			}
		}

		/**
		 * @return bool[]
		 */
		public function getNodes(): array {
			return $this->nodes;
		}

		/**
		 * @return bool
		 */
		public function isAllowedByDefault(): bool {
			return $this->default;
		}

		/**
		 * Queries the projection for the given path, returning an integer representation of the {@see QueryResult}
		 * for the query.
		 *
		 * @param string $path
		 * @param bool   $useCache if `false`, skip checking for a cached result; the new result will be written to
		 *                         the cache
		 *
		 * @return int
		 */
		public function query(string $path, bool $useCache = true): int {
			if ($useCache && $this->cache->has($path))
				return $this->cache->get($path);

			$current = $this->getNodes();

			if (!$current)
				return $this->getDefaultResult();

			$result = QueryResult::empty();
			$parts = explode('.', $path);

			foreach ($parts as $part) {
				if (!isset($current[$part])) {
					if (null !== $allValue = $this->getMatchAllValue($current))
						$result = QueryResult::from($allValue, true);
					else
						$result = $this->getDefaultResult();

					break;
				}

				$current = $current[$part];

				if (!is_array($current)) {
					$result = QueryResult::from($current, true);
					break;
				}
			}

			// If the loop ended with an empty result, the query was for an ancestor of a path contained in the list if
			// $current is still an array. If it isn't an array, then we should fall back on the default match behavior
			// for the projection.
			if (QueryResult::isEmpty($result)) {
				if (is_array($current))
					$result = QueryResult::allow();
				else
					$result = $this->getDefaultResult();
			}

			return $this->cache->set($path, $result);
		}

		public function isAllowed(string $path, bool $useCache = true): bool {
			return QueryResult::isAllow($this->query($path, $useCache));
		}

		public function isAllowedExplicitly(string $path, bool $useCache = true): bool {
			return QueryResult::isExplicitAllow($this->query($path, $useCache));
		}

		public function isDenied(string $path, bool $useCache = true): bool {
			return QueryResult::isDeny($this->query($path, $useCache));
		}

		public function isDeniedExplicitly(string $path, bool $useCache = true): bool {
			return QueryResult::isExplicitDeny($this->query($path, $useCache));
		}

		/**
		 * @param array       $data
		 * @param string|null $prefix
		 *
		 * @return array
		 */
		public function filter(array $data, string $prefix = null): array {
			$output = [];

			foreach ($data as $key => $value) {
				$path = ($prefix ? $prefix . '.' : '') . $key;

				if (!$this->isAllowed($path))
					continue;

				if (is_array($value) && count($value) > 0) {
					if (isset($value[0])) {
						if (is_array($value[0])) {
							foreach ($value as $index => $item)
								$value[$index] = $this->filter($item, $path);
						}
					} else
						$value = $this->filter($value, $path);
				}

				$output[$key] = $value;
			}

			return $output;
		}

		protected function getDefaultResult(): int {
			return QueryResult::from($this->isAllowedByDefault(), false);
		}

		protected function getMatchAllValue(array $nodes): ?bool {
			if (null !== $value = $nodes[static::MATCH_ALL_SYMBOL] ?? null)
				return (bool)$value;

			return null;
		}

		/**
		 * @param array     $fields
		 * @param bool|null $default
		 *
		 * @return static
		 */
		public static function fromFields(array $fields, bool $default = null): static {
			return new static(static::toNodes($fields), $default);
		}

		/**
		 * @param array $fields
		 *
		 * @return array
		 */
		protected static function toNodes(array $fields): array {
			$nodes = [];

			foreach ($fields as $field => $value) {
				$current = &$nodes;
				$parts = explode('.', $field);

				$lastKey = array_pop($parts);

				foreach ($parts as $part) {
					if (!isset($current[$part]) || !is_array($current[$part]))
						$current[$part] = [];

					$current = &$current[$part];
				}

				$current[$lastKey] = $value;
			}

			return $nodes;
		}
	}
