This is a poker game for every rule which form with
the containers: cards stack, hand cards and cards pool, 
and the rules: how to win.

# card define

The card define by card index with 13 band:
```
card_index = [0,4*13+2]

card_value = card_index % 13 // 0 - 12,0-1 0-8:2-10, 9-12:JQKA, 0-1:joker12
card_type = card_index / 13 % 4 // 0 - 5 0: 1:diamonds, 2:clubs, 3:hearts, 4:spades, 5:joker
```

# public interface

```
init(index_array):cards_array // exclude some cards
shuffle(&cards_array):cards_array // shuffle
order(&cards_array):cards_array // order
get(num, &cards_array, reverse):cards_array // get card(s) from cards stack, reverse for pop or shift
push(index_array, &cards_array):cards_array // push card(s) from hand, retern the cards push
```
# container
Each set has containers as follow:
```
cards_stack
cards_pool
action_stack
player
	hand
	pool
	score
