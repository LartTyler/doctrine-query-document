<?php
	namespace Operators;

	use DaybreakStudios\DoctrineQueryDocument\QueryManager;
	use Doctrine\DBAL\Types\Type;
	use Doctrine\ORM\EntityManager;
	use Doctrine\ORM\Mapping\ClassMetadata;
	use Doctrine\ORM\QueryBuilder;
	use PHPUnit\Framework\TestCase;

	class NotContainsOperatorTest extends TestCase {
		/**
		 * @return void
		 */
		public function testMemberOfOperator(): void {
			$em = $this->createMock(EntityManager::class);

			$rootMetadata = $this->createMock(ClassMetadata::class);

			$rootMetadata->expects($this->any())
				->method('getName')
				->willReturn('Entity');

			$rootMetadata->expects($this->any())
				->method('hasAssociation')
				->with('related')
				->willReturn(true);

			$rootMetadata->expects($this->any())
				->method('isCollectionValuedAssociation')
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
						]
					)
				);

			$qm = new QueryManager($em);

			$qm->apply(
				$qb = $this->createQueryBuilder($em),
				[
					'related' => [
						'$ncontains' => 1,
					],
				],
			);

			$this->assertEquals('SELECT e FROM Entity e WHERE ?0 NOT MEMBER OF e.related', $qb->getDQL());
		}

		/**
		 * @return void
		 */
		public function testJsonMemberOfOperator(): void {
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
				->willReturn(Type::JSON);

			$em->expects($this->any())
				->method('getClassMetadata')
				->willReturn($rootMetadata);

			$qm = new QueryManager($em);

			$qm->apply(
				$qb = $this->createQueryBuilder($em),
				[
					'json' => [
						'$ncontains' => 1,
					],
				]
			);

			$this->assertEquals('SELECT e FROM Entity e WHERE NOT JSON_CONTAINS(e.json, ?0)', $qb->getDQL());

			$qm->apply(
				$qb = $this->createQueryBuilder($em),
				[
					'json.nested' => [
						'$ncontains' => 1,
					],
				]
			);

			$this->assertEquals('SELECT e FROM Entity e WHERE NOT JSON_CONTAINS(e.json, ?0, "$.nested")', $qb->getDQL());
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