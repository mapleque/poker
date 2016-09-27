<?php

require __DIR__ . '/../../core/base.php';
$req = Base::getRequestJson();
$user_id = $req['user_id'];
if (!isset($user_id)) {
	Base::dieWithError(ERROR_NOT_LOGIN);
}
$set_id = Texas::create($user_id);

if ($set_id > 0) {
	Base::dieWithResponse([ 'set_id' => $set_id ]);
}
Base::dieWithError(ERROR_INTERNAL);
