<?php
	namespace DaybreakStudios\DoctrineQueryDocument;

	class LinkedList {
		/**
		 * @var mixed
		 */
		protected $value;

		/**
		 * @var LinkedList|null
		 */
		protected $next;

		/**
		 * LinkedList constructor.
		 *
		 * @param mixed      $value
		 */
		public function __construct($value) {
			$this->value = $value;
		}

		/**
		 * @return mixed
		 */
		public function getValue() {
			return $this->value;
		}

		/**
		 * @return LinkedList|null
		 */
		public function getNext(): ?LinkedList {
			return $this->next;
		}

		/**
		 * @param LinkedList|null $next
		 *
		 * @return $this
		 */
		public function setNext(?LinkedList $next) {
			$this->next = $next;

			return $this;
		}

		/**
		 * @param array $items
		 *
		 * @return static
		 */
		public static function fromArray(array $items) {
			if (!$items)
				throw new \InvalidArgumentException('Cannot create list from an empty array');

			$head = $node = new static(array_shift($items));

			foreach ($items as $item)
				$node->setNext($next = new static($item));

			return $head;
		}
	}