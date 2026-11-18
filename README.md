# BYOMailer PHP SDK

A PHP client for a self-hosted BYOMailer instance. The package also has a Laravel integration.

BYOMailer sends newsletters and email sequences through your own Amazon SES account. Each user runs an own instance, so the client needs the URL of your instance.

- A method for each operation of the BYOMailer API.
- Typed, read-only value objects for each response.
- A helper that reads all the pages of a list.
- A webhook verifier for the server.
- A typed exception for each error.
- Retries with exponential backoff and jitter.
- Any PSR-18 HTTP client. Guzzle is the default.
- Laravel: a service provider, a configuration file and a facade.
- No telemetry.

## Supported versions

| Runtime | Versions |
|---|---|
| PHP | 8.2, 8.3, 8.4 |
| Laravel (optional) | 11, 12, 13 |

Laravel 13 needs PHP 8.3 or later.

## Installation

The package is not on Packagist yet. Add the GitHub repository to the `composer.json` file of your project:

```json
{
    "repositories": [
        { "type": "vcs", "url": "https://github.com/amedina068/byomailer-php" }
    ]
}
```

Then install the package:

```bash
composer require byomailer/byomailer-php
```

The repository is private. Composer needs a GitHub token with read access to it.

The SDK sends requests with any PSR-18 client. If your project has no PSR-18 client, install Guzzle:

```bash
composer require guzzlehttp/guzzle
```

## Quick start

```php
use Byomailer\Byomailer;

$byomailer = new Byomailer('https://mail.example.com', getenv('BYOMAILER_TOKEN'));

$contact = $byomailer->contacts()->addToList(
    listId: 3,
    email: 'ana@example.com',
    name: 'Ana',
    tags: ['customer'],
    attributes: ['company' => 'Acme'],
);

if ($contact->wasRecentlyCreated) {
    // The contact is new.
}
```

Make the API token in BYOMailer, in Settings, API Tokens. A token has read access, or read and write access. A read-only token gets an `AuthorizationException` on each request that changes data.

## Configuration

```php
use Byomailer\Byomailer;
use Byomailer\Http\RetryPolicy;

$byomailer = new Byomailer(
    url: 'https://mail.example.com', // The SDK adds /api/v1.
    token: $token,
    timeout: 30.0,                   // Seconds. The default is 30.
    retries: 2,                      // Or a RetryPolicy. The default is 2.
    httpClient: null,                // Any PSR-18 client.
);
```

- The SDK accepts the URL with or without a trailing `/api` or `/api/v1`.
- PSR-18 has no timeout setting. If you give your own HTTP client, set its timeout yourself. With no client, the SDK creates a Guzzle client with the timeout.
- Each request sees only the data of the client of the token user.

## Resources

Each resource is a method of the client. Each method returns a value object, a list of value objects or a page.

### Contacts

```php
$page = $byomailer->contacts()->list(lists: [3], tags: [5], search: 'ana', perPage: 50);
$contact = $byomailer->contacts()->find(42);
$contact = $byomailer->contacts()->addToList(3, 'ana@example.com', name: 'Ana');
$contact = $byomailer->contacts()->update(42, name: 'Ana Pérez', removeTags: ['lead'], attributes: ['plan' => 'pro']);
$contact = $byomailer->contacts()->unsubscribe(42);
$contact = $byomailer->contacts()->removeFromList(3, 42);
```

- `list()` leaves out unsubscribed contacts. Give `includeUnsubscribed: true` to include them.
- `addToList()` creates the contact, or updates the contact with the same email. `$contact->wasRecentlyCreated` is true for a new contact.
- `attributes` are the custom attributes of the contact. The SDK sends them at the top level of the body, as the API expects. The names `email`, `name`, `tags` and `remove_tags` are not custom attributes, and the SDK refuses them.
- `update()` changes only the fields that you give. To remove the name, give an empty string.
- `unsubscribe()` unsubscribes the contact from all email. The contact stays on its lists.
- `removeFromList()` returns the contact without the list.

### Lists

```php
$page = $byomailer->lists()->list(perPage: 25);
$list = $byomailer->lists()->create('Customers');
$list = $byomailer->lists()->rename(3, 'Buyers');
$byomailer->lists()->delete(3);
```

### Topics

```php
$topics = $byomailer->topics()->list(listId: 3);
$topic = $byomailer->topics()->create(3, 'Product news', description: 'Releases and fixes');
$topic = $byomailer->topics()->update(3, 8, 'Product news', description: null);
$byomailer->topics()->delete(3, 8);
```

