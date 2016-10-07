<?php

DB::exec('CREATE TABLE set_info (
	id				INT UNSIGNED AUTO_INCREMENT PRIMARY KEY NOT NULL,
	rule			VARCHAR(32) NOT NULL,			# 规则
	status			CHAR(1) NOT NULL,				# 状态 SLS_*
	card_stack		TEXT,							# 本局牌堆
	action_stack	TEXT,							# 用户操作队列
	pool			TEXT,							# 公共牌池
	win_info		TEXT,							# 本盘结算信息
	time			DATETIME NOT NULL
)');

