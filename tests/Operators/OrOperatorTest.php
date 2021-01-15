<?php
	namespace Operators;

	use DaybreakStudios\DoctrineQueryDocument\QueryManager;
	use Doctrine\DBAL\Types\Types;

	class OrOperatorTest extends AbstractOperatorTestCase {
		/**
		 * @return void
		 */
		public function testOrOperator() {
			$em = $this->createMockEntityManager();

			$metadata = $this->createMockClassMetadata('Entity');
			$this->addClassMetadataField($metadata, 'a', Types::INTEGER);
			$this->addClassMetadataField($metadata, 'b', Types::INTEGER);

			$qm = new QueryManager($em);
			$qm->apply(
				$qb = $this->createQueryBuilder($em, 'Entity', 'e'),
				[
					'$or' => [
						[
							'a' => 1,
						],
						[
							'b' => 2,
						],
					],
				]
			);

			$this->assertEquals('SELECT e FROM Entity e WHERE e.a = ?0 OR e.b = ?1', $qb->getDQL());

			$qm->apply(
				$qb = $this->createQueryBuilder($em, 'Entity', 'e'),
				[
					'$or' => [
						[
							'a' => 1,
							'b' => 2,
						],
						[
							'b' => 3,
						],
					],
				]
			);

			$this->assertEquals('SELECT e FROM Entity e WHERE (e.a = ?0 AND e.b = ?1) OR e.b = ?2', $qb->getDQL());

			$qm->apply(
				$qb = $this->createQueryBuilder($em, 'Entity', 'e'),
				[
					'$or' => [
						[
							'a' => 1,
							'$or' => [
								[
									'b' => 1,
								],
								[
									'b' => 2,
								],
							],
						],
						[
							'b' => 2,
						],
					],
				]
			);

			$this->assertEquals(
				'SELECT e FROM Entity e WHERE (e.a = ?0 AND (e.b = ?1 OR e.b = ?2)) OR e.b = ?3',
				$qb->getDQL()
			);
		}
	}