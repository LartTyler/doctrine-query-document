<?php
	namespace Projection;

	use DaybreakStudios\DoctrineQueryDocument\Projection\Projection;
	use DaybreakStudios\DoctrineQueryDocument\Projection\QueryResult;
	use PHPUnit\Framework\TestCase;

	class ProjectionTest extends TestCase {
		public function testEmpty() {
			$projection = Projection::fromFields([]);

			$this->assertEquals(QueryResult::allow(), $projection->query('test'));
			$this->assertEquals(QueryResult::allow(), $projection->query('test.nested'));
		}

		public function testQuery() {
			$projection = Projection::fromFields(
				[
					'a.b.c' => true,
				],
			);

			$result = $projection->query('a.b.c');
			$this->assertEquals(QueryResult::allow(true), $result, 'reports explicit allows');

			$result = $projection->query('1.2.3');
			$this->assertEquals(QueryResult::deny(), $result, 'reports implicit denies');

			$projection = Projection::fromFields(
				[
					'a.b.c' => false,
				],
			);

			$result = $projection->query('a.b.c');
			$this->assertEquals(QueryResult::deny(true), $result, 'reports explicit denies');

			$result = $projection->query('1.2.3');
			$this->assertEquals(QueryResult::allow(), $result, 'reports implicit allows');

			$projection = Projection::fromFields(
				[
					'a.b' => true,
				],
			);

			$result = $projection->query('a.b.c');
			$this->assertEquals(
				QueryResult::allow(true),
				$result,
				'explicit allows on parent nodes are inherited by child nodes',
			);

			$projection = Projection::fromFields(
				[
					'a.b' => false,
				],
			);

			$result = $projection->query('a.b.c');
			$this->assertEquals(
				QueryResult::deny(true),
				$result,
				'explict denys on parent nodes are inherited by child nodes',
			);
		}

		public function testIsAllowedByDefault() {
			$projection = Projection::fromFields(
				[
					'a.b.c' => true,
					'1.2.3' => true,
				],
			);

			$this->assertFalse($projection->isAllowedByDefault(), 'infers default deny from allow-list');

			$projection = Projection::fromFields(
				[
					'a.b.c' => false,
					'1.2.3' => false,
				],
			);

			$this->assertTrue($projection->isAllowedByDefault(), 'infers default allow from deny-list');

			$projection = Projection::fromFields(
				[
					'a.b.c' => true,
					'1.2.3' => false,
				],
			);

			$this->assertFalse($projection->isAllowedByDefault(), 'infers default from initial value in mixed list');
		}

		public function testIsDenied() {
			$projection = Projection::fromFields(
				[
					'a.b.c' => false,
				],
			);

			$this->assertTrue($projection->isDenied('a.b.c'), 'matches explicit deny');

			$projection = Projection::fromFields(
				[
					'a.b.c' => true,
				],
			);

			$this->assertTrue($projection->isDenied('1.2.3'), 'matches implicit deny');
		}

		public function testIsDeniedExplicitly() {
			$projection = Projection::fromFields(
				[
					'a.b.c' => false,
				],
			);

			$this->assertTrue($projection->isDeniedExplicitly('a.b.c'), 'matches explicit deny');

			$projection = Projection::fromFields(
				[
					'a.b.c' => true,
				],
			);

			$this->assertFalse($projection->isDeniedExplicitly('1.2.3'), 'does not match implicit deny');
		}

		public function testIsAllowed() {
			$projection = Projection::fromFields(
				[
					'a.b.c' => true,
				],
			);

			$this->assertTrue($projection->isAllowed('a.b.c'), 'matches explicit allow');

			$projection = Projection::fromFields(
				[
					'a.b.c' => false,
				],
			);

			$this->assertTrue($projection->isAllowed('1.2.3'), 'matches implicit allow');
		}

		public function testIsAllowedExplicitly() {
			$projection = Projection::fromFields(
				[
					'a.b.c' => true,
				],
			);

			$this->assertTrue($projection->isAllowedExplicitly('a.b.c'), 'matches explicit allow');

			$projection = Projection::fromFields(
				[
					'a.b.c' => false,
				],
			);

			$this->assertFalse($projection->isAllowedExplicitly('1.2.3'), 'does not match implicit allow');
		}

		public function testFilter() {
			$projection = Projection::fromFields(
				[
					'a' => true,
					'b' => true,
					'd' => true,
				],
			);

			$this->assertEquals(
				['a' => 1, 'b' => 2, 'd' => 4],
				$projection->filter(
					[
						'a' => 1,
						'b' => 2,
						'c' => 3,
						'd' => 4,
					],
				),
				'removes unmatched keys from allow-list',
			);

			$projection = Projection::fromFields(
				[
					'a' => false,
					'c' => false,
				],
			);

			$this->assertEquals(
				['b' => 2, 'd' => 4],
				$projection->filter(
					[
						'a' => 1,
						'b' => 2,
						'c' => 3,
						'd' => 4,
					],
				),
				'removes matched keys from deny-list',
			);
		}
	}
