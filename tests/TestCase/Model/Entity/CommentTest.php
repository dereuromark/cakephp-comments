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
	 * Public-form fields are mass-assignable; everything else (id,
	 * identity, relational, moderation flags) must be set explicitly via
	 * `set()` / `patchEntity(..., accessibleFields: ...)`.
	 *
	 * @return void
	 */
	public function testOnlyPublicFieldsMassAssignable(): void {
		$comment = new Comment([
			'id' => 999,
			'name' => 'John Doe',
			'email' => 'john@example.com',
			'title' => 'My Title',
			'content' => 'Test comment',
			'model' => 'Posts',
			'foreign_key' => 1,
			'parent_id' => 5,
			'user_id' => 42,
			'is_private' => true,
			'is_spam' => true,
		], ['guard' => true]);

		$this->assertNull($comment->id, 'id must never be mass-assignable.');
		$this->assertSame('John Doe', $comment->name);
		$this->assertSame('john@example.com', $comment->email);
		$this->assertSame('My Title', $comment->title);
		$this->assertSame('Test comment', $comment->content);

		// Relational / identity / moderation columns must NOT be mass-assigned.
		$this->assertNull($comment->model);
		$this->assertNull($comment->foreign_key);
		$this->assertNull($comment->parent_id);
		$this->assertNull($comment->user_id);
		$this->assertNull($comment->is_private);
		$this->assertNull($comment->is_spam);
	}

	/**
	 * Server-trusted code can still set the protected columns explicitly
	 * via `set()` or via an `accessibleFields` override on patchEntity.
	 *
	 * @return void
	 */
	public function testRelationalColumnsAssignableViaSet(): void {
		$comment = new Comment();
		$comment->set('model', 'Articles');
		$comment->set('foreign_key', 123);
		$comment->set('user_id', 42);
		$comment->set('parent_id', 5);

		$this->assertSame('Articles', $comment->model);
		$this->assertSame(123, $comment->foreign_key);
		$this->assertSame(42, $comment->user_id);
		$this->assertSame(5, $comment->parent_id);
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
		$this->assertTrue($comment->isAccessible('title'));
		$this->assertTrue($comment->isAccessible('name'));
		$this->assertTrue($comment->isAccessible('email'));

		$this->assertFalse($comment->isAccessible('model'), 'Identity columns must not be mass-assignable.');
		$this->assertFalse($comment->isAccessible('foreign_key'));
		$this->assertFalse($comment->isAccessible('user_id'));
		$this->assertFalse($comment->isAccessible('parent_id'));
		$this->assertFalse($comment->isAccessible('is_private'));
		$this->assertFalse($comment->isAccessible('is_spam'));
	}

}
