<?php

DB::exec('CREATE TABLE user_log (
	id			INT UNSIGNED AUTO_INCREMENT PRIMARY KEY NOT NULL,
	user_id		INT UNSIGNED,
	set_id		INT UNSIGNED,
	status		CHAR(1) NOT NULL,			# SLS_* 当前游戏状态
	hand		TEXT,						# 玩家手牌
	pool		TEXT,						# 玩家弃牌
	score		INT UNSIGNED,				# 玩家筹码
	time		DATETIME NOT NULL
)');

DB::exec('ALTER TABLE user_log ADD INDEX (user_id), ADD INDEX (set_id)');
