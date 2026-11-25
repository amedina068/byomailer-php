# Update report: byomailer-php 0.2.0

## Pull request

https://github.com/amedina068/byomailer-php/pull/1

- Branch: `update/0.2.0`, from `origin/main`.
- Base: `main`. The PR is not merged.
- Commit: `bcdf56c`.

## Changes

| Item of UPDATE-0.2.0.md | Change in the SDK |
|---|---|
| 1. `contacts.subscribe` | New method `contacts()->subscribe(int $contactId): Contact`. It sends `POST /contacts/{contactId}/subscribe`. The SDK retries it as an idempotent write. New method `ValidationException::has(string $field)`. A 422 has the error on the field `contact` (unsubscribe through an email link) or on the field `email` (blacklisted address). |
| 2. Update never subscribes again | The docblock of `update()` and the README say that an update never changes the subscription. The README had no caution about an admin unsubscribe, so there was nothing to remove. |
| 3. `contacts.add` | The docblock of `addToList()` and the README say that the instance subscribes again a contact that an admin or the API unsubscribed. For an opt-in list, only a contact that is new on the list gets the opt-in email. |
| 4. PUT contact | No change. The SDK uses `PATCH`. The README had no note about a 422 on `PUT`. |
| 5. PATCH `operationId` | No change. The SDK does not use operation IDs in code, tests or docs. |
| 6. Sequences | New type `SequenceSummary` for the items of `list()` and `all()`. `find()` returns `Sequence`, with `steps`, `filters` and `contentVariables`. Both types need the four counts. New enum `Byomailer\Enums\SequenceStatus` (`Draft`, `Live`, `Paused`) for the `status` filter. |
| 7. Step mail export | `exportStepMails()` removes `current_only` from the query. The docblock and the README do not name it. |
| 8. Cursor lists | No code change. `CursorPage` reads only `data`, `meta.next_cursor` and `meta.prev_cursor`. The test fixture already has `links`, `meta.per_page` and `meta.path`. |
| 9. Webhooks | The docblock of `WebhookEvent::NEWSLETTER_SENT` and the README say that `newsletter.sent` comes one time for an A/B newsletter, when a caller chooses the winner. The signature vectors did not change. |

Other change: the docblock of `newsletters()->chooseWinner()` said that the instance sends the winner to the other contacts. PR #115 changed this. The instance now sends no more email. The docblock and the README now say this.

`CHANGELOG.md` has a `0.2.0 - Unreleased` section.

## Tests

- `composer test` (Pest): 152 passed, 3 skipped, 779 assertions. The 3 skipped tests are the integration tests. They need `BYOMAILER_TEST_URL` and `BYOMAILER_TEST_TOKEN`.
- `composer lint` (Pint): passed.
- `composer analyse` (PHPStan, level max): no errors.

## Differences between the spec and the source

- `current_only` of the step export: the spec puts `deprecated: true` in the parameter schema, not on the parameter. The source marks it with a `@deprecated` comment in `ExportSequenceStepContactMailsRequest`. The behavior is the same: the app ignores the value.

## Open points

1. Breaking changes for 0.1.0 callers: `sequences()->list()` returns `SequenceSummary`, and the `status` filter is a `SequenceStatus`, not a string. The version is 0.x, so a minor version is correct. Tell the callers in the release notes.
2. The `status` property of `Sequence` and `SequenceSummary` stays a string. An enum there throws an error if the app adds a status. Decide if the property also becomes a `SequenceStatus`.
3. `Sequence` and `SequenceSummary` now need `steps_count`, `contacts_count`, `completed_count` and `active_count`. A response without one of these fields gives an `UnexpectedResponseException`.
4. `composer validate --strict` warns that the package has no license. This is intended: the owner decides the license later.
