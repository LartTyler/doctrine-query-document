<?php
	namespace DaybreakStudios\DoctrineQueryDocument;

	trait MappedFieldsTrait {
		/**
		 * @var string[][]
		 */
		protected array $mappedFields = [];

		/**
		 * @param string[][] $mappedFields
		 *
		 * @return $this
		 */
		public function setAllMappedFields(array $mappedFields): static {
			$this->mappedFields = [];

			foreach ($mappedFields as $class => $fields)
				$this->setMappedFields($class, $fields);

			return $this;
		}

		public function hasMappedFields(string $class): bool {
			return isset($this->mappedFields[$class]);
		}

		public function getMappedField(string $class, string $field): ?string {
			return $this->mappedFields[$class][$field] ?? null;
		}

		/**
		 * @param string   $class
		 * @param string[] $mappedFields
		 *
		 * @return static
		 */
		public function setMappedFields(string $class, array $mappedFields): static {
			$this->mappedFields[$class] = [];

			foreach ($mappedFields as $field => $mappedField)
				$this->setMappedField($class, $field, $mappedField);

			return $this;
		}

		public function setMappedField(string $class, string $field, string $target): static {
			if (!isset($this->mappedFields[$class]))
				$this->mappedFields[$class] = [];

			$this->mappedFields[$class][$field] = $target;

			return $this;
		}

		public function removeMappedField(string $class, string $field): static {
			if (!isset($this->mappedFields[$class]))
				return $this;

			unset($this->mappedFields[$class][$field]);

			return $this;
		}
	}
