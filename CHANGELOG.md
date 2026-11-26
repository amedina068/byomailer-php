# Changelog

All notable changes to this project are in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/). The project uses [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.2.0] - 2026-11-25

The same behavior as the JS, Python and Ruby SDKs.

### Added

- The MIT license: a `LICENSE` file and the `license` field in `composer.json`.
- `WebhookVerificationException::invalidTimestamp()`, for a timestamp header that is missing or is not a Unix time.
- `contacts()->subscribe()` subscribes a contact again after an admin or the API unsubscribed it.
- `ValidationException::has()` tells if the instance refused one field. Use it to read why `subscribe()` failed: `contact` or `email`.
- `SequenceSummary`, the type of each item of `sequences()->list()` and `sequences()->all()`.
- `Byomailer\Enums\SequenceStatus`, with the cases `Draft`, `Live` and `Paused`.

### Changed

- **Breaking:** `contacts()->addToList()` is now `contacts()->add()`, the same name as in the other SDKs.
- **Breaking:** `newsletters()->chooseWinner()` is now `newsletters()->pickWinner()`, the same name as in the other SDKs.
- **Breaking:** a missing or malformed timestamp header now gives the reason `VerificationFailure::Timestamp`. Before, it gave `VerificationFailure::Signature`.
- The rule for a connection error before the send did not change: the SDK retries each request after a DNS failure, a refused connection or a connect timeout. The 12 SDKs of the contract now use this rule.
- `Webhook::verify()` changes the signature header to lowercase before the compare. A body that is a JSON list now gives `VerificationFailure::Payload`.
- An empty `X-Byomailer-Delivery` header gives a null `deliveryId`.
- The README gives the install command with `:dev-main`, the idempotent list and the MIT license.
- `sequences()->list()` and `sequences()->all()` return `SequenceSummary` items in place of `Sequence` items.
- The `status` filter of `sequences()->list()` and `sequences()->all()` is a `SequenceStatus`, not a string.
- `Sequence` and `SequenceSummary` need `steps_count`, `contacts_count`, `completed_count` and `active_count`. The instance now sends the real values.
- The docs of `contacts()->update()` say that an update never changes the subscription.
- The docs of `contacts()->addToList()` say that the instance subscribes again a contact that an admin unsubscribed. For an opt-in list, only a contact that is new on the list gets the opt-in email.
- The docs of `newsletters()->chooseWinner()` say that the instance sends no more email. The `newsletter.sent` webhook event comes one time.

### Removed

- `sequences()->exportStepMails()` does not send the `current_only` filter. The instance deprecated this filter and ignores it.

## [0.1.0] - Not published

Version 0.1.0 was not published. Release 0.2.0 contains these changes.

### Added

- A client for one BYOMailer instance, with an API token, a timeout and a retry policy.
- Resources: contacts, lists, topics, tags, newsletters, sequences, blocks, email templates, email identities, email logs, projects and webhook endpoints.
- Typed, read-only value objects for each response.
- `CursorPage` and an `all()` method for each list with pages.
- `Webhook::verify()`, with the shared signature test vectors.
- A typed exception for each error: 401, 403, 404, 422, 429, 5xx, connection, unexpected response and webhook verification.
- Retries after a 429, a 5xx or a connection error, with exponential backoff, jitter and `Retry-After`.
- PSR-18 and PSR-17 support with `php-http/discovery`.
- Laravel: a service provider with auto-discovery, a configuration file, a facade and a `Webhook` binding.