`update()` replaces the name and the description. A null description removes the description.

### Tags

```php
$tags = $byomailer->tags()->list();
$tag = $byomailer->tags()->create('vip');
$tag = $byomailer->tags()->rename(5, 'gold');
$byomailer->tags()->delete(5);
```

### Newsletters

```php
$page = $byomailer->newsletters()->list(status: 'draft', search: 'November', project: 1);
$newsletter = $byomailer->newsletters()->find(7);

$newsletter = $byomailer->newsletters()->create([
    'title' => 'November news',
    'subject' => 'Our November news',
    'html_content' => '<p>Hello {{ first_name }}</p>',
    'email_identity_id' => 2,
    'filters' => [['category' => 'lists', 'mode' => 'any', 'values' => [3]]],
]);

$newsletter = $byomailer->newsletters()->update(7, ['subject' => 'Our news for November']);
$byomailer->newsletters()->delete(7);

$count = $byomailer->newsletters()->countRecipients([['category' => 'tags', 'mode' => 'any', 'values' => [5]]]);
$address = $byomailer->newsletters()->sendTest(7);
$newsletter = $byomailer->newsletters()->chooseWinner(7, 'b');

$variables = $byomailer->newsletters()->variables(7);
$variables = $byomailer->newsletters()->replaceVariables(7, ['first_name' => 'friend']);
```

- `create()` and `update()` take the API fields with their API names. `scheduled_at` accepts a `DateTimeInterface`.
- With no `status`, `create()` makes a draft. A `live` status with a `scheduled_at` in the future schedules the newsletter. A `live` status with a `scheduled_at` of now sends it.
- `update()` changes only a draft or a scheduled newsletter. `delete()` deletes only a draft.
- `list()` does not include the HTML and the text content. Use `find()` to get them.
- `sendTest()` sends the newsletter to the email address of the token user, and returns this address.
- `chooseWinner()` needs a newsletter with the `ab_testing` status.
- `replaceVariables()` replaces all the saved defaults. An empty array removes them.

### Sequences

```php
$page = $byomailer->sequences()->list(status: 'live');
$sequence = $byomailer->sequences()->find(3);
$stats = $byomailer->sequences()->stats(3);
$step = $byomailer->sequences()->step(3, 11);
$stats = $byomailer->sequences()->stepStats(3, 11);
$step = $byomailer->sequences()->replaceStepFilters(3, 11, [['category' => 'tags', 'mode' => 'none', 'values' => [5]]]);
$variables = $byomailer->sequences()->variables(3);
$variables = $byomailer->sequences()->replaceVariables(3, ['first_name' => 'friend']);
$csv = $byomailer->sequences()->exportStepMails(3, 11, ['status' => 'sent', 'current_only' => true]);
```

- `list()` returns summaries with the counts. Use `find()` to get the steps, the filters and the content variables.
- `exportStepMails()` returns the CSV text, with a header row.

### Blocks

```php
$blocks = $byomailer->blocks()->list();
$block = $byomailer->blocks()->find(1);
$block = $byomailer->blocks()->create('footer', '<p>See you soon.</p>');
$block = $byomailer->blocks()->update(1, 'footer', '<p>Bye.</p>');
$byomailer->blocks()->delete(1);
```

### Email templates

```php
$page = $byomailer->emailTemplates()->list(archived: false);
$template = $byomailer->emailTemplates()->find(6);
$template = $byomailer->emailTemplates()->default();
```

If the client has no default template, `default()` throws a `NotFoundException`.

### Email identities

```php
$page = $byomailer->emailIdentities()->list(status: 'verified');
$identity = $byomailer->emailIdentities()->find(2);
```

### Email logs

```php
$page = $byomailer->emailLogs()->list(status: 'success', operation: 'SendEmail', recipient: 'ana');
$log = $byomailer->emailLogs()->find(70);
```

### Projects

```php
$page = $byomailer->projects()->list();
$project = $byomailer->projects()->find(1);
$project = $byomailer->projects()->create('Launch');
$project = $byomailer->projects()->rename(1, 'Spring launch');
$byomailer->projects()->delete(1);
```

### Webhook endpoints

Only a token of an admin user can use the webhook endpoints. The other tokens get an `AuthorizationException`.

