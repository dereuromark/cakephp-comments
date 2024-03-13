<?php
declare(strict_types=1);

namespace Comments\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Comments\Controller\CommentsController Test Case
 *
 * @uses \Comments\Controller\CommentsController
 */
class CommentsControllerTest extends TestCase {

	use IntegrationTestTrait;

	/**
	 * Fixtures
	 *
	 * @var list<string>
	 */
	protected array $fixtures = [
		'plugin.Comments.Comments',
	];

	/**
	 * Test add method
	 *
	 * @uses \Comments\Controller\CommentsController::add()
	 *
	 * @return void
	 */
	public function testAdd(): void {
		$this->markTestIncomplete('Not implemented yet.');
	}

	/**
	 * Test delete method
	 *
	 * @uses \Comments\Controller\CommentsController::delete()
	 *
	 * @return void
	 */
	public function testDelete(): void {
		$this->markTestIncomplete('Not implemented yet.');
	}

}
