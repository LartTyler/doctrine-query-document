<?php
	namespace Operators;

	use DaybreakStudios\DoctrineQueryDocument\QueryManager;
	use Doctrine\DBAL\Types\Types;
	use Doctrine\ORM\EntityManager;
	use Doctrine\ORM\Mapping\ClassMetadata;
	use Doctrine\ORM\QueryBuilder;
	use PHPUnit\Framework\TestCase;

	class SizeOperatorTest extends TestCase {
		/**
		 * @return void
		 */
		public function testSizeOperator(): void {
			$em = $this->createMock(EntityManager::class);

			$rootMetadata = $this->createMock(ClassMetadata::class);

			$rootMetadata->expects($this->any())
				->method('getName')
				->willReturn('Entity');

			$rootMetadata->expects($this->any())
				->method('hasAssociation')
				->with('related')
				->willReturn(true);

			$relatedMetadata = $this->createMock(ClassMetadata::class);

			$relatedMetadata->expects($this->any())
				->method('getName')
				->willReturn('Related');

			$em->expects($this->any())
				->method('getClassMetadata')
				->will(
					$this->returnValueMap(
						[
							['Entity', $rootMetadata],
							['Related', $relatedMetadata],
						],
					),
				);

			$qm = new QueryManager($em);

			$qm->apply(
				$qb = $this->createQueryBuilder($em),
				[
					'related' => [
						'$size' => 1,
					],
				],
			);

			$this->assertEquals('SELECT e FROM Entity e WHERE SIZE(e.related) = ?0', $qb->getDQL());

			$qm->apply(
				$qb = $this->createQueryBuilder($em),
				[
					'related' => [
						'$size' => [
							'$gte' => 1,
						],
					],
				],
			);

			$this->assertEquals('SELECT e FROM Entity e WHERE SIZE(e.related) >= ?0', $qb->getDQL());
		}

		/**
		 * @return void
		 */
		public function testJsonSize(): void {
			$em = $this->createMock(EntityManager::class);
			$rootMetadata = $this->createMock(ClassMetadata::class);

			$rootMetadata->expects($this->any())
				->method('getName')
				->willReturn('Entity');

			$rootMetadata->expects($this->any())
				->method('hasField')
				->with('json')
				->willReturn(true);

			$rootMetadata->expects($this->any())
				->method('getTypeOfField')
				->with('json')
				->willReturn(Types::JSON);

			$em->expects($this->any())
				->method('getClassMetadata')
				->willReturn($rootMetadata);

			$qm = new QueryManager($em);

			$qm->apply(
				$qb = $this->createQueryBuilder($em),
				[
					'json' => [
						'$size' => 1,
					],
				],
			);

			$this->assertEquals('SELECT e FROM Entity e WHERE JSON_LENGTH(e.json) = ?0', $qb->getDQL());

			$qm->apply(
				$qb = $this->createQueryBuilder($em),
				[
					'json' => [
						'$size' => [
							'$gte' => 1,
						],
					],
				],
			);

			$this->assertEquals('SELECT e FROM Entity e WHERE JSON_LENGTH(e.json) >= ?0', $qb->getDQL());

			$qm->apply(
				$qb = $this->createQueryBuilder($em),
				[
					'json.field' => [
						'$size' => 1,
					],
				],
			);

			$this->assertEquals('SELECT e FROM Entity e WHERE JSON_LENGTH(e.json, \'$.field\') = ?0', $qb->getDQL());
		}

		/**
		 * @param EntityManager $em
		 *
		 * @return QueryBuilder
		 */
		protected function createQueryBuilder(EntityManager $em): QueryBuilder {
			$qb = new QueryBuilder($em);
			$qb
				->from('Entity', 'e')
				->select('e');

			return $qb;
		}
	}
