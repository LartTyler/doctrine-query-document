<?php
	namespace DaybreakStudios\DoctrineQueryDocument\Projection;

	class PrefixedProjection implements ProjectionInterface, PrefixableProjectionInterface {
		public function __construct(
			protected ProjectionInterface $projection,
			protected string $prefix,
		) {}

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

		public function withPrefix(string $prefix): ProjectionInterface {
			return new static($this->projection, $this->addPrefix($prefix));
		}

		protected function addPrefix(string $path): string {
			return $this->prefix . '.' . $path;
		}
	}
