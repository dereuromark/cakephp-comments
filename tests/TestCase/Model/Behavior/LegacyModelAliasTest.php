<?php
declare(strict_types=1);

namespace Comments\Test\TestCase\Model\Behavior;

use Cake\Controller\ComponentRegistry;
use Cake\Event\Event;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\ServerRequest;
use Cake\ORM\Table;
use Cake\TestSuite\TestCase;
use Comments\Controller\Component\CommentComponent;
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
	public function testFinderIncludesLegacyIdentifierWithoutOtherPluginsOrRecords(): void {
		$comments = $this->fetchTable('Comments.Comments');
		$comments->add(['model' => 'Blog.Posts', 'foreign_key' => 1, 'content' => 'Legacy comment']);
		$comments->add(['model' => 'Other.Posts', 'foreign_key' => 1, 'content' => 'Other plugin']);
		$comments->add(['model' => 'Blog.Posts', 'foreign_key' => 2, 'content' => 'Other record']);

		$post = $this->posts->find('comments', id: 1)->where(['Posts.id' => 1])->firstOrFail();

		$this->assertCount(2, $post->get('comments'));
		$this->assertEqualsCanonicalizing(['Posts', 'Blog.Posts'], array_column($post->get('comments'), 'model'));
	}

	/**
	 * @return void
	 */
	public function testFlatWidgetIncludesLegacyIdentifierWithoutOtherPluginsOrRecords(): void {
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

		$this->assertCount(2, $result);
		$this->assertEqualsCanonicalizing(['Posts', 'Blog.Posts'], array_column(iterator_to_array($result), 'model'));
	}

	/**
	 * @return void
	 */
	public function testCanonicalReplyCanUseLegacyParentAndThreadedFinder(): void {
		$parent = $this->fetchTable('Comments.Comments')->add(['model' => 'Blog.Posts', 'foreign_key' => 1, 'content' => 'Legacy parent']);
		$behavior = $this->posts->getBehavior('Commentable');

		$id = $behavior->commentAdd($parent->get('id'), ['model' => 'Posts', 'modelId' => 1, 'data' => ['content' => 'New reply']]);

		$this->assertNotNull($id);
		$reply = $this->fetchTable('Comments.Comments')->get($id);
		$this->assertSame('Posts', $reply->get('model'));
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

}
