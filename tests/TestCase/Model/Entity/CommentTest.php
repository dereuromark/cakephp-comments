<?php
declare(strict_types=1);

namespace Comments\Test\TestCase\Model\Entity;

use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;
use Comments\Model\Entity\Comment;

/**
 * Comments\Model\Entity\Comment Test Case
 */
class CommentTest extends TestCase {

	/**
	 * Test mass assignment protection for id field
	 *
	 * @return void
	 */
	public function testIdNotMassAssignable(): void {
		$comment = new Comment();
		$comment->setAccess('id', false);

		$comment = $comment->patch([
			'id' => 999,
			'content' => 'Test comment',
			'model' => 'Posts',
			'foreign_key' => 1,
		]);

		$this->assertNull($comment->id);
		$this->assertSame('Test comment', $comment->content);
		$this->assertSame('Posts', $comment->model);
		$this->assertSame(1, $comment->foreign_key);
	}

	/**
	 * Test all other fields are mass assignable
	 *
	 * @return void
	 */
	public function testFieldsMassAssignable(): void {
		$data = [
			'content' => 'Test content',
			'model' => 'Articles',
			'foreign_key' => 123,
			'parent_id' => 5,
			'user_id' => 42,
			'name' => 'John Doe',
			'email' => 'john@example.com',
			'is_private' => true,
			'is_spam' => false,
		];

		$comment = new Comment($data);

		$this->assertSame('Test content', $comment->content);
		$this->assertSame('Articles', $comment->model);
		$this->assertSame(123, $comment->foreign_key);
		$this->assertSame(5, $comment->parent_id);
		$this->assertSame(42, $comment->user_id);
		$this->assertSame('John Doe', $comment->name);
		$this->assertSame('john@example.com', $comment->email);
		$this->assertTrue($comment->is_private);
		$this->assertFalse($comment->is_spam);
	}

	/**
	 * Test entity can be created with timestamps
	 *
	 * @return void
	 */
	public function testTimestamps(): void {
		$now = DateTime::now();
		$data = [
			'content' => 'Test',
			'model' => 'Posts',
			'foreign_key' => 1,
			'created' => $now,
			'modified' => $now,
		];

		$comment = new Comment($data);

		$this->assertEquals($now, $comment->created);
		$this->assertEquals($now, $comment->modified);
	}

	/**
	 * Test entity properties can be modified
	 *
	 * @return void
	 */
	public function testPropertyModification(): void {
		$comment = new Comment([
			'content' => 'Original content',
			'model' => 'Posts',
			'foreign_key' => 1,
		]);

		$comment->content = 'Updated content';
		$this->assertSame('Updated content', $comment->content);

		$comment->is_spam = true;
		$this->assertTrue($comment->is_spam);
	}

	/**
	 * Test entity toArray
	 *
	 * @return void
	 */
	public function testToArray(): void {
		$data = [
			'content' => 'Test',
			'model' => 'Posts',
			'foreign_key' => 1,
			'name' => 'Jane',
		];

		$comment = new Comment($data);
		$array = $comment->toArray();

		$this->assertIsArray($array);
		$this->assertSame('Test', $array['content']);
		$this->assertSame('Posts', $array['model']);
		$this->assertSame(1, $array['foreign_key']);
		$this->assertSame('Jane', $array['name']);
	}

	/**
	 * Test entity with null values
	 *
	 * @return void
	 */
	public function testNullValues(): void {
		$data = [
			'content' => 'Test',
			'model' => 'Posts',
			'foreign_key' => 1,
			'parent_id' => null,
			'user_id' => null,
			'name' => null,
			'email' => null,
		];

		$comment = new Comment($data);

		$this->assertNull($comment->parent_id);
		$this->assertNull($comment->user_id);
		$this->assertNull($comment->name);
		$this->assertNull($comment->email);
	}

	/**
	 * Test entity get method
	 *
	 * @return void
	 */
	public function testGet(): void {
		$comment = new Comment([
			'content' => 'Test content',
		]);

		$this->assertSame('Test content', $comment->get('content'));
	}

	/**
	 * Test entity has() method
	 *
	 * @return void
	 */
	public function testHas(): void {
		$comment = new Comment([
			'content' => 'Test',
		]);

		$this->assertTrue($comment->has('content'));
		$this->assertFalse($comment->has('model'));
	}

	/**
	 * Test entity hasValue() method
	 *
	 * @return void
	 */
	public function testHasValue(): void {
		$comment = new Comment([
			'content' => 'Test',
			'name' => '',
		]);

		$this->assertTrue($comment->hasValue('content'));
		$this->assertFalse($comment->hasValue('name'));
		$this->assertFalse($comment->hasValue('nonexistent'));
	}

	/**
	 * Test accessible fields
	 *
	 * @return void
	 */
	public function testAccessible(): void {
		$comment = new Comment();

		$this->assertFalse($comment->isAccessible('id'));
		$this->assertTrue($comment->isAccessible('content'));
		$this->assertTrue($comment->isAccessible('model'));
		$this->assertTrue($comment->isAccessible('foreign_key'));
	}

}
