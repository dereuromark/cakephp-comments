<?php
declare(strict_types=1);

namespace Comments\Test\TestCase\Model\Table;

use Cake\TestSuite\TestCase;
use Comments\Model\Table\CommentsTable;

/**
 * Comments\Model\Table\CommentsTable Test Case
 */
class CommentsTableTest extends TestCase {

	/**
	 * Test subject
	 *
	 * @var \Comments\Model\Table\CommentsTable
	 */
	protected $Comments;

	/**
	 * Fixtures
	 *
	 * @var list<string>
	 */
	protected array $fixtures = [
		'plugin.Comments.Comments',
		'plugin.Comments.Users',
	];

	/**
	 * setUp method
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$config = $this->getTableLocator()->exists('Comments') ? [] : ['className' => CommentsTable::class];
		$this->Comments = $this->getTableLocator()->get('Comments', $config);
	}

	/**
	 * tearDown method
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		unset($this->Comments);

		parent::tearDown();
	}

	/**
	 * Test validationDefault method
	 *
	 * @uses \Comments\Model\Table\CommentsTable::validationDefault()
	 *
	 * @return void
	 */
	public function testValidationDefault(): void {
		$this->markTestIncomplete('Not implemented yet.');
	}

	/**
	 * Test buildRules method
	 *
	 * @uses \Comments\Model\Table\CommentsTable::buildRules()
	 *
	 * @return void
	 */
	public function testBuildRules(): void {
		$this->markTestIncomplete('Not implemented yet.');
	}

	/**
	 * Test add method
	 *
	 * @uses \Comments\Model\Table\CommentsTable::add()
	 *
	 * @return void
	 */
	public function testAdd(): void {
		$this->markTestIncomplete('Not implemented yet.');
	}

}
