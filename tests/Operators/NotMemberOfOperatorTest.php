<?php
	namespace Operators;

	use DaybreakStudios\DoctrineQueryDocument\QueryManager;
	use Doctrine\ORM\EntityManager;
	use Doctrine\ORM\Mapping\ClassMetadata;
	use Doctrine\ORM\QueryBuilder;
	use PHPUnit\Framework\TestCase;

	class NotMemberOfOperatorTest extends TestCase {
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
			$qb = new QueryBuilder($em);
			$qb
				->from('Entity', 'e')
				->select('e');

			$qm->apply(
				$qb,
				[
					'related' => [
						'$notMemberOf' => 1,
					],
				],
			);

			$this->assertEquals('SELECT e FROM Entity e WHERE ?0 NOT MEMBER OF e.related', $qb->getDQL());
		}
	}