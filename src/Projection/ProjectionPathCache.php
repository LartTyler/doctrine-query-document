<?php
	namespace DaybreakStudios\DoctrineQueryDocument\Projection;

	class ProjectionPathCache {
		/**
		 * @var array<string, int>
		 */
		protected $data = [];

		/**
		 * @param string $path
		 * @param int   $value {@see AllowResult}
		 *
		 * @return int
		 */
		public function set(string $path, int $value): int {
			return $this->data[$path] = $value;
		}

		/**
		 * @param string $path
		 *
		 * @return bool
		 */
		public function has(string $path): bool {
			return isset($this->data[$path]);
		}

		/**
		 * {@see AllowResult}
		 *
		 * @param string $path
		 *
		 * @return int
		 */
		public function get(string $path): int {
			if (!$this->has($path)) {
				throw new \InvalidArgumentException($path . ' does not exist in the cache. Use ' . static::class .
					'::has() to check first!');
			}

			return $this->data[$path];
		}
	}
