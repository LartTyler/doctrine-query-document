<?php
	namespace DaybreakStudios\DoctrineQueryDocument\Exception;

	class CannotDirectlySearchRelationshipException extends ResolutionException {
		/**
		 * CannotSearchByDirectRelationshipException constructor.
		 *
		 * @param string $field
		 */
		public function __construct(string $field) {
			parent::__construct(sprintf('You may not search by %1$s; you must provide a child field to search, in ' .
				'the form \'%1$s.fieldName\'', $field));
		}
	}