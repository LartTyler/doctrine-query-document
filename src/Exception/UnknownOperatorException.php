<?php
	namespace DaybreakStudios\DoctrineQueryDocument\Exception;

	class UnknownOperatorException extends QueryException {
		/**
		 * UnknownOperatorException constructor.
		 *
		 * @param string $operator
		 */
		public function __construct(string $operator) {
			parent::__construct('Unrecognized operator: ' . $operator);
		}
	}