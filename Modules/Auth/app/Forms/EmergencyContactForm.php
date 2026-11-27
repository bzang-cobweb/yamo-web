<?php

namespace Modules\Auth\Forms;

use Bza\Form\Enums\SuccessAction;
use Bza\Form\Support\Field;
use Bza\Form\Support\Form;
use Bza\Form\Support\Locales;
use Bza\Form\Support\Submission;
use Modules\Auth\Models\User;
use Modules\Auth\Support\PhoneNumbers;
use Modules\Location\Support\CurrentCountry;

/**
 * « Contact d'urgence », dans « Informations personnelles » (`PUT /api/v1/auth/me/emergency-contact`, décision
 * 2026-11-27-contact-d-urgence) : nom (obligatoire), lien de parenté, e-mail, téléphone (obligatoire, pays + numéro)
 * et langue (obligatoire) ; prérempli par le contact enregistré, qui est créé ou modifié.
 */
class EmergencyContactForm extends Form
{
    public static function name(): string
    {
        return 'auth.profile.emergency-contact';
    }

    public function fields(): array
    {
        $user = $this->user instanceof User ? $this->user : null;
        $contact = $user?->emergencyContact()->with('phoneCountry')->first();

        return [
            Field::text('name')
                ->label(__('auth::emergency_contact.name'))
                ->default($contact?->name)
                ->required()
                ->maxLength(255),
            Field::text('relationship')
                ->label(__('auth::emergency_contact.relationship'))
                ->placeholder(__('auth::emergency_contact.relationship_placeholder'))
                ->default($contact?->relationship)
                ->maxLength(255),
            Field::email('email')
                ->label(__('auth::emergency_contact.email'))
                ->default($contact?->email)
                ->maxLength(255),
            Field::phone('phone')
                ->label(__('auth::emergency_contact.phone'))
                ->options(PhoneNumbers::countryOptions())
                ->default([
                    // Le pays courant d'office, comme pour le numéro du compte (décision 2026-11-18-espace-pays).
                    'country' => $contact?->phoneCountry?->code ?? CurrentCountry::get()?->code,
                    'number' => $contact === null ? null : PhoneNumbers::national($contact->phone),
                ])
                ->required(),
            Field::select('locale')
                ->label(__('auth::emergency_contact.locale'))
                ->help(__('auth::emergency_contact.locale_help'))
                ->options(Locales::options())
                ->default($contact?->locale ?? $user?->locale ?? Locales::default())
                ->required(),
        ];
    }

    public function title(): string
    {
        return __('auth::emergency_contact.title');
    }

    public function notice(): ?string
    {
        return __('auth::emergency_contact.notice');
    }

    public function submission(): Submission
    {
        return Submission::toRoute('api.v1.auth.me.emergency-contact.update', SuccessAction::PROFILE, __('auth::emergency_contact.saved'));
    }

    public function submitLabel(): string
    {
        return __('auth::form.save');
    }
}
