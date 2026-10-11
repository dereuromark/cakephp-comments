<?php

use Cake\Http\ServerRequest;

return [
	'Comments' => [
		// URL alias => application table class for the public add action.
		'controllerModels' => [
			//'Posts' => 'Posts',
		],
		// Applies to public creation and the Comment component.
		'allowAnonymous' => false,
		// Request identity is checked before auth components and this session fallback.
		'sessionKey' => 'Auth.User',
		'userIdField' => 'id',
		// Replace this callback with your application's admin policy.
		// Literal true grants admin access and elevated public deletion.
		'adminAccess' => function (ServerRequest $request): bool {
			return false;
		},
	],
];
