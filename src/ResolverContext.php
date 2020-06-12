<?php
	namespace DaybreakStudios\DoctrineQueryDocument;

	class ResolverContext {
		/**
		 * If set, indicates that the resolver should resolve paths that end in an association field to their actual ID.
		 *
		 * Default: true
		 */
		public const RESOLVE_ASSOCIATIONS_TO_ID = 'resolveAssociationToId';

		/**
		 * If set, indicates that the resolver should resolve paths that descend into a JSON object to an appropriate
		 * call to JSON_EXTRACT.
		 *
		 * Default: true
		 */
		public const RESOLVE_EMBEDDED_JSON_TO_EXTRACT_FUNC = 'resolveEmbeddedJsonToExtractFunc';
	}
