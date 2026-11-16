<?php

namespace Modules\Form\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Form\Support\Form;

/**
 * FormRequest d'un formulaire servi à l'app : règles et libellés viennent du
 * formulaire lui-même — une seule source pour l'affichage et la validation. Seuls
 * les champs du formulaire sont validés ; tout autre champ envoyé est ignoré par
 * `validated()`.
 */
abstract class FormBackedRequest extends FormRequest
{
    /**
     * Le formulaire, construit pour l'utilisateur de la requête (valeurs par défaut).
     */
    abstract protected function form(): Form;

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return $this->form()->rules();
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->form()->attributes();
    }
}
