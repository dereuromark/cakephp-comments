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
	protected CommentsTable $Comments;

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
	 * Test initialize method
	 *
	 * @uses \Comments\Model\Table\CommentsTable::initialize()
	 * @return void
	 */
	public function testInitialize(): void {
		$this->assertSame('comments_comments', $this->Comments->getTable());
		$this->assertSame('content', $this->Comments->getDisplayField());
		$this->assertSame('id', $this->Comments->getPrimaryKey());

		$this->assertTrue($this->Comments->hasBehavior('Timestamp'));
		$this->assertTrue($this->Comments->hasAssociation('ParentComments'));
		$this->assertTrue($this->Comments->hasAssociation('ChildComments'));
		$this->assertTrue($this->Comments->hasAssociation('Users'));
	}

	/**
	 * Test ParentComments association
	 *
	 * @return void
	 */
	public function testParentCommentsAssociation(): void {
		$association = $this->Comments->getAssociation('ParentComments');
		$this->assertSame('Comments.Comments', $association->getClassName());
		$this->assertSame('parent_id', $association->getForeignKey());
	}

	/**
	 * Test ChildComments association
	 *
	 * @return void
	 */
	public function testChildCommentsAssociation(): void {
		$association = $this->Comments->getAssociation('ChildComments');
		$this->assertSame('Comments.Comments', $association->getClassName());
		$this->assertSame('parent_id', $association->getForeignKey());
	}

	/**
	 * Test Users association
	 *
	 * @return void
	 */
	public function testUsersAssociation(): void {
		$this->assertTrue($this->Comments->hasAssociation('Users'));
	}

	/**
	 * Test validationDefault method - content required
	 *
	 * @uses \Comments\Model\Table\CommentsTable::validationDefault()
	 * @return void
	 */
	public function testValidationContentRequired(): void {
		$data = [
			'foreign_key' => 1,
			'model' => 'Posts',
		];
		$entity = $this->Comments->newEntity($data);

		$this->assertTrue($entity->hasErrors());
		$this->assertArrayHasKey('content', $entity->getErrors());
	}

	/**
	 * Test validationDefault method - model required
	 *
	 * @uses \Comments\Model\Table\CommentsTable::validationDefault()
	 * @return void
	 */
	public function testValidationModelRequired(): void {
		$data = [
			'foreign_key' => 1,
			'content' => 'Test',
		];
		$entity = $this->Comments->newEntity($data);

		$this->assertTrue($entity->hasErrors());
		$this->assertArrayHasKey('model', $entity->getErrors());
	}

	/**
	 * Test validationDefault method - foreign_key required
	 *
	 * @uses \Comments\Model\Table\CommentsTable::validationDefault()
	 * @return void
	 */
	public function testValidationForeignKeyRequired(): void {
		$data = [
			'model' => 'Posts',
			'content' => 'Test',
		];
		$entity = $this->Comments->newEntity($data);

		$this->assertTrue($entity->hasErrors());
		$this->assertArrayHasKey('foreign_key', $entity->getErrors());
	}

	/**
	 * Test validationDefault method - email validation
	 *
	 * @uses \Comments\Model\Table\CommentsTable::validationDefault()
	 * @return void
	 */
	public function testValidationEmail(): void {
		$data = [
			'foreign_key' => 1,
			'model' => 'Posts',
			'content' => 'Test',
			'email' => 'invalid-email',
		];
		$entity = $this->Comments->newEntity($data);

		$this->assertTrue($entity->hasErrors());
		$this->assertArrayHasKey('email', $entity->getErrors());
	}

	/**
	 * Test validationDefault method - valid email passes
	 *
	 * @uses \Comments\Model\Table\CommentsTable::validationDefault()
	 * @return void
	 */
	public function testValidationValidEmail(): void {
		$data = [
			'foreign_key' => 1,
			'model' => 'Posts',
			'content' => 'Test',
			'email' => 'test@example.com',
		];
		$entity = $this->Comments->newEntity($data);

		$this->assertFalse($entity->hasErrors());
	}

	/**
	 * Test validationDefault method - empty email is allowed
	 *
	 * @uses \Comments\Model\Table\CommentsTable::validationDefault()
	 * @return void
	 */
	public function testValidationEmptyEmailAllowed(): void {
		$data = [
			'foreign_key' => 1,
			'model' => 'Posts',
			'content' => 'Test',
		];
		$entity = $this->Comments->newEntity($data);

		$this->assertFalse($entity->hasErrors());
	}

	/**
	 * Test add method
	 *
	 * @uses \Comments\Model\Table\CommentsTable::add()
	 * @return void
	 */
	public function testAdd(): void {
		$data = [
			'foreign_key' => 1,
			'model' => 'Users',
			'content' => 'Foo Bar',
		];
		$result = $this->Comments->add($data);
		$this->assertFalse($result->isNew());
		$this->assertNotEmpty($result->id);
	}

	/**
	 * Test add method with optional fields
	 *
	 * @uses \Comments\Model\Table\CommentsTable::add()
	 * @return void
	 */
	public function testAddWithOptionalFields(): void {
		$data = [
			'foreign_key' => 1,
			'model' => 'Posts',
			'content' => 'Test comment',
			'name' => 'John Doe',
			'email' => 'john@example.com',
		];
		$result = $this->Comments->add($data);

		$this->assertFalse($result->isNew());
		$this->assertSame('John Doe', $result->name);
		$this->assertSame('john@example.com', $result->email);
	}

	/**
	 * Test add method with user_id
	 *
	 * @uses \Comments\Model\Table\CommentsTable::add()
	 * @return void
	 */
	public function testAddWithUserId(): void {
		$data = [
			'foreign_key' => 1,
			'model' => 'Posts',
			'content' => 'Logged in user comment',
			'user_id' => 1,
		];
		$result = $this->Comments->add($data);

		$this->assertFalse($result->isNew());
		$this->assertSame(1, $result->user_id);
	}

	/**
	 * Test add method with invalid data
	 *
	 * @uses \Comments\Model\Table\CommentsTable::add()
	 * @return void
	 */
	public function testAddInvalid(): void {
		$data = [
			'foreign_key' => 1,
			'model' => 'Users',
		];
		$result = $this->Comments->add($data);
		$this->assertTrue($result->isNew());
		$this->assertEmpty($result->id);
	}

	/**
	 * Test add method with invalid email
	 *
	 * @uses \Comments\Model\Table\CommentsTable::add()
	 * @return void
	 */
	public function testAddInvalidEmail(): void {
		$data = [
			'foreign_key' => 1,
			'model' => 'Posts',
			'content' => 'Test',
			'email' => 'not-an-email',
		];
		$result = $this->Comments->add($data);

		$this->assertTrue($result->isNew());
		$this->assertTrue($result->hasErrors());
	}

	/**
	 * Test buildRules - parent_id exists check
	 *
	 * @uses \Comments\Model\Table\CommentsTable::buildRules()
	 * @return void
	 */
	public function testBuildRulesParentIdExists(): void {
		$data = [
			'foreign_key' => 1,
			'model' => 'Posts',
			'content' => 'Test',
			'parent_id' => 99999,
		];
		$entity = $this->Comments->newEntity($data);
		$result = $this->Comments->save($entity);

		$this->assertFalse($result);
		$this->assertArrayHasKey('parent_id', $entity->getErrors());
	}

	/**
	 * Test find with contain ParentComments
	 *
	 * @return void
	 */
	public function testFindWithParentComments(): void {
		$result = $this->Comments->find()
			->contain(['ParentComments'])
			->first();

		$this->assertNotNull($result);
	}

	/**
	 * Test find with contain ChildComments
	 *
	 * @return void
	 */
	public function testFindWithChildComments(): void {
		$result = $this->Comments->find()
			->contain(['ChildComments'])
			->first();

		$this->assertNotNull($result);
	}

}
