<?php
declare(strict_types=1);

namespace Comments\Test\TestCase\Model\Behavior;

use Cake\Core\Configure;
use Cake\ORM\Table;
use Cake\TestSuite\TestCase;
use Comments\Model\Behavior\CommentableBehavior;

/**
 * Comments\Model\Behavior\CommentableBehavior Test Case
 */
class CommentableBehaviorTest extends TestCase {

	/**
	 * @var \Cake\ORM\Table
	 */
	protected Table $Posts;

	/**
	 * @var \Comments\Model\Behavior\CommentableBehavior
	 */
	protected CommentableBehavior $Commentable;

	/**
	 * Fixtures
	 *
	 * @var list<string>
	 */
	protected array $fixtures = [
		'plugin.Comments.Comments',
		'plugin.Comments.Posts',
		'plugin.Comments.Users',
	];

	/**
	 * setUp method
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->Posts = $this->getTableLocator()->get('Posts');
		$this->Posts->addBehavior('Comments.Commentable');
		$this->Commentable = $this->Posts->behaviors()->get('Commentable');
	}

	/**
	 * tearDown method
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		unset($this->Posts, $this->Commentable);
		Configure::delete('Comments');

		parent::tearDown();
	}

	/**
	 * Test initialize sets up Comments association
	 *
	 * @return void
	 */
	public function testInitializeCreatesCommentsAssociation(): void {
		$this->assertTrue($this->Posts->hasAssociation('Comments'));
		$association = $this->Posts->getAssociation('Comments');
		$this->assertSame('Comments.Comments', $association->getClassName());
		$this->assertSame('foreign_key', $association->getForeignKey());
	}

	/**
	 * Test initialize sets up model alias correctly
	 *
	 * @return void
	 */
	public function testInitializeModelAlias(): void {
		$this->assertSame('Posts', $this->Commentable->getConfig('modelClass'));
	}

	/**
	 * Test initialize with custom config
	 *
	 * @return void
	 */
	public function testInitializeWithCustomConfig(): void {
		$table = $this->getTableLocator()->get('CustomPosts', [
			'className' => Table::class,
			'table' => 'posts',
		]);
		$table->addBehavior('Comments.Commentable', [
			'commentClass' => 'Comments.Comments',
			'userModelAlias' => 'Authors',
			'userModelClass' => 'Authors',
		]);

		$behavior = $table->behaviors()->get('Commentable');
		$this->assertSame('Authors', $behavior->getConfig('userModelAlias'));
		$this->assertSame('Authors', $behavior->getConfig('userModelClass'));
	}

	/**
	 * Test that Configure settings are merged into behavior config
	 *
	 * @return void
	 */
	public function testConfigureMerge(): void {
		Configure::write('Comments.userModelAlias', 'Members');

		$table = $this->getTableLocator()->get('ConfigPosts', [
			'className' => Table::class,
			'table' => 'posts',
		]);
		$table->addBehavior('Comments.Commentable');

		$behavior = $table->behaviors()->get('Commentable');
		$this->assertSame('Members', $behavior->getConfig('userModelAlias'));
	}

	/**
	 * Test commentAdd adds a new comment successfully
	 *
	 * @return void
	 */
	public function testCommentAdd(): void {
		$options = [
			'userId' => 1,
			'modelId' => 1,
			'model' => 'Posts',
			'data' => [
				'content' => 'This is a new comment',
			],
		];

		$result = $this->Commentable->commentAdd(null, $options);
		$this->assertNotNull($result);
		$this->assertIsInt($result);

		$comment = $this->getTableLocator()->get('Comments.Comments')->get($result);
		$this->assertSame('This is a new comment', $comment->content);
		$this->assertSame(1, $comment->user_id);
		$this->assertSame(1, $comment->foreign_key);
		$this->assertSame('Posts', $comment->model);
	}

	/**
	 * Test commentAdd with comment field instead of content
	 *
	 * @return void
	 */
	public function testCommentAddWithCommentField(): void {
		$options = [
			'userId' => 1,
			'modelId' => 1,
			'model' => 'Posts',
			'data' => [
				'comment' => 'Comment via comment field',
			],
		];

		$result = $this->Commentable->commentAdd(null, $options);
		$this->assertNotNull($result);

		$comment = $this->getTableLocator()->get('Comments.Comments')->get($result);
		$this->assertSame('Comment via comment field', $comment->content);
	}

