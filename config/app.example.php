<?php

use Cake\Http\ServerRequest;

return [
	'Comments' => [
		'modelClass' => null, // Auto-detect
		'commentClass' => 'Comments.Comments',
		'nameField' => 'name',
		'emailField' => 'email', // Set to false to only use logged-in commenting
		'userModelAlias' => 'Users',
		'userModelClass' => 'Users', // Set to false to only use guest commenting
		'userModel' => null,

		// Session key used to look up the current user id when neither the
		// TinyAuth AuthUser nor the legacy Auth component is loaded. The user id
		// is read from "<sessionKey>.<userIdField>" in the session. Default is
		// 'Auth.User'; use 'Auth' for CakeDC/Users.
		'sessionKey' => 'Auth.User',
		// Session/identity sub-key holding the user id. Combined with sessionKey
		// (e.g. 'Auth.User.id') for guest/session lookups, and passed to
		// AuthUser->user()/Auth->user() when those components are present.
		'userIdField' => 'id',
		// Allow comments from non-logged-in (anonymous) visitors via the public
		// CommentsController::add() action. When false, anonymous posts are
		// rejected with a 403. Default false.
		'allowAnonymous' => false,

		// Admin access gate. REQUIRED — the host app MUST set this to a Closure
		// that receives the current request and returns literal true to grant
		// access to /admin/comments/...; anything else (unset, non-Closure,
		// returns false, returns a truthy non-bool, or throws) yields a 403.
		// The same gate also authorizes deleting another user's comment via the
		// public delete() action (an owner can always delete their own). The
		// default policy is deny.
		// Example — admin role check on the cakephp/authentication identity:
		'adminAccess' => function (ServerRequest $request): bool {
			$identity = $request->getAttribute('identity');

			return $identity !== null && in_array('admin', (array)$identity->roles, true);
		},

		'countComments' => false,
		'fieldCounter' => 'comments_count', //TODO
		'titleField' => null, // Auto-detect "title" //TODO
		'spamField' => null, // Auto-detect "is_spam" //TODO
		'hiddenField' => null, // Auto-detect "is_hidden" //TODO
		'approval' => false, // Set to true if you want to allow users to approve comments (uses hiddenField then) //TODO
		'threaded' => null, // Auto-detect "parent_id" //TODO
		// Whitelist of models allowed to use the separate controller (necessary when e.g. PRG component is in place).
		// Key is the alias used in URLs/helper, value is the model class to load.
		// Example: 'Posts' => 'Posts' or 'Articles' => 'Blog.Articles'
		'controllerModels' => [
			//'Posts' => 'Posts',
		],
	],
];
