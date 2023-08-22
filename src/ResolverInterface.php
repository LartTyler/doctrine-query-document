<?php
	namespace DaybreakStudios\DoctrineQueryDocument;

	use Doctrine\Persistence\Mapping\ClassMetadata;

	interface ResolverInterface {
		/**
		 * @param string $class
		 * @param string $field
		 *
		 * @return null|string
		 */
		public function getMappedField(string $class, string $field): ?string;

		/**
		 * @param string[][] $mappedFields
		 *
		 * @return static
		 */
		public function setAllMappedFields(array $mappedFields): static;

		/**
		 * @param string   $class
		 * @param string[] $mappedFields
		 *
		 * @return static
		 */
		public function setMappedFields(string $class, array $mappedFields): static;

		/**
		 * @param string $class
		 * @param string $field
		 * @param string $target
		 *
		 * @return static
		 */
		public function setMappedField(string $class, string $field, string $target): static;

		/**
		 * @param string $class
		 * @param string $field
		 *
		 * @return static
		 */
		public function removeMappedField(string $class, string $field): static;

		/**
		 * @param string $field
		 * @param array  $context
		 *
		 * @return string
		 */
		public function resolve(string $field, array $context = []): string;

		/**
		 * @param string $alias
		 *
		 * @return ClassMetadata|null
		 */
		public function getMetadata(string $alias): ?ClassMetadata;
	}
