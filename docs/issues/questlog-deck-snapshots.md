# Quest log deck snapshots are rebuilt on every request

Status: open, deferred to a later rework of quest logs. The performance part is mitigated: the
snapshots of a list page are now rebuilt in 4 queries in all, and only for the quest log decks
without a decklist (see [questlog-list-optimization.md](questlog-list-optimization.md)). The
description below is the state before that change.

## Problem

### Performance

The quest log list pages rebuild the deck snapshot of every quest log they display:

- `questlogs_list` (`ListQuestlogController`): popular, recent, hall of fame, hot topics, search,
  favorites, mine;
- `myquestlogs_list` (`MyListQuestlogController`).

Both call `SnapshotManager::setSnapshots()` on the quest logs of the page. For each `QuestlogDeck`,
it calls `Decks::setSlots()`, which:

1. looks up every card of the snapshot one by one with `Decks::findCardByCode()` (one or two
   queries per card, with the fallback on pre-printings-merge codes);
2. loads the current slots and side slots of the linked `Deck` to remove them;
3. creates new `Deckslot` / `Decksideslot` objects from the snapshot.

A page holds up to 30 quest logs, each with up to 4 decks of about 50 cards: up to several thousand
queries per page. The templates (`QuestLog/public-questlogs.html.twig`, `QuestLog/questlist.html.twig`)
only use the **heroes** of each deck (thumbnail, name, sphere), through
`getSlots().getHeroDeck()`.

The public lists are HTTP-cached (`max-age`), which limits the cost on popular / recent; the
private lists (favorites, mine, `myquestlogs_list`) are not.

These figures come from reading the code. Measured since on the test fixtures: about 3,050 of the
3,218 queries of `/questlogs/popular` came from the snapshots
([questlog-list-optimization.md](questlog-list-optimization.md)).

The work is also partly useless: when the `QuestlogDeck` points to a `Decklist`, the template
displays the `Decklist` and ignores `deck`, which `setSlots()` has just rewritten.

### Correctness

`setSlots()` works on the user's live `Deck`, a managed Doctrine entity:

- it calls `remove()` on its real slots. No `flush()` runs in these requests today, but any
  `flush()` added in the same request (a listener, a service) would overwrite the user's current
  deck with the quest log snapshot;
- when the deck was deleted, `SnapshotManager::setSnapshot()` creates a `[deleted]` `Deck`, never
  persisted, and attaches it to the managed `QuestlogDeck`: a `flush()` would fail on it;
- quantities are capped at the card's deck limit and unknown codes are dropped silently.

The quest log export (`QuestlogArchiver::downloadFromSelection()`) uses the same trick: it rewrites
the slots of each linked `Deck` with the snapshot, then calls `Deck::getTextExport()`. The file name
uses the *current* deck name and version, not those of the snapshot, and a `QuestlogDeck` whose
deck was deleted is skipped.

## Cause

`QuestlogDeck::content` stores the deck at the time the quest was played as JSON
(`{"main": {code: qty}, "side": {code: qty}}`). Codes are not references: some predate the
card-printings merge (e.g. `31031`, now a printing of card `17143`).

Everything that displays or exports a deck (`SlotCollectionInterface`, `ExportableDeck::getTextExport()`,
the `Export/*.twig` templates) needs slots holding real `Card` entities: card type for
`getSlotsByType()` / `getHeroDeck()` / `getDrawDeck()`, pack for `getIncludedPacks()`, name, pack
name, OCTGN id and cost in the templates. A JSON of codes provides none of that, so the code borrows
the live `Deck` entity as a container and refills it from the JSON on every request.

## Proposed solution

Store the snapshot as real slots instead of JSON.

### Schema

A new entity `QuestlogDeckSlot` (table `questlog_deck_slot`), implementing `SlotInterface`:

| column             | type                        |
|--------------------|-----------------------------|
| `id`               | int, PK                     |
| `questlog_deck_id` | FK `questlog_deck`, cascade |
| `card_id`          | FK `card`                   |
| `quantity`         | smallint                    |
| `is_side`          | bool                        |

(or two entities, main and side, mirroring `Deckslot` / `Decksideslot`).

`QuestlogDeck` gets the `slots` / `sideslots` relations, exposed as `SlotCollectionInterface`
through `SlotCollectionDecorator`, like `Deck` and `Decklist`.

### QuestlogDeck as an exportable deck

`ExportableDeck` is an abstract class whose contract (`getDateCreation()`, `getUser()`,
`getDescriptionMd()`, `getLastPack()`, `getArrayExport()`…) has no meaning for a `QuestlogDeck`.
`getTextExport()`, the only method the exports need, uses `getName()`, `getSlots()` and
`getSideslots()`. Extract it into a narrow interface (e.g. `TextExportableDeck`, with a trait for
`getTextExport()`), implemented by `ExportableDeck` and by `QuestlogDeck`.

`QuestlogDeck::getName()` returns the name of the decklist, else of the deck, else `[deleted]`.

### What goes away

- `Decks::setSlots()` and `SnapshotManager`;
- the per-card lookups at display time: the list controllers fetch-join
  `questlog → decks → slots → card → sphere` in a few queries per page;
- `QuestlogArchiver` calls `getTextExport()` on each `QuestlogDeck` directly, including those whose
  deck was deleted; the file name uses the quest log deck name and number instead of the current
  deck version.

### Migration

- Doctrine migration: create the table, then fill it from `questlog_deck.content`, resolving old
  codes through `card_printing.image_code` (same fallback as `Decks::findCardByCode()`). Decide what
  to do with codes that resolve to no card (today they are ignored); log them during the migration.
- Then drop `questlog_deck.content`, or keep it until the new path is validated in production.

### Code to adapt

- `SaveQuestlogController`: the three `setContent(json_encode(...))` calls create slots instead;
- `ViewQuestlogController`, `EditQuestlogController`: they pass the JSON to the JavaScript
  (`questlogdeckN_content`); generate it from the slots (`ExportableDeck::getContent()` already does
  this for decks);
- `QuestlogFixtures` and the functional test snapshots;
- `QuestlogDeck`-related templates (`public-questlogs`, `questlist`): read the heroes from the quest
  log deck slots instead of `deck.deck` / `deck.decklist`.
