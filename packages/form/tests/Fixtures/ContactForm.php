<?php

namespace Bza\Form\Tests\Fixtures;

use Bza\Form\Enums\SuccessAction;
use Bza\Form\Support\Field;
use Bza\Form\Support\Form;
use Bza\Form\Support\FormLink;
use Bza\Form\Support\Step;
use Bza\Form\Support\Submission;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * A public two-step form with a file field, bindable to a record.
 */
class ContactForm extends Form
{
    private ?string $record = null;

    public static function name(): string
    {
        return 'test.contact';
    }

    public function title(): ?string
    {
        return 'Contact us';
    }

    public function links(): array
    {
        return [FormLink::make('Our private form', 'test.private')];
    }

    public function submitLabel(): string
    {
        return 'Send';
    }

    public function canBeViewedBy(?Authenticatable $user): bool
    {
        return true;
    }

    public function forRecord(string $key): static
    {
        $form = clone $this;
        $form->record = $key;

        return $form;
    }

    public function submission(): ?Submission
    {
        return Submission::toRoute(config('form.routes.name').'v1.forms.show', SuccessAction::BACK, 'Sent', parameters: ['form' => static::name()]);
    }

    public function fields(): array
    {
        return [
            Field::text('name')->label('Name')->required()->default($this->record === null ? null : 'record-'.$this->record),
            Field::email('email')->label('E-mail')->required(),
            Field::select('topic')->label('Topic')->options(['sales' => 'Sales', 'support' => 'Support']),
            Field::upload('photos')->label('Photos')->accept(['image/jpeg', 'image/png'])->maxSize(512)->maxFiles(2),
        ];
    }

    public function steps(): array
    {
        return [
            Step::make('Who are you?', ['name', 'email']),
            Step::make('Your request', ['topic', 'photos'])->subtitle('Tell us more'),
        ];
    }
}
