<?php
	namespace DaybreakStudios\DoctrineQueryDocument\Exception;

	class UnknownFieldException extends ResolutionException {
		/**
		 * UnknownFieldException constructor.
		 *
		 * @param string $field
		 */
		public function __construct(string $field) {
			parent::__construct('Unknown field: ' . $field);
		}
	}