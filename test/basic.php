<?php

require __DIR__ . '/../core/base.php';

$index_array = range(0,51);
dump(count($index_array));
$card_stack = Poker::init($index_array);
dump_card($card_stack);
echo "\n";
dump(Poker::get(2, $card_stack));
