<?php
declare(strict_types=1);

namespace Comments\Controller\Admin;

use App\Controller\AppController;
use Cake\Core\Configure;
use Cake\Event\EventInterface;
use Cake\Http\Exception\ForbiddenException;
use Cake\Log\Log;
use Closure;
use Throwable;

/**
 * Admin namespace for the Comments plugin.
 *
 * The default policy is **deny**: the host application MUST set
 * `Comments.adminAccess` to a `Closure` that receives the current request
 * and returns literal `true` to grant access. Anything else (unset,
 * non-Closure, returns false, returns a truthy non-bool, or throws) yields
 * a 403. (Patterned after Queue.adminAccess.)
 *
 * ```php
 * Configure::write('Comments.adminAccess', function (\Cake\Http\ServerRequest $request): bool {
 *     $identity = $request->getAttribute('identity');
 *     return $identity !== null && in_array('admin', (array)$identity->roles, true);
 * });
 * ```
 *
 * @property \Comments\Model\Table\CommentsTable $Comments
 * @method \Comments\Model\Entity\Comment[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class CommentsController extends AppController {

	/**
	 * @param \Cake\Event\EventInterface<\Cake\Controller\Controller> $event
	 *
	 * @throws \Cake\Http\Exception\ForbiddenException When access is denied or unconfigured.
	 *
	 * @return void
	 */
	public function beforeFilter(EventInterface $event): void {
		parent::beforeFilter($event);

		// Coexist with cakephp/authorization: the gate IS the authorization decision
		// for these controllers, so silence the policy check.
		if ($this->components()->has('Authorization') && method_exists($this->components()->get('Authorization'), 'skipAuthorization')) {
			$this->components()->get('Authorization')->skipAuthorization();
		}

		$gate = Configure::read('Comments.adminAccess');
		if (!($gate instanceof Closure)) {
			throw new ForbiddenException(__d(
				'comments',
				'Comments admin backend is not configured. Set Comments.adminAccess to a Closure that returns true for permitted callers.',
			));
		}

		try {
			$allowed = $gate($this->request) === true;
		} catch (ForbiddenException $e) {
			throw $e;
		} catch (Throwable $e) {
			Log::warning(sprintf('Comments.adminAccess threw %s: %s', $e::class, $e->getMessage()));

			throw new ForbiddenException(__d('comments', 'Comments admin access denied.'));
		}

		if (!$allowed) {
			throw new ForbiddenException(__d('comments', 'Comments admin access denied.'));
		}
	}

	/**
	 * @return \Cake\Http\Response|null|void Renders view
	 */
	public function index() {
		$query = $this->Comments->find()
			->contain(['ParentComments', 'Users']);
		$comments = $this->paginate($query);

		$this->set(compact('comments'));
	}

	/**
	 * @param string|null $id Comment id.
	 *
	 * @return \Cake\Http\Response|null|void Renders view
	 */
	public function view($id = null) {
		$comment = $this->Comments->get($id, contain: ['ParentComments', 'Users', 'ChildComments']);
		$this->set(compact('comment'));
	}

	/**
	 * @param string|null $id Comment id.
	 *
	 * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
	 */
	public function edit($id = null) {
		$comment = $this->Comments->get($id, contain: []);
		if ($this->request->is(['patch', 'post', 'put'])) {
			$comment = $this->Comments->patchEntity($comment, $this->request->getData());
			if ($this->Comments->save($comment)) {
				$this->Flash->success(__d('comments', 'The comment has been saved.'));

				return $this->redirect(['action' => 'index']);
			}
			$this->Flash->error(__d('comments', 'The comment could not be saved. Please, try again.'));
		}
		$parentComments = $this->Comments->ParentComments->find('list', limit: 1000)->all();
		$this->set(compact('comment', 'parentComments'));
	}

	/**
	 * @param string|null $id Comment id.
	 *
	 * @return \Cake\Http\Response|null Redirects to index.
	 */
	public function delete($id = null) {
		$this->request->allowMethod(['post', 'delete']);
		$comment = $this->Comments->get($id);
		if ($this->Comments->delete($comment)) {
			$this->Flash->success(__d('comments', 'The comment has been deleted.'));
		} else {
			$this->Flash->error(__d('comments', 'The comment could not be deleted. Please, try again.'));
		}

		return $this->redirect(['action' => 'index']);
	}

}
