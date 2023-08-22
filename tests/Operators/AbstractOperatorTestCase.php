<?php
	namespace Operators;

	use Doctrine\ORM\EntityManager;
	use Doctrine\ORM\Mapping\ClassMetadata;
	use Doctrine\ORM\QueryBuilder;
	use PHPUnit\Framework\MockObject\MockObject;
	use PHPUnit\Framework\TestCase;

	class AbstractOperatorTestCase extends TestCase {
		/**
		 * @var MockObject[]|ClassMetadata[]
		 */
		protected $mockedClassMetadata = [];

		/**
		 * @var array[]
		 */
		protected $mockedClassMetadataFields = [];

		/**
		 * @return MockObject|EntityManager
		 */
		protected function createMockEntityManager(): MockObject {
			$entityManager = $this->createMock(EntityManager::class);

			$entityManager->expects($this->any())
				->method('getClassMetadata')
				->will(
					$this->returnCallback(
						function(string $name) {
							return $this->mockedClassMetadata[$name];
						}
					)
				);

			return $entityManager;
		}

		/**
		 * @param string   $name
		 * @param string[] $relationships
		 *
		 * @return MockObject|ClassMetadata
		 */
		protected function createMockClassMetadata(
			string $name,
			array $relationships = []
		): MockObject {
			$metdata = $this->createMock(ClassMetadata::class);

			$metdata->expects($this->any())
				->method('getName')
				->willReturn($name);

			foreach ($relationships as $relationship) {
				$metdata->expects($this->any())
					->method('hasAssociation')
					->with($relationship)
					->willReturn(true);
			}

			return $this->mockedClassMetadata[$name] = $metdata;
		}

		/**
		 * @param EntityManager $em
		 * @param string        $rootEntity
		 * @param string        $rootAlias
		 *
		 * @return QueryBuilder
		 */
		protected function createQueryBuilder(EntityManager $em, string $rootEntity, string $rootAlias): QueryBuilder {
			$qb = new QueryBuilder($em);
			$qb
				->from($rootEntity, $rootAlias)
				->select($rootAlias);

			return $qb;
		}

		/**
		 * @param MockObject|ClassMetadata $metadata
		 * @param string                   $name
		 * @param string                   $type
		 *
		 * @return void
		 */
		protected function addClassMetadataField(MockObject $metadata, string $name, string $type) {
			$key = $metadata->getName();

			if (!isset($this->mockedClassMetadataFields[$key])) {
				$this->mockedClassMetadataFields[$key] = [];

				$metadata->expects($this->any())
					->method('hasField')
					->will(
						$this->returnCallback(
							function(string $field) use ($key) {
								return isset($this->mockedClassMetadataFields[$key][$field]);
							}
						)
					);

				$metadata->expects($this->any())
					->method('getTypeOfField')
					->will(
						$this->returnCallback(
							function(string $field) use ($key) {
								return $this->mockedClassMetadataFields[$key][$field];
							}
						)
					);
			}

			$this->mockedClassMetadataFields[$key][$name] = $type;
		}
	}
