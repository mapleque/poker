<?php

require __DIR__ . '/../core/base.php';

DB::exec('DROP TABLE IF EXISTS user_log');
DB::exec('DROP TABLE IF EXISTS set_info');
DB::exec('DROP TABLE IF EXISTS user');
include __DIR__ . '/../deploy/user.php';
include __DIR__ . '/../deploy/user_log.php';
include __DIR__ . '/../deploy/set_info.php';
$user_name1 = '1';
$user_name2 = '2';
DB::insert("INSERT INTO user (username, password) VALUES(?,?)", [ $user_name1, md5('123') ]);
DB::insert("INSERT INTO user (username, password) VALUES(?,?)", [ $user_name2, md5('123') ]);

$user_id1 = DB::select("SELECT id FROM user WHERE username = ? LIMIT 1", [ $user_name1 ])[0]['id'];
$user_id2 = DB::select("SELECT id FROM user WHERE username = ? LIMIT 1", [ $user_name2 ])[0]['id'];
echo 'user ';
dump($user_id1, $user_id2);
echo "\n";
assert($user_id1 === 1);
assert($user_id2 === 2);
echo "\n";

echo 'create set ';
$set_id = Texas::create($user_id1);
dump($set_id);
assert($set_id === 1);
echo "\n";

echo 'join set';
assert(Texas::join($user_id2, $set_id));
echo "\n";

echo 'start ';
assert(Texas::start($user_id1, $set_id));
assert(Texas::start($user_id2, $set_id));
echo "\n";

echo 'info ';
dump(Texas::info($user_id1, $set_id));
echo "\n";

