<?php
	namespace DaybreakStudios\DoctrineQueryDocument;

	use Doctrine\Common\Persistence\ObjectManager;

	class QueryManager implements QueryManagerInterface {
		protected $objectManager;

		/**
		 * QueryManager constructor.
		 *
		 * @param ObjectManager $objectManager
		 * @param array         $operators
		 */
		public function __construct(ObjectManager $objectManager, array $operators = []) {
			$this->objectManager = $objectManager;
		}
	}