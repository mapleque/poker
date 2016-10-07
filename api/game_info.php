<?php

require __DIR__ . '/../core/base.php';

$req = Base::getRequestJson();

$user_id = $req['user_id'];
if (!isset($user_id)) {
	Base::dieWithError(ERROR_NOT_LOGIN);
}
$set_info = Game::info($user_id);
if (isset($set_info)) {
	$rule = new $set_info['rule'];
	Base::dieWithResponse($rule->info($user_id, $set_info['set_id']));
} else {
	Base::dieWithResponse([ 'list' => Game::getList() ]);
}
