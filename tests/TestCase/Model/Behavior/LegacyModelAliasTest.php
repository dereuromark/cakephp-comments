<?php
declare(strict_types=1);

namespace Comments\Test\TestCase\Model\Behavior;

use ArrayObject;
use Cake\Controller\ComponentRegistry;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Event\EventInterface;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\ServerRequest;
use Cake\ORM\Table;
use Cake\TestSuite\TestCase;
use Comments\Controller\Component\CommentComponent;
use Comments\Utility\ModelIdentifier;
use InvalidArgumentException;
use ReflectionProperty;
use TestApp\Controller\AppController;
use TestApp\Model\Table\PostsTable;

class LegacyModelAliasTest extends TestCase {

	/**
	 * @var list<string>
	 */
	protected array $fixtures = ['plugin.Comments.Comments', 'plugin.Comments.Posts', 'plugin.Comments.Users'];

	/**
	 * @var \Cake\ORM\Table
	 */
	protected Table $posts;

	/**
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->posts = $this->getTableLocator()->get('Blog.Posts', ['className' => PostsTable::class]);
		$this->posts->addBehavior('Comments.Commentable');
		$this->assertSame('Posts', $this->posts->getAlias());
		$this->assertSame('Blog.Posts', $this->posts->getRegistryAlias());
	}

	/**
	 * @return void
	 */
	public function testDefaultFinderUsesOnlyTableAlias(): void {
		$comments = $this->fetchTable('Comments.Comments');
		$comments->add(['model' => 'Blog.Posts', 'foreign_key' => 1, 'content' => 'Legacy comment']);
		$comments->add(['model' => 'Other.Posts', 'foreign_key' => 1, 'content' => 'Other plugin']);
		$comments->add(['model' => 'Blog.Posts', 'foreign_key' => 2, 'content' => 'Other record']);

		$post = $this->posts->find('comments', id: 1)->where(['Posts.id' => 1])->firstOrFail();

		$this->assertCount(1, $post->get('comments'));
		$this->assertEqualsCanonicalizing(['Posts'], array_column($post->get('comments'), 'model'));
	}

	/**
	 * @return void
	 */
	public function testConfiguredFlatWidgetUsesOnlyExplicitLegacyIdentifier(): void {
		$this->posts->removeBehavior('Commentable');
		$this->posts->associations()->remove('Comments');
		$this->posts->addBehavior('Comments.Commentable', ['model' => 'Blog.Posts']);
		$comments = $this->fetchTable('Comments.Comments');
		$comments->add(['model' => 'Blog.Posts', 'foreign_key' => 1, 'content' => 'Legacy widget comment']);
		$comments->add(['model' => 'Other.Posts', 'foreign_key' => 1, 'content' => 'Other plugin']);
		$comments->add(['model' => 'Blog.Posts', 'foreign_key' => 2, 'content' => 'Other record']);
		$controller = new class (new ServerRequest(['params' => ['controller' => 'Posts', 'action' => 'view']])) extends AppController {
			protected ?string $defaultTable = 'Blog.Posts';
		};
		$component = new CommentComponent(new ComponentRegistry($controller));
		$component->startup(new Event('Controller.startup', $controller));

		$result = $component->callbackFetchDataFlat(['id' => 1]);

		$this->assertCount(1, $result);
		$this->assertEqualsCanonicalizing(['Blog.Posts'], array_column(iterator_to_array($result), 'model'));
	}

