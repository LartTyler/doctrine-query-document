<?php
	namespace DaybreakStudios\DoctrineQueryDocument\Projection;

	class Projection {
		/**
		 * @var bool[]
		 */
		protected $nodes;

		/**
		 * @var bool
		 */
		protected $default;

		/**
		 * @var ProjectionPathCache
		 */
		protected $cache;

		/**
		 * Projection constructor.
		 *
		 * @param bool[] $nodes
		 */
		protected function __construct(array $nodes) {
			$this->nodes = $nodes;
			$this->cache = new ProjectionPathCache();

			if (count($nodes) === 0)
				$this->default = true;
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
		 * Queries the projection for the given path, returning an integer representation of the {@see AllowResult}
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

			// For projections with no nodes, all paths are allowed
			if (!$current)
				return true;

			$parts = explode('.', $path);
			$result = AllowResult::from($this->isAllowedByDefault(), false);

			foreach ($parts as $part) {
				if (!isset($current[$part]))
					break;

				$value = $current[$part];

				if (!is_array($value)) {
					$result = AllowResult::from($value, true);
					break;
				}

				$current = $value;
			}

			return $this->cache->set($path, $result);
		}

		public function isAllowed(string $path, bool $useCache = true): bool {
			return AllowResult::isAllow($this->query($path, $useCache));
		}

		public function isAllowedExplicitly(string $path, bool $useCache = true): bool {
			return AllowResult::isExplicitAllow($this->query($path, $useCache));
		}

		public function isDenied(string $path, bool $useCache = true): bool {
			return AllowResult::isDeny($this->query($path, $useCache));
		}

		public function isDeniedExplicitly(string $path, bool $useCache = true): bool {
			return AllowResult::isExplicitDeny($this->query($path, $useCache));
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

		/**
		 * @param array $fields
		 *
		 * @return static
		 */
		public static function fromFields(array $fields) {
			return new static(static::toNodes($fields));
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
