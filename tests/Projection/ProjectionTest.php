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

			// Reverse-inheritance of explicit status
			$projection = Projection::fromFields(
				[
					'a.b.c' => true,
				],
			);

			$this->assertEquals(
				QueryResult::allow(true),
				$projection->query('a.b'),
				'explicit allows on child nodes are passed up to parent node',
			);

			$this->assertEquals(
				QueryResult::allow(true),
				$projection->query('a'),
				'explicit allows on child nodes are passed up to all ancestors',
			);

			// NOTE Unlike with allows, denies cannot be passed up to ancestors. If a node is explicitly denied further
			//      down the line, ancestors MUST be marked as allowed, otherwise a denial could prevent all descendents
			//      from being marked as allowed.
			$projection = Projection::fromFields(['a.b.c' => false]);

			$this->assertEquals(
				QueryResult::allow(true),
				$projection->query('a.b'),
				'explicit denies are NOT passed up to parents',
			);

			$this->assertEquals(
				QueryResult::allow(true),
				$projection->query('a'),
				'explicit denies are NOT passed up to all ancestors',
			);

			$projection = Projection::fromFields(['a.b.*' => false, 'a.b.c' => true]);

			$this->assertEquals(QueryResult::allow(true), $projection->query('a.b'));
			$this->assertEquals(QueryResult::deny(true), $projection->query('a.b.foo'));
			$this->assertEquals(QueryResult::allow(true), $projection->query('a.b.c'));
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

			$projection = Projection::fromFields(
				[
					'a' => false,
				],
			);

			$this->assertTrue($projection->isDeniedExplicitly('a.b'), 'child nodes inherit parent explict deny');

			$projection = Projection::fromFields(
				[
					'a.b' => false,
				],
			);

			$this->assertFalse($projection->isDenied('a'), 'ancestors are assumed allowed');
			$this->assertTrue($projection->isDeniedExplicitly('a.b'), 'child node matches explicitly');
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

			$projection = Projection::fromFields(
				[
					'a.b' => true,
				],
				true,
			);

			$this->assertTrue($projection->isAllowedExplicitly('a.b'));
			$this->assertTrue($projection->isAllowed('a.c'));
			$this->assertFalse($projection->isAllowedExplicitly('a.c'));
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

		public function testAllowedChildNode() {
			$projection = Projection::fromFields(
				[
					'child.field' => true,
				],
				false,
			);

			$this->assertTrue($projection->isAllowedExplicitly('child'));
			$this->assertTrue($projection->isAllowedExplicitly('child.field'));
			$this->assertFalse($projection->isAllowed('child.bar'));

			$projection = Projection::fromFields(
				[
					'child' => true,
				],
				false,
			);

			$this->assertTrue($projection->isAllowedExplicitly('child'));
			$this->assertTrue($projection->isAllowedExplicitly('child.field'));
			$this->assertFalse($projection->isAllowed('bar'));

			$projection = Projection::fromFields(
				[
					'child.field' => false,
					'other.*' => false,
					'other.bar' => true,
				],
				true,
			);

			$this->assertTrue($projection->isAllowed('child'));
			$this->assertTrue($projection->isDeniedExplicitly('child.field'));
			$this->assertTrue($projection->isAllowed('child.bar'));

			$this->assertTrue($projection->isAllowedExplicitly('other'));
			$this->assertTrue($projection->isAllowedExplicitly('other.bar'));
			$this->assertTrue($projection->isDeniedExplicitly('other.foo'));
		}

		public function testMatchAllFallback() {
			$projection = Projection::fromFields(
				[
					'child.*' => false,
					'child.foo' => true,
				],
				true,
			);

			$this->assertFalse($projection->isAllowed('child.bar'));
			$this->assertTrue($projection->isDeniedExplicitly('child.bar'));
			$this->assertTrue($projection->isAllowedExplicitly('child.foo'));
		}
	}
