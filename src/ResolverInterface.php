<?php
	namespace DaybreakStudios\DoctrineQueryDocument;

	interface ResolverInterface {
		/**
		 * @param string $class
		 * @param string $field
		 *
		 * @return null|string
		 */
		public function getMappedField(string $class, string $field): ?string;

		/**
		 * @param string   $class
		 * @param string[] $mappedFields
		 *
		 * @return $this
		 */
		public function setMappedFields(string $class, array $mappedFields);

		/**
		 * @param string $class
		 * @param string $field
		 * @param string $target
		 *
		 * @return $this
		 */
		public function setMappedField(string $class, string $field, string $target);

		/**
		 * @param string $class
		 * @param string $field
		 *
		 * @return $this
		 */
		public function removeMappedField(string $class, string $field);

		/**
		 * @param string $field
		 *
		 * @return string
		 */
		public function resolve(string $field): string;
	}