	/**
	 * @return void
	 */
	public function testConfiguredReplyAndFinderUseLegacyIdentifier(): void {
		$this->posts->removeBehavior('Commentable');
		$this->posts->associations()->remove('Comments');
		$this->posts->addBehavior('Comments.Commentable', ['model' => 'Blog.Posts']);
		$parent = $this->fetchTable('Comments.Comments')->add(['model' => 'Blog.Posts', 'foreign_key' => 1, 'content' => 'Legacy parent']);
		$behavior = $this->posts->getBehavior('Commentable');

		$id = $behavior->commentAdd($parent->get('id'), ['model' => $behavior->getConfig('model'), 'modelId' => 1, 'data' => ['content' => 'New reply']]);

		$this->assertNotNull($id);
		$reply = $this->fetchTable('Comments.Comments')->get($id);
		$this->assertSame('Blog.Posts', $reply->get('model'));
		$this->assertSame($parent->get('id'), $reply->get('parent_id'));
		$post = $behavior->findThreaded($this->posts->find())->where(['Posts.id' => 1])->firstOrFail();
		$legacyParents = array_values(array_filter($post->get('comments'), static fn ($comment) => $comment->get('id') === $parent->get('id')));
		$this->assertCount(1, $legacyParents);
		$this->assertSame($id, $legacyParents[0]->get('children')[0]->get('id'));
	}

	/**
	 * @return void
	 */
	public function testReplyRejectsSameRecordInAnotherPlugin(): void {
		$parent = $this->fetchTable('Comments.Comments')->add(['model' => 'Other.Posts', 'foreign_key' => 1, 'content' => 'Other plugin parent']);
		$this->expectException(MethodNotAllowedException::class);

		$this->posts->getBehavior('Commentable')->commentAdd($parent->get('id'), ['model' => 'Posts', 'modelId' => 1, 'data' => ['content' => 'Blocked reply']]);
	}

	/**
	 * @return void
	 */
	public function testReplyRejectsLegacyParentOnAnotherRecord(): void {
		$parent = $this->fetchTable('Comments.Comments')->add(['model' => 'Blog.Posts', 'foreign_key' => 2, 'content' => 'Other record parent']);
		$this->expectException(MethodNotAllowedException::class);

		$this->posts->getBehavior('Commentable')->commentAdd($parent->get('id'), ['model' => 'Posts', 'modelId' => 1, 'data' => ['content' => 'Blocked reply']]);
	}

	/**
	 * @return void
	 */
	public function testTrustedDirectCallerRetainsItsExplicitModelIdentifier(): void {
		$parent = $this->fetchTable('Comments.Comments')->add(['model' => 'CustomTarget', 'foreign_key' => 1, 'content' => 'Custom target parent']);
		$id = $this->posts->getBehavior('Commentable')->commentAdd($parent->get('id'), ['model' => 'CustomTarget', 'modelId' => 1, 'data' => ['content' => 'Custom target reply']]);

		$this->assertNotNull($id);
		$reply = $this->fetchTable('Comments.Comments')->get($id);
		$this->assertSame('CustomTarget', $reply->get('model'));
		$this->assertSame($parent->get('id'), $reply->get('parent_id'));
	}

	/**
	 * @return void
	 */
	public function testDistinctLocatorAliasesDoNotShareCommentsOnSamePhysicalTable(): void {
		$other = $this->getTableLocator()->get('OtherPosts', ['className' => PostsTable::class]);
		$other->addBehavior('Comments.Commentable');
		$this->assertSame($this->posts->getTable(), $other->getTable());
		$this->fetchTable('Comments.Comments')->add(['model' => 'OtherPosts', 'foreign_key' => 1, 'content' => 'Separate alias']);
		$this->assertSame(['Posts'], array_column($this->posts->get(1, contain: ['Comments'])->get('comments'), 'model'));
		$this->assertSame(['OtherPosts'], array_column($other->get(1, contain: ['Comments'])->get('comments'), 'model'));
	}

