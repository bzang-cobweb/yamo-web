<?php

namespace Modules\Partner\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Faire ou défaire un administrateur d'un partenaire depuis l'app (décision 2026-11-27-membre-administrateur) ; les droits
 * sont vérifiés sur la route (`PartnerPolicy::manageMembers()`).
 */
class PartnerMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return ['is_admin' => ['required', 'boolean']];
    }
}
