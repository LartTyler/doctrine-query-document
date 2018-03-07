<?php
	namespace DaybreakStudios\DoctrineQueryDocument\Exception;

	class InvalidFieldValueException extends QueryException {
		/**
		 * InvalidFieldValueException constructor.
		 *
		 * @param string      $field
		 * @param string      $expected
		 * @param null|string $operator
		 */
		public function __construct(string $field, string $expected, ?string $operator = null) {
			if ($operator)
				$field = $field . '.$' . $operator;

			parent::__construct(sprintf('Invalid parameter for %s; value must be a(n) %s', $field, $expected));
		}
	}