	/**
	 * Test commentAdd with default title
	 *
	 * @return void
	 */
	public function testCommentAddWithDefaultTitle(): void {
		$options = [
			'userId' => 1,
			'modelId' => 1,
			'model' => 'Posts',
			'defaultTitle' => 'Re: Post Title',
			'data' => [
				'content' => 'Comment with title',
			],
		];

		$result = $this->Commentable->commentAdd(null, $options);
		$this->assertNotNull($result);
	}

	/**
	 * Threading is controlled by the first argument to commentAdd (the
	 * parent comment id), not by request data. A `parent_id` smuggled
	 * into `options.data` must be ignored — otherwise an attacker could
	 * thread under any parent on the same record without going through
	 * the parent-validity check.
	 *
	 * @return void
	 */
	public function testCommentAddIgnoresParentIdInData(): void {
		$commentsTable = $this->getTableLocator()->get('Comments.Comments');

		$parentId = $this->Commentable->commentAdd(null, [
			'userId' => 1,
			'modelId' => 1,
			'model' => 'Posts',
			'data' => ['content' => 'Parent comment'],
		]);
		$this->assertNotNull($parentId);

		// Threaded reply via the first arg — works.
		$childId = $this->Commentable->commentAdd($parentId, [
			'userId' => 1,
			'modelId' => 1,
			'model' => 'Posts',
			'data' => ['content' => 'Child comment'],
		]);
		$this->assertNotNull($childId);
		$this->assertSame($parentId, $commentsTable->get($childId)->parent_id);

		// Smuggled parent_id in data is ignored — top-level comment, not threaded.
		$smuggledId = $this->Commentable->commentAdd(null, [
			'userId' => 1,
			'modelId' => 1,
			'model' => 'Posts',
			'data' => [
				'content' => 'Smuggled',
				'parent_id' => $parentId,
			],
		]);
		$this->assertNotNull($smuggledId);
		$this->assertNull($commentsTable->get($smuggledId)->parent_id, 'parent_id from request data must NOT be honored.');
	}

	/**
	 * Test commentAdd rejects spam (Other field)
	 *
	 * @return void
	 */
	public function testCommentAddRejectsSpam(): void {
		$options = [
			'userId' => 1,
			'modelId' => 1,
			'model' => 'Posts',
			'data' => [
				'content' => 'Spam comment',
				'Other' => ['field1' => 'spamvalue'],
			],
		];

		$result = $this->Commentable->commentAdd(null, $options);
		$this->assertNull($result);
	}

	/**
	 * Test commentAdd returns null for empty data
	 *
	 * @return void
	 */
	public function testCommentAddEmptyData(): void {
		$options = [
			'userId' => 1,
			'modelId' => 1,
			'model' => 'Posts',
			'data' => [],
		];

		$result = $this->Commentable->commentAdd(null, $options);
		$this->assertNull($result);
	}

	/**
	 * Test findComments finder
	 *
	 * @return void
	 */
	public function testFindComments(): void {
		// Add a comment first
		$options = [
			'userId' => 1,
			'modelId' => 1,
			'model' => 'Posts',
			'data' => [
				'content' => 'Test findComments',
			],
		];
		$this->Commentable->commentAdd(null, $options);

		$query = $this->Posts->find('comments', id: 1);
		$this->assertNotNull($query);

		$result = $query->first();
		$this->assertNotNull($result);
		$this->assertTrue($result->has('comments'));
	}

	/**
	 * Test findThreaded finder
	 *
	 * @return void
	 */
	public function testFindThreaded(): void {
		// Add comments
		$options = [
			'userId' => 1,
			'modelId' => 1,
			'model' => 'Posts',
			'data' => [
				'content' => 'Test findThreaded',
			],
		];
		$this->Commentable->commentAdd(null, $options);

		$query = $this->Posts->find('threaded');
		$this->assertNotNull($query);
	}

	/**
	 * Test findComments with hidden field config
	 *
	 * @return void
	 */
	public function testFindCommentsWithHiddenField(): void {
		$table = $this->getTableLocator()->get('HiddenPosts', [
			'className' => Table::class,
			'table' => 'posts',
		]);
		$table->addBehavior('Comments.Commentable', [
			'hiddenField' => 'is_private',
		]);

		$query = $table->find('comments', id: 1);
		$this->assertNotNull($query);
	}

