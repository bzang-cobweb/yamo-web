# bza/laravel-form

Server-described forms and lists for Laravel. A form is declared once, in PHP, and that single description drives:

- what a client app displays (fields, labels, options, steps, upload settings), served as JSON;
- what the server accepts (validation rules derived from the same fields);
- the back office, through an optional Filament adapter.

Lists work the same way: the server describes the title, the items, the search and the filters, and serves the
items page by page with a cursor.

The core has no Filament dependency. Filament is only used by `Bza\Form\Filament\FieldComponent`.

## Contents

```
src/
├── Support/
│   ├── Form.php              Abstract form: fields, steps, title, submission, access
│   ├── Field.php             A field: type, label, options, conditions, rules, JSON shape
│   ├── Step.php              A step of a multi-step form
│   ├── Submission.php        Where and how the client sends the form, read from a named route
│   ├── FormRegistry.php      Forms by public name
│   ├── Listing.php           Abstract list: query, item formatting, search, filters, cursor pages
│   ├── ListItem.php          An item of a list: title, subtitle, image, actions, edit, delete…
│   ├── ListingRegistry.php   Lists by public name
│   ├── FormAuth.php          The only place reading the requesting account (`form.guard`)
│   ├── ContactDetails.php    Finds contact details in a text (phone, e-mail, link, network…)
│   ├── ContainsSearch.php    Case-insensitive « contains » search, wildcards matched literally
│   ├── DisplayTime.php       Dates formatted on the server, in the reader's zone and language
│   └── Locales.php           The languages offered by the application
├── Enums/
│   ├── FieldType.php         The field types the client knows
│   └── SuccessAction.php     What the client does after a successful submission
├── Rules/                    NoContactDetails, MinImageSide, OptionBelongsToParent
├── Models/FormUpload.php     A file uploaded for an `upload` field, pending for 24 h
├── Http/                     API controllers, resources and `FormBackedRequest`
└── Filament/FieldComponent.php   The Filament component of a `Field`
```

## Installation

The package is auto-discovered (`Bza\Form\FormServiceProvider`). Run the migrations (they create `form_uploads`):

```bash
php artisan migrate
```

Publish the configuration to change it:

```bash
php artisan vendor:publish --tag=form-config
```

## Declaring a form

```php
use Bza\Form\Enums\SuccessAction;
use Bza\Form\Support\Field;
use Bza\Form\Support\Form;
use Bza\Form\Support\Step;
use Bza\Form\Support\Submission;

class ContactForm extends Form
{
    public static function name(): string
    {
        return 'support.contact';
    }

    public function title(): ?string
    {
        return __('support::contact.title');
    }

    public function submitLabel(): string
    {
        return __('support::contact.send');
    }

    public function fields(): array
    {
        return [
            Field::text('name')->label(__('support::contact.name'))->required(),
            Field::email('email')->label(__('support::contact.email'))->required(),
            Field::select('topic')->label(__('support::contact.topic'))->options([
                'sales' => __('support::contact.topics.sales'),
                'support' => __('support::contact.topics.support'),
            ]),
            Field::textarea('message')->label(__('support::contact.message'))->required()->withoutContactDetails(),
            Field::upload('photos')->label(__('support::contact.photos'))->accept(['image/jpeg', 'image/png'])->maxSize(5120)->maxFiles(3),
        ];
    }

    // Optional: the client shows one step at a time and validates each one through the API.
    public function steps(): array
    {
        return [
            Step::make(__('support::contact.who'), ['name', 'email']),
            Step::make(__('support::contact.what'), ['topic', 'message', 'photos']),
        ];
    }

    // Optional: describe where the client sends the form, so that it needs no code of its own.
    public function submission(): ?Submission
    {
        return Submission::toRoute('api.v1.support.contact.store', SuccessAction::BACK, __('support::contact.sent'));
    }
}
```

Register it from a service provider:

```php
public function boot(FormRegistry $forms): void
{
    $forms->register(ContactForm::class);
}
```

Other hooks of `Form`, all optional: `subtitle()`, `description()`, `notice()`, `navigationTitle()`,
`titleAlign()`, `forRecord()` (bind the form to a record to edit it), `isAvailable()` (an unavailable form answers
404), `canBeViewedBy()` (by default, a signed-in account only; return `true` for a public form) and `links()`
(a list of `FormLink::make($label, $form)`, links shown under the form, each opening another form).

### Validating the submission

The request that receives the form takes its rules and attribute names from the form itself:

```php
use Bza\Form\Http\Requests\FormBackedRequest;

class StoreContactRequest extends FormBackedRequest
{
    protected function form(): Form
    {
        return new ContactForm($this->user());
    }
}
```

Only the form's fields end up in `validated()`.

### Fields

