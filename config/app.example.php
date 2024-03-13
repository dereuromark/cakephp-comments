<?php

return [
	'Comments' => [
		'modelClass' => null, // Auto-detect
		'commentClass' => 'Comments.Comments',
		'nameField' => 'name',
		'emailField' => 'email', // Set to false to only use logged-in commenting
		'userModelAlias' => 'Users',
		'userModelClass' => 'Users', // Set to false to only use guest commenting
		'userModel' => null,
		'countComments' => false,
		'fieldCounter' => 'comments_count', //TODO
		'titleField' => null, // Auto-detect "title" //TODO
		'spamField' => null, // Auto-detect "is_spam" //TODO
		'hiddenField' => null, // Auto-detect "is_hidden" //TODO
		'approval' => false, // Set to true if you want to allow users to approve comments (uses hiddenField then) //TODO
		'threaded' => null, // Auto-detect "parent_id" //TODO
		// The following are allowed to use the separate controller, necessary when e.g. PRG component is in place
		'controllerModels' => [
			'Alias' => 'MyPlugin.MyModel',
		],
	],
];