```php
$page = $byomailer->webhookEndpoints()->list();
$endpoint = $byomailer->webhookEndpoints()->find(4);
$endpoint = $byomailer->webhookEndpoints()->create('https://example.com/webhooks/byomailer', ['contact.subscribed']);
$endpoint = $byomailer->webhookEndpoints()->update(4, active: false);
$byomailer->webhookEndpoints()->delete(4);
$page = $byomailer->webhookEndpoints()->deliveries(4);
$endpoint = $byomailer->webhookEndpoints()->rotateSecret(4);
$delivery = $byomailer->webhookEndpoints()->sendTest(4);
```

- `create()` and `rotateSecret()` return the secret in `$endpoint->secret`. The instance does not send it again, so store it at once.
- `update()` changes only `active` and the description. To change the URL or the events, create a new endpoint.
- `sendTest()` queues a `webhook.test` delivery.

## Pagination

A list endpoint returns one `CursorPage`. A page has `items`, `nextCursor` and `previousCursor`. The page size is 1 to 100, and the default is 25.

```php
$page = $byomailer->contacts()->list(perPage: 100);

foreach ($page as $contact) {
    echo $contact->email;
}

if ($page->hasMore()) {
    $next = $byomailer->contacts()->list(perPage: 100, cursor: $page->nextCursor);
}
```

To read every page, use `all()`. It returns a generator. The SDK reads the next page only when you reach the end of the current page.

```php
foreach ($byomailer->contacts()->all(tags: [5], perPage: 100) as $contact) {
    echo $contact->email;
}

foreach ($byomailer->webhookEndpoints()->allDeliveries(4) as $delivery) {
    echo $delivery->status;
}
```

`tags()->list()`, `blocks()->list()` and `topics()->list()` return all the items in one array. These endpoints have no pages.

## Webhook verification

BYOMailer signs each webhook delivery with the secret of the endpoint. Verify each delivery before you use it.

Verify webhooks only on the server. The endpoint secret is a secret. Never put it in browser code or in a mobile app.

```php
use Byomailer\Exceptions\WebhookVerificationException;
use Byomailer\Webhooks\Webhook;
use Byomailer\Webhooks\WebhookEvent;

$webhook = new Webhook(getenv('BYOMAILER_WEBHOOK_SECRET'));

try {
    $event = $webhook->verify(file_get_contents('php://input'), getallheaders());
} catch (WebhookVerificationException $exception) {
    http_response_code(400);
    exit;
}

if ($event->is(WebhookEvent::CONTACT_SUBSCRIBED)) {
    $email = $event->contact()['email'];
}
```

- Give the raw request body. Do not decode the JSON and encode it again. A new encoding gives other bytes, and the signature check fails.
- `verify()` returns a `WebhookEvent`. It has the event `type`, the decoded `payload`, the `deliveryId`, the signing `timestamp` and the `occurredAt` date.
- The SDK compares the signature in constant time.
- The SDK refuses a delivery that the instance signed more than 300 seconds before or after the current time. To change this tolerance, give `tolerance` to the constructor.
- `$exception->reason` tells why the SDK refused the delivery: `VerificationFailure::Signature`, `VerificationFailure::Timestamp` or `VerificationFailure::Payload`.
- The instance can add event types. The SDK accepts each event type. Ignore the types that you do not use.
- Use `deliveryId` to ignore a delivery that you received before. The instance sends a delivery again after a failure.

The events are `contact.subscribed`, `contact.unsubscribed`, `contact.resubscribed`, `newsletter.sent`, `sequence_step.sent`, `sequence.completed` and `webhook.test`. Each payload has `event` and `occurred_at`.

## Errors

Each exception extends `Byomailer\Exceptions\ByomailerException`.

| Exception | Cause |
|---|---|
| `AuthenticationException` | HTTP 401. The token is missing, not valid or revoked. |
| `AuthorizationException` | HTTP 403. The token is read-only, or the user is not an admin. |
| `NotFoundException` | HTTP 404. The record does not exist, or it belongs to another client. |
| `ValidationException` | HTTP 422. The instance refused the input. |
| `RateLimitException` | HTTP 429. The token sent too many requests. |
| `ServerException` | HTTP 5xx. The instance failed. |
| `ApiException` | The other 4xx statuses. It is also the parent of the exceptions above. |
| `ConnectionException` | The SDK got no response. |
| `UnexpectedResponseException` | The response body is not in the expected format. |
| `MissingCredentialsException` | The URL, the token or the webhook secret is empty. |
| `WebhookVerificationException` | A webhook delivery is not valid. |