	/**
	 * Test default config values
	 *
	 * @return void
	 */
	public function testDefaultConfig(): void {
		$this->assertSame('Comments.Comments', $this->Commentable->getConfig('commentClass'));
		$this->assertSame('name', $this->Commentable->getConfig('nameField'));
		$this->assertSame('email', $this->Commentable->getConfig('emailField'));
		$this->assertSame('Users', $this->Commentable->getConfig('userModelAlias'));
		$this->assertFalse($this->Commentable->getConfig('countComments'));
	}

	/**
	 * Test commentAdd returns null when validation fails
	 *
	 * @return void
	 */
	public function testCommentAddValidationFails(): void {
		// Try to create a comment with invalid data (missing required content)
		$options = [
			'userId' => 1,
			'modelId' => 1,
			'model' => 'Posts',
			'data' => [
				'content' => '', // Empty content should fail validation
			],
		];

		$result = $this->Commentable->commentAdd(null, $options);
		$this->assertNull($result);
	}

	/**
	 * Test initialize with userModel as array config
	 *
	 * @return void
	 */
	public function testInitializeWithUserModelArray(): void {
		$table = $this->getTableLocator()->get('UserModelPosts', [
			'className' => Table::class,
			'table' => 'posts',
		]);
		$table->addBehavior('Comments.Commentable', [
			'userModelAlias' => 'Members',
			'userModel' => [
				'className' => 'Users',
				'foreignKey' => 'member_id',
			],
		]);

		$behavior = $table->behaviors()->get('Commentable');
		$this->assertSame('Members', $behavior->getConfig('userModelAlias'));
		$this->assertIsArray($behavior->getConfig('userModel'));
	}

	/**
	 * The previous behavior allowed `foreign_key` smuggled into
	 * `options.data` to override the caller's `modelId` — a clean IDOR
	 * because the controller's authoritative model id was silently
	 * replaced by attacker-supplied request data. The comment must now
	 * always attach to `modelId`, regardless of what `data` says.
	 *
	 * @return void
	 */
	public function testCommentAddIgnoresForeignKeyInData(): void {
		$result = $this->Commentable->commentAdd(null, [
			'userId' => 1,
			'modelId' => 1,
			'model' => 'Posts',
			'data' => [
				'content' => 'Attempted cross-record IDOR',
				'foreign_key' => 999,
			],
		]);
		$this->assertNotNull($result);

		$comment = $this->getTableLocator()->get('Comments.Comments')->get($result);
		$this->assertSame(1, $comment->foreign_key, 'foreign_key in request data must NOT override modelId.');
	}

	/**
	 * Test commentAdd with model_id in data (alternative to foreign_key)
	 *
	 * @return void
	 */
	public function testCommentAddWithModelIdInData(): void {
		$options = [
			'userId' => 1,
			'modelId' => 1,
			'model' => 'Posts',
			'data' => [
				'content' => 'Comment with model_id',
				'model_id' => 3,
			],
		];

		$result = $this->Commentable->commentAdd(null, $options);
		$this->assertNotNull($result);
	}

	/**
	 * Test commentAdd with permalink option
	 *
	 * @return void
	 */
	public function testCommentAddWithPermalink(): void {
		$options = [
			'userId' => 1,
			'modelId' => 1,
			'model' => 'Posts',
			'permalink' => 'https://example.com/posts/1',
			'data' => [
				'content' => 'Comment with permalink',
			],
		];

		$result = $this->Commentable->commentAdd(null, $options);
		$this->assertNotNull($result);
	}

	/**
	 * Test findThreaded finder with options
	 *
	 * @return void
	 */
	public function testFindThreadedWithOptions(): void {
		// Add comments first
		$options = [
			'userId' => 1,
			'modelId' => 1,
			'model' => 'Posts',
			'data' => [
				'content' => 'Test findThreaded with options',
			],
		];
		$this->Commentable->commentAdd(null, $options);

		$query = $this->Posts->find('threaded', parentField: 'parent_id');
		$this->assertNotNull($query);
	}

}
