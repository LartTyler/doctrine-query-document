<?php
	namespace DaybreakStudios\DoctrineQueryDocument;

	trait MappedFieldsTrait {
		/**
		 * @var string[][]
		 */
		protected $mappedFields = [];

		/**
		 * {@inheritdoc}
		 */
		public function setAllMappedFields(array $mappedFields) {
			$this->mappedFields = [];

			foreach ($mappedFields as $class => $fields)
				$this->setMappedFields($class, $fields);

			return $this;
		}

		/**
		 * @param string $class
		 *
		 * @return bool
		 */
		public function hasMappedFields(string $class): bool {
			return isset($this->mappedFields[$class]);
		}

		/**
		 * {@inheritdoc}
		 */
		public function getMappedField(string $class, string $field): ?string {
			return $this->mappedFields[$class][$field] ?? null;
		}

		/**
		 * {@inheritdoc}
		 */
		public function setMappedFields(string $class, array $mappedFields) {
			$this->mappedFields[$class] = [];

			foreach ($mappedFields as $field => $mappedField)
				$this->setMappedField($class, $field, $mappedField);
		}

		/**
		 * {@inheritdoc}
		 */
		public function setMappedField(string $class, string $field, string $target) {
			if (!isset($this->mappedFields[$class]))
				$this->mappedFields[$class] = [];

			$this->mappedFields[$class][$field] = $target;

			return $this;
		}

		/**
		 * {@inheritdoc}
		 */
		public function removeMappedField(string $class, string $field) {
			if (!isset($this->mappedFields[$class]))
				return $this;

			unset($this->mappedFields[$class][$field]);

			return $this;
		}
	}