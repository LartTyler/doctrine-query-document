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
		 * Removes the number of elements specified by $length, and injects the provided list. If $length is zero, or
		 * longer than the length of the list, all following elements will be removed prior to injecting the provided
		 * list.
		 *
		 * @param LinkedList $list
		 * @param int        $length
		 *
		 * @return $this
		 */
		public function splice(LinkedList $list, $length = 0) {
			if ($this->getNext() && $length > 0) {
				for ($i = 0; $i < $length; $i++) {
					$next = $this->getNext()->getNext();

					if (!$next) {
						$this->setNext(null);

						break;
					}

					$this->setNext($next);
				}
			} else
				$this->setNext(null);

			return $this->inject($list);
		}

		/**
		 * @param LinkedList $list
		 *
		 * @return $this
		 */
		public function inject(LinkedList $list) {
			$this->value = $list->getValue();

			// If the current node has no next item, we can blindly call setNext() and stop processing early
			if (!$this->getNext()) {
				$this->setNext($list->getNext());

				return $this;
				// Otherwise, if the injected list's head has no next item, we don't even need to call setNext() at all
			} else if (!$list->getNext())
				return $this;

			$oldNext = $this->getNext();
			$this->setNext($tail = $list->getNext());

			while ($tail->getNext())
				$tail = $tail->getNext();

			$tail->setNext($oldNext);

			return $this;
		}

		/**
		 * @return mixed[]
		 */
		public function all(): array {
			$node = $this;
			$all = [];

			do {
				$all[] = $node->getValue();
			} while ($node = $node->getNext());

			return $all;
		}

		/**
		 * @param array $items
		 *
		 * @return static
		 */
		public static function fromArray(array $items) {
			if (!$items)
				throw new \InvalidArgumentException('Cannot create list from an empty array');

			$head = $tail = new static(array_shift($items));

			foreach ($items as $item)
				$tail->setNext($tail = new static($item));

			return $head;
		}
	}