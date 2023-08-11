<?php
	namespace DaybreakStudios\DoctrineQueryDocument\Projection;

	final class AllowResult {
		/**
		 * Indicates that the result was explicit, meaning that the projection didn't allow or deny the query by
		 * default; that the queried path was one of the keys in the projection.
		 */
		public const IS_EXPLICIT = 1;

		public const ALLOW = 2;
		public const DENY = 4;

		private function __construct() {}

		public static function allow(bool $explicit = false): int {
			return self::ALLOW | (self::IS_EXPLICIT * (int)$explicit);
		}

		public static function deny(bool $explicit = false): int {
			return self::DENY | (self::IS_EXPLICIT * (int)$explicit);
		}

		public static function isAllow(int $value): bool {
			return $value & self::ALLOW !== 0;
		}

		public static function isExplicitAllow(int $value): bool {
			return self::isAllow($value) && self::isExplicit($value);
		}

		public static function isDeny(int $value): bool {
			return $value & self::DENY !== 0;
		}

		public static function isExplicitDeny(int $value): bool {
			return self::isDeny($value) && self::isExplicit($value);
		}

		public static function isExplicit(int $value): bool {
			return $value & self::IS_EXPLICIT !== 0;
		}

		public static function from(bool $allowed, bool $explicit): int {
			return $allowed ? self::allow($explicit) : self::deny($explicit);
		}
	}
