# Contributing

Thank you for your help. This file tells you how to prepare a change.

## Set up the project

1. Install PHP 8.2 or later and Composer.
2. Clone the repository.
3. Install the dependencies:

```bash
composer install
```

## Make a change

1. Create a branch from `main`.
2. Write a test for the change first. Then write the code.
3. Run the tests:

```bash
composer test
```

4. Run the static analysis:

```bash
composer analyse
```

5. Format the code:

```bash
composer format
```

6. Add a line to the `Unreleased` section of `CHANGELOG.md`.
7. Open a pull request. Complete the pull request template.

## Rules

- Each response is a typed, read-only value object. Do not return raw arrays for records.
- The BYOMailer source is the reference. If the OpenAPI document and the source do not agree, follow the source.
- Do not add a dependency if the PHP standard library can do the job.
- Do not add telemetry.
- Never put a token, a webhook secret or another secret in the code, the tests or the logs. Use `example.com` data.
- The integration tests must stay read-only. They must never send email, schedule a newsletter or send a webhook test delivery.
- If you change the webhook verifier, keep the shared vectors in `tests/Fixtures/webhook-signature-vectors.json` passing.

## Write the documentation

Write documentation, comments and commit messages in Simple English (ASD-STE100):

- Use short sentences. An instruction has a maximum of 20 words. A description has a maximum of 25 words.
- Use the active voice. Use the imperative for instructions.
- Use one word for one meaning.
- Do not use contractions or semicolons.
- Use "can", "will" and "must". Do not use "should", "may", "might", "could" or "would".

## Pull request titles

The title of each pull request must follow [Conventional Commits](https://www.conventionalcommits.org/en/v1.0.0/). The `PR title` check fails when the title does not follow the format. Examples:

- `feat: add the subscribe method to contacts`
- `fix(laravel): read the token from the config file`
- `docs: add a retry example to the README`

Use one of these types: `feat`, `fix`, `docs`, `style`, `refactor`, `perf`, `test`, `build`, `ci`, `chore` or `revert`. The scope in parentheses is optional.

GitHub makes the release notes from the titles of the merged pull requests. Write each title for a person who reads the release notes.

## Triage

Each issue form adds a type label and the `needs-triage` label to a new issue. The maintainer reads each new issue, removes `needs-triage` and sets the correct type label: `bug`, `enhancement`, `documentation` or `question`.

## Report a bug

Open an issue with the bug template. To report a security problem, read [SECURITY.md](SECURITY.md). Do not open a public issue for a security problem.
