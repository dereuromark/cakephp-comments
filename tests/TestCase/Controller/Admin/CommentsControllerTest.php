<?php
declare(strict_types=1);

namespace Comments\Test\TestCase\Controller\Admin;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * @uses \Comments\Controller\Admin\CommentsController
 */
class CommentsControllerTest extends TestCase {

	use IntegrationTestTrait;

	/**
	 * @var list<string>
	 */
	protected array $fixtures = [
		'plugin.Comments.Comments',
	];

	/**
	 * @uses \Comments\Controller\Admin\CommentsController::index()
	 *
	 * @return void
	 */
	public function testIndex(): void {
		$this->markTestIncomplete('Not implemented yet.');
	}

	/**
	 * @uses \Comments\Controller\Admin\CommentsController::view()
	 *
	 * @return void
	 */
	public function testView(): void {
		$this->markTestIncomplete('Not implemented yet.');
	}

	/**
	 * @uses \Comments\Controller\Admin\CommentsController::edit()
	 *
	 * @return void
	 */
	public function testEdit(): void {
		$this->markTestIncomplete('Not implemented yet.');
	}

	/**
	 * @uses \Comments\Controller\Admin\CommentsController::delete()
	 *
	 * @return void
	 */
	public function testDelete(): void {
		$this->markTestIncomplete('Not implemented yet.');
	}

}
