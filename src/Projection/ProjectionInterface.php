<?php
	namespace DaybreakStudios\DoctrineQueryDocument\Projection;

	/**
	 * A common interface for all projection interpreters.
	 *
	 * As interpreting projection definitions can be expensive, it is expected that all interpreters will use some
	 * form of caching mechanism for queries. As such, query methods (such as {@see ProjectionInterface::query()} and
	 * {@see Projection::isAllowed()}) include a `$useCache` argument, and implementations are expected to honor the
	 * value of that argument. In the event that an implementation does _not_ include a caching mechanism, it should
	 * be well-documented that the `$useCache` argument has no effect.
	 */
	interface ProjectionInterface {
		public const MATCH_ALL_SYMBOL = '*';

		/**
		 * Returns an integer representing the result of the query.
		 *
		 * See {@see QueryResult} for more information.
		 *
		 * @param string $path
		 * @param bool   $useCache
		 *
		 * @return int
		 */
		public function query(string $path, bool $useCache = true): int;

		/**
		 * Returns `true` if the given `$path` is allowed by the projection.
		 *
		 * A path is considered allowed in one of two cases:
		 * - The projection is a deny-list, and neither the path nor its ancestors are present in the list
		 * - The projection is an allow-list, and the path or one of its ancestors _is_ present in the list
		 *
		 * @param string $path
		 * @param bool   $useCache
		 *
		 * @return bool
		 */
		public function isAllowed(string $path, bool $useCache = true): bool;

		/**
		 * Returns `true` only if the given `$path` is present in an allow-list.
		 *
		 * A path is considered explicitly allowed _only if_ the path is present in the list, and the value of its
		 * entry is `true`.
		 *
		 * @param string $path
		 * @param bool   $useCache
		 *
		 * @return bool
		 */
		public function isAllowedExplicitly(string $path, bool $useCache = true): bool;

		/**
		 * Returns `true` if the given `$path` is denied by the projection.
		 *
		 * A path is considered denied in one of two cases:
		 * - The projection is a deny-list, and the path or one of its ancestors is present in the list
		 * - The projection is an allow-list, and the neither the path nor one of its ancestors is present in the list
		 *
		 * @param string $path
		 * @param bool   $useCache
		 *
		 * @return bool
		 */
		public function isDenied(string $path, bool $useCache = true): bool;

		/**
		 * Returns `true` only if the given `$path` is present in a deny-list.
		 *
		 * A path is considered explicitly denied _only if_ the path is present in the list, and the value of its
		 * entry is `false`.
		 *
		 * @param string $path
		 * @param bool   $useCache
		 *
		 * @return bool
		 */
		public function isDeniedExplicitly(string $path, bool $useCache = true): bool;

		/**
		 * Walks an array, removing any keys that are not allowed by the projection.
		 *
		 * A key is allowed if {@see ProjectionInterface::isAllowed()} evaluates to `true`.
		 *
		 * If `$prefix` is provided, each key in `$data` will be prefixed with the string followed by a dot (e.g.
		 * "prefix.$key").
		 *
		 * @param array       $data
		 * @param string|null $prefix
		 *
		 * @return array
		 */
		public function filter(array $data, string $prefix = null): array;
	}