	/**
	 * @return void
	 */
	public function testComponentWritesAndRepliesWithConfiguredIdentifier(): void {
		$this->posts->removeBehavior('Commentable');
		$this->posts->associations()->remove('Comments');
		$this->posts->addBehavior('Comments.Commentable', ['model' => 'Blog.Posts']);
		$parent = $this->fetchTable('Comments.Comments')->add(['model' => 'Blog.Posts', 'foreign_key' => 1, 'content' => 'Configured parent']);
		$request = new ServerRequest(['params' => ['controller' => 'Posts', 'action' => 'view', 'pass' => [1]]]);
		$request = $request->withHeader('X-Requested-With', 'XMLHttpRequest')->withMethod('POST')->withParsedBody(['content' => 'Configured component reply', 'parent_id' => $parent->id]);
		$controller = new class ($request) extends AppController {
			protected ?string $defaultTable = 'Blog.Posts';
		};
		$component = new CommentComponent(new ComponentRegistry($controller), ['allowAnonymous' => true]);
		$component->startup(new Event('Controller.startup', $controller));
		$controller->set('post', $this->posts->get(1));
		$component->beforeRender(new Event('Controller.beforeRender', $controller));
		$reply = $this->fetchTable('Comments.Comments')->find()->where(['content' => 'Configured component reply'])->firstOrFail();
		$this->assertSame('Blog.Posts', $reply->model);
		$this->assertSame($parent->id, $reply->parent_id);
		$component->callbackPrepareParams();
		$this->assertSame('Blog.Posts', (new ReflectionProperty($component, 'commentParams'))->getValue($component)['modelName']);
	}

	/**
	 * @return void
	 */
	public function testNullDirectModelPreservesReleasedValidationFailure(): void {
		$comments = $this->fetchTable('Comments.Comments');
		$count = $comments->find()->count();
		foreach ([[], ['model' => null], ['model' => '']] as $modelOptions) {
			$this->assertNull($this->posts->getBehavior('Commentable')->commentAdd(1, $modelOptions + ['modelId' => 1, 'data' => ['content' => 'Missing target']]));
		}
		$this->assertSame($count, $comments->find()->count());
	}

	/**
	 * @return void
	 */
	public function testMissingParentStillRejectsOmittedModel(): void {
		$this->expectException(MethodNotAllowedException::class);
		$this->posts->getBehavior('Commentable')->commentAdd(999, ['modelId' => 1, 'data' => ['content' => 'Missing parent']]);
	}

	/**
	 * @return void
	 */
	public function testMarshaledModelCanCompleteDirectReply(): void {
		$comments = $this->fetchTable('Comments.Comments');
		$comments->getEventManager()->on('Model.beforeMarshal', function (EventInterface $event, ArrayObject $data): void {
			$data['model'] = 'Posts';
		});
		$id = $this->posts->getBehavior('Commentable')->commentAdd(1, ['modelId' => 1, 'userId' => 1, 'data' => ['content' => 'Marshaled target']]);
		$this->assertNotNull($id);
		$reply = $comments->get($id);
		$this->assertSame('Posts', $reply->model);
		$this->assertSame(1, $reply->parent_id);
	}

	/**
	 * @return void
	 */
	public function testMarshaledModelStillMustMatchParent(): void {
		$comments = $this->fetchTable('Comments.Comments');
		$comments->getEventManager()->on('Model.beforeMarshal', function (EventInterface $event, ArrayObject $data): void {
			$data['model'] = 'OtherPosts';
		});
		$this->expectException(MethodNotAllowedException::class);
		$this->posts->getBehavior('Commentable')->commentAdd(1, ['modelId' => 1, 'userId' => 1, 'data' => ['content' => 'Wrong marshaled target']]);
	}

