<?php

require __DIR__ . '/../../core/base.php';
$req = Base::getRequestJson();
$user_id = $req['user_id'];
if (!isset($user_id)) {
	Base::dieWithError(ERROR_NOT_LOGIN);
}
if (!isset($req['amount']) {
	Base::dieWithError(ERROR_INVALID_REQUEST);
}
if (!isset($req['set_id']) {
	Base::dieWithError(ERROR_INVALID_REQUEST);
}
if (Texas::add($user_id, $req['set_id'], $req['amount'])) {
	Base::dieWithResponse();
}
Base::dieWithError(ERROR_INTERNAL);

