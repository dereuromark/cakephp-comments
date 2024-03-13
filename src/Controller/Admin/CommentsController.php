<?php
declare(strict_types=1);

namespace Comments\Controller\Admin;

use App\Controller\AppController;

/**
 * @property \Comments\Model\Table\CommentsTable $Comments
 * @method \Comments\Model\Entity\Comment[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class CommentsController extends AppController {

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
				$this->Flash->success(__('The comment has been saved.'));

				return $this->redirect(['action' => 'index']);
			}
			$this->Flash->error(__('The comment could not be saved. Please, try again.'));
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
			$this->Flash->success(__('The comment has been deleted.'));
		} else {
			$this->Flash->error(__('The comment could not be deleted. Please, try again.'));
		}

		return $this->redirect(['action' => 'index']);
	}

}
