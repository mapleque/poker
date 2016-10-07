<?php
class Poker
{
	public static function init($index_array)
	{
		$ret = [];
		foreach ($index_array as $index) {
			$type = floor($index / 13);
			$value = $index % 13;
			$ret[] = [
				'index' => $index,
				'type' => $type,
				'value' => $value,
				'name' => $type . '-' . $value,
			];
		}
		return $ret;
	}
	public static function shuffle(&$cards_array)
	{
		shuffle($cards_array);
		return $cards_array;
	}
	public static function get($num, &$cards_array, $reverse = false)
	{
		if ($reverse) {
			$count = count($cards_array);
			$cards = array_splice($cards_array, $count - $num, $num);
		} else {
			$cards = array_splice($cards_array, 0, $num);
		}
		return $cards;
	}
	public static function push($index_array, &$cards_array)
	{
		arsort($index_array);
		$ret = [];
		foreach ($index_array as $index) {
			array_merge($ret, array_splice($cards_array, $index, 1));
		}
		return $ret;
	}
}