	/**
	 * @return void
	 */
	public function testGlobalDefaultReachesAutoAttachedBehaviorAndComponent(): void {
		$this->posts->removeBehavior('Commentable');
		$this->posts->associations()->remove('Comments');
		Configure::write('Comments.model', 'LegacyPosts');
		try {
			$controller = new class ((new ServerRequest(['params' => ['controller' => 'Posts', 'action' => 'view']]))->withHeader('X-Requested-With', 'XMLHttpRequest')->withMethod('POST')->withParsedBody(['content' => 'Global component write'])) extends AppController {
				protected ?string $defaultTable = 'Blog.Posts';
			};
			$component = new CommentComponent(new ComponentRegistry($controller));
			$component->startup(new Event('Controller.startup', $controller));
			$this->assertSame('LegacyPosts', $this->posts->getBehavior('Commentable')->getConfig('model'));
			$this->fetchTable('Comments.Comments')->add(['model' => 'LegacyPosts', 'foreign_key' => 1, 'content' => 'Global model comment']);
			$this->assertSame(['LegacyPosts'], array_column($this->posts->get(1, contain: ['Comments'])->get('comments'), 'model'));
			$this->assertSame(['LegacyPosts'], array_column(iterator_to_array($component->callbackFetchDataFlat(['id' => 1])), 'model'));
			$component->setConfig('allowAnonymous', true);
			$controller->set('post', $this->posts->get(1));
			$component->beforeRender(new Event('Controller.beforeRender', $controller));
			$this->assertSame('LegacyPosts', $this->fetchTable('Comments.Comments')->find()->where(['content' => 'Global component write'])->firstOrFail()->model);
		} finally {
			Configure::delete('Comments.model');
		}
	}

	/**
	 * @return void
	 */
	public function testEmptyConfiguredModelFailsDuringInitialization(): void {
		$this->posts->removeBehavior('Commentable');
		$this->posts->associations()->remove('Comments');
		$this->expectException(InvalidArgumentException::class);
		$this->posts->addBehavior('Comments.Commentable', ['model' => '']);
	}

	/**
	 * @return void
	 */
	public function testLocalModelOverridesGlobalDefault(): void {
		$this->posts->removeBehavior('Commentable');
		$this->posts->associations()->remove('Comments');
		Configure::write('Comments.model', 'LegacyPosts');
		try {
			$this->posts->addBehavior('Comments.Commentable', ['model' => 'Posts']);
			$this->assertSame('Posts', $this->posts->getBehavior('Commentable')->getConfig('model'));
			$this->assertSame(['Posts'], array_column($this->posts->get(1, contain: ['Comments'])->get('comments'), 'model'));
		} finally {
			Configure::delete('Comments.model');
		}
	}

	/**
	 * @return void
	 */
	public function testInvalidGlobalIdentifierIsRejectedWithoutBehavior(): void {
		$this->posts->removeBehavior('Commentable');
		Configure::write('Comments.model', '');
		try {
			$this->expectException(InvalidArgumentException::class);
			ModelIdentifier::resolve($this->posts);
		} finally {
			Configure::delete('Comments.model');
		}
	}

	/**
	 * @return void
	 */
	public function testComponentSubclassRetainsModelAliasOverrideAfterStartup(): void {
		$controller = new class ((new ServerRequest(['params' => ['controller' => 'Posts', 'action' => 'view']]))->withHeader('X-Requested-With', 'XMLHttpRequest')->withMethod('POST')->withParsedBody(['content' => 'Subclass target'])) extends AppController {
			protected ?string $defaultTable = 'Blog.Posts';
		};
		$component = new class (new ComponentRegistry($controller), ['allowAnonymous' => true]) extends CommentComponent {
			/**
			 * @return void
			 */
			public function selectTarget(): void {
				$this->modelAlias = 'CustomTarget';
			}
		};
		$component->startup(new Event('Controller.startup', $controller));
		$component->selectTarget();
		$controller->set('post', $this->posts->get(1));
		$component->beforeRender(new Event('Controller.beforeRender', $controller));
		$this->assertSame('CustomTarget', $this->fetchTable('Comments.Comments')->find()->where(['content' => 'Subclass target'])->firstOrFail()->model);
		$this->assertSame(['CustomTarget'], array_column(iterator_to_array($component->callbackFetchDataFlat(['id' => 1])), 'model'));
		$component->callbackPrepareParams();
		$this->assertSame('CustomTarget', (new ReflectionProperty($component, 'commentParams'))->getValue($component)['modelName']);
	}

}