| Builder | Value |
|---|---|
| `text`, `hidden`, `email`, `password`, `textarea` | a string (`textarea`: 1000 characters by default) |
| `number` | a number |
| `select`, `radio`, `segmented` | one option value |
| `checkbox`, `switch` | a boolean |
| `checkboxGroup`, `multiselect`, `chips` | a list of option values |
| `date` | `YYYY-MM-DD`, with `dateRange()` and `calendar()` for the client's picker |
| `phone` | the number in `<name>`, the country in `<name>_country` |
| `code` | six digits |
| `range` | `[min, max]`, each optional, max ≥ min; `slider()` for a two-thumb slider |
| `rating` | a number, 1 to 5 by 0.1 unless `ratingRange()` says otherwise |
| `upload` | the ordered list of uploaded file identifiers |
| `repeater` | a list of groups of sub-fields (`fields()`, `maxItems()`) |

Common settings: `label()`, `placeholder()`, `help()`, `notice()`, `required()`, `readonly()` (shown, never
validated), `default()`, `options()` / `optionsUsing()`, `dependsOn()` (an option must belong to the parent's value),
`visibleWhen()` (hidden fields are neither required nor kept), `maxLength()`, `suffix()`, `money()`, `rules()` (extra
rules), `withoutContactDetails()` (rejects phones, e-mails, links, social networks…).

`Field::validationRules()` gives the Laravel rules, `Field::toArray()` the JSON served to the client and
`Field::displayValue($value, $locale)` a human-readable value.

## Declaring a list

```php
use Bza\Form\Support\ListItem;
use Bza\Form\Support\Listing;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AddressListing extends Listing
{
    public static function name(): string
    {
        return 'account.addresses';
    }

    public function title(): string
    {
        return __('account::addresses.title');
    }

    // A stable order ending with a unique column: the cursor follows it.
    public function query(): Builder
    {
        return $this->user->addresses()->getQuery()->orderBy('id');
    }

    public function item(Model $address): ListItem
    {
        return ListItem::make($address->id, $address->street, $address->city)
            ->edit(AddressForm::name());
    }

    public function create(): ?array
    {
        return ['label' => __('account::addresses.add'), 'form' => AddressForm::name()];
    }

    // A placeholder makes the list searchable.
    public function searchPlaceholder(): ?string
    {
        return __('account::addresses.search');
    }

    protected function search(Builder $query, string $term): void
    {
        $this->orWhereContains($query, ['street', 'city'], $term);
    }
}
```

Register it with `ListingRegistry::register()`. Other hooks: `perPage()`, `emptyText()`, `footer()`, `filters()` and
`applyFilters()`, `pinned()`, `constrain()`, `titleFor()`, `headingFor()`, `actionsMenu()`, `isAvailable()`,
`canBeViewedBy()`.

## API

Routes are registered under the configured prefix and name prefix (`api` and `api.` by default):

| Method and path | Name | Response |
|---|---|---|
| `GET v1/forms/{form}?record=` | `v1.forms.show` | the form's description |
| `POST v1/forms/{form}/steps/{step}` | `v1.forms.steps.validate` | 204, or 422 with the step's errors |
| `POST v1/forms/{form}/uploads/{field}` | `v1.forms.uploads.store` | 201 with the file identifier (`data.id`) |
| `GET v1/lists/{list}?cursor=&search=&filter[…]=` | `v1.lists.show` | the list and one page of items |

An unknown or unavailable form or list answers 404. Access goes through the `viewForm` and `viewList` gates, which
call `canBeViewedBy()`: 401 for a visitor, 403 for a refused account.

Uploaded files stay on the `local` disk for 24 hours, reserved to the account that sent them; schedule
`model:prune` to remove the expired ones:

```php
Schedule::command('model:prune', ['--model' => [FormUpload::class]])->daily();
```

## Filament

`FieldComponent::component($field)` turns a `Field` into the matching Filament component, with the same label, help,
options, required flag, visibility condition and validation rules. A `range` becomes two « Min » and « Max » inputs.

```php
use Bza\Form\Filament\FieldComponent;

public static function form(Schema $schema): Schema
{
    return $schema->components(array_map(
        fn (Field $field) => FieldComponent::component($field),
        (new ContactForm)->fields(),
    ));
}
```

## Configuration

`config/form.php`:

| Key | Default | Meaning |
|---|---|---|
| `guard` | `null` | the guard reading the requesting account; `null`: Laravel's default guard |
| `routes.enabled` | `true` | registers the API routes |
| `routes.prefix`, `routes.name`, `routes.middleware` | `api`, `api.`, `['api']` | URL prefix, route name prefix, middleware |
| `uploads.guests` | `false` | lets visitors upload files (the random identifier is then the proof) |
| `uploads.middleware` | `['throttle:60,1']` | added to the upload route |
| `date_formats` | | `Intl.DateTimeFormat` options served with each date field |
| `locales`, `default_locale` | | the offered languages; otherwise `app.supported_locales` and `app.default_locale`, then `app.locale` |

Translations live under the `form::` namespace (`lang/{en,fr,de}`). The word lists used to find contact details are in
`form::contact` and can be extended without code.

## Tests

The tests run inside the host application (its database and configuration), on their own fixture forms and list
(`tests/Fixtures`), without using any of the application's modules:

```bash
php artisan test packages/form/tests
```

They expect the application's user model to have a factory, and the package's routes to be enabled.
