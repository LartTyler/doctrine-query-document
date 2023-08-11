<?php
	namespace DaybreakStudios\DoctrineQueryDocument\Projection;

	class PrefixedProjection implements ProjectionInterface {
		/**
		 * @var ProjectionInterface
		 */
		protected $projection;

		/**
		 * @var string
		 */
		protected $prefix;

		public function __construct(ProjectionInterface $projection, string $prefix) {
			$this->projection = $projection;
			$this->prefix = $prefix;
		}

		public function query(string $path, bool $useCache = true): int {
			return $this->projection->query($this->addPrefix($path), $useCache);
		}

		public function isAllowed(string $path, bool $useCache = true): bool {
			return $this->projection->isAllowed($this->addPrefix($path), $useCache);
		}

		public function isAllowedExplicitly(string $path, bool $useCache = true): bool {
			return $this->projection->isAllowedExplicitly($this->addPrefix($path), $useCache);
		}

		public function isDenied(string $path, bool $useCache = true): bool {
			return $this->projection->isDenied($this->addPrefix($path), $useCache);
		}

		public function isDeniedExplicitly(string $path, bool $useCache = true): bool {
			return $this->projection->isDeniedExplicitly($this->addPrefix($path), $useCache);
		}

		public function filter(array $data, string $prefix = null): array {
			if ($prefix !== null)
				$prefix = $this->addPrefix($prefix);
			else
				$prefix = $this->prefix;

			return $this->projection->filter($data, $prefix);
		}

		protected function addPrefix(string $path): string {
			return $this->prefix . '.' . $path;
		}
	}
