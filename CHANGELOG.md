# Changelog

All notable changes to this project are in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/). The project uses [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.1.0] - Unreleased

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