```php
use Byomailer\Exceptions\RateLimitException;
use Byomailer\Exceptions\ValidationException;

try {
    $byomailer->contacts()->addToList(3, 'not-an-email');
} catch (ValidationException $exception) {
    $exception->errors();        // ['email' => ['The email field must be a valid email address.']]
    $exception->first('email');  // 'The email field must be a valid email address.'
} catch (RateLimitException $exception) {
    $exception->retryAfter();    // Seconds, from the Retry-After header.
}
```

Each `ApiException` has the HTTP `status`, the `response` and the decoded `body()`.

## Retries

The SDK sends a request again after a 429, a 5xx or a connection error. The default is 2 retries.

- The delay grows exponentially with jitter: 0.5 seconds, then 1 second, then 2 seconds, up to 8 seconds.
- After a 429, the SDK waits for the time in the `Retry-After` header. If this time is more than 60 seconds, the SDK does not wait and throws the `RateLimitException`.
- The SDK does not send a request that is not idempotent again after a 5xx or a timeout. The instance can have received the request. It sends this request again only after a 429, or after a connection error that occurred before the instance received the request.
- `GET` and `DELETE` requests are idempotent. These writes are also idempotent, because they set values: the renames, the updates of contacts, topics, blocks and webhook endpoints, the replacement of filters and content variables, `unsubscribe()` and `countRecipients()`.

Three endpoints have a rate limit:

| Method | Limit |
|---|---|
| `newsletters()->countRecipients()` | 30 a minute |
| `newsletters()->sendTest()` | 5 a minute |
| `webhookEndpoints()->sendTest()` | 10 a minute |

To change the retries, give a `RetryPolicy`:

```php
use Byomailer\Http\RetryPolicy;

$byomailer = new Byomailer($url, $token, retries: new RetryPolicy(
    retries: 4,
    baseDelayMs: 250,
    maxDelayMs: 5_000,
    maxRetryAfter: 30,
));

$byomailer = new Byomailer($url, $token, retries: RetryPolicy::none());
```

## Laravel

The package registers its service provider and the `Byomailer` facade automatically.

### Configuration

Add these values to the `.env` file:

```dotenv
BYOMAILER_URL=https://mail.example.com
BYOMAILER_TOKEN=your-api-token
BYOMAILER_WEBHOOK_SECRET=whsec_your-endpoint-secret
```

The other values are `BYOMAILER_TIMEOUT` (30), `BYOMAILER_RETRIES` (2) and `BYOMAILER_WEBHOOK_TOLERANCE` (300).

To publish the configuration file, run this command:

```bash
php artisan vendor:publish --tag=byomailer-config
```

### Facade and injection

```php
use Byomailer\Byomailer;
use Byomailer\Laravel\Facades\Byomailer as ByomailerFacade;

$contact = ByomailerFacade::contacts()->addToList(3, $user->email, name: $user->name);

public function __construct(private Byomailer $byomailer) {}
```

The container gives one client for the application.

### Webhooks

The container also gives a `Webhook` with the configured secret and tolerance.

```php
use Byomailer\Exceptions\WebhookVerificationException;
use Byomailer\Webhooks\Webhook;
use Illuminate\Http\Request;

class ByomailerWebhookController
{
    public function __invoke(Request $request, Webhook $webhook)
    {
        try {
            $event = $webhook->verify($request->getContent(), $request->headers->all());
        } catch (WebhookVerificationException) {
            abort(400);
        }

        // Process $event, or dispatch a job.

        return response()->noContent();
    }
}
```

If the webhook route is in `routes/web.php`, remove it from CSRF protection. The instance sends no CSRF token.

## Testing the SDK

```bash
composer test       # Pest
composer lint       # Pint
composer analyse    # PHPStan, level max
```

The integration tests call a real instance. They run only when `BYOMAILER_TEST_URL` and `BYOMAILER_TEST_TOKEN` are set. They only read data: they list contacts, lists and tags.

```bash
BYOMAILER_TEST_URL=https://mail.example.com BYOMAILER_TEST_TOKEN=your-read-only-token composer test:integration
```

Use a read-only token for the integration tests.

## Security

To report a security problem, read [SECURITY.md](SECURITY.md).

## License

The owner has not chosen a license yet.
