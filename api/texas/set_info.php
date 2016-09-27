<?php

require __DIR__ . '/../../core/base.php';
$req = Base::getRequestJson();
$user_id = $req['user_id'];
if (!isset($user_id)) {
	Base::dieWithError(ERROR_NOT_LOGIN);
}
if (!isset($req['set_id']) {
	Base::dieWithError(ERROR_INVALID_REQUEST);
}
$set_info = Texas::info($user_id, $req['set_id']);
if (isset($set_info)) {
	Base::dieWithResponse($set_info);
}
Base::dieWithError(ERROR_INTERNAL);

