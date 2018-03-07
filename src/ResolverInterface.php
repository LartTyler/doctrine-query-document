<?php
	namespace DaybreakStudios\DoctrineQueryDocument;

	interface ResolverInterface {
		/**
		 * @param string $field
		 *
		 * @return string
		 */
		public function resolve(string $field): string;
	}