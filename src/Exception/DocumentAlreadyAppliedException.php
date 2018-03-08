<?php
	namespace DaybreakStudios\DoctrineQueryDocument\Exception;

	class DocumentAlreadyAppliedException extends QueryException {
		public function __construct() {
			parent::__construct('This query document has already been applied');
		}
	}