<?php
	namespace DaybreakStudios\DoctrineQueryDocument\Projection;

	interface PrefixableProjectionInterface {
		/**
		 * Creates a new projection where all query paths will be prefixed with the given prefix value.
		 *
		 * Implementations should take care to flatten any previously prefixed projections to avoid deeply nesting
		 * wrapped objects.
		 *
		 * @param string $prefix
		 *
		 * @return ProjectionInterface
		 */
		public function withPrefix(string $prefix): ProjectionInterface;
	}
