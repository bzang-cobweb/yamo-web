<?php

namespace Bza\Form\Tests\Fixtures;

use Bza\Form\Support\Listing;
use Bza\Form\Support\ListItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * A searchable list of notes, two per page, each opening the contact form.
 */
class NoteListing extends Listing
{
    public static function name(): string
    {
        return 'test.notes';
    }

    public function title(): string
    {
        return 'Notes';
    }

    public function query(): Builder
    {
        return Note::query()->orderBy('id');
    }

    public function perPage(): int
    {
        return 2;
    }

    public function item(Model $record): ListItem
    {
        return ListItem::make($record->getKey(), (string) $record->getAttribute('title'))->edit(ContactForm::name());
    }

    public function emptyText(): ?string
    {
        return 'No notes yet';
    }

    public function create(): ?array
    {
        return ['label' => 'Add a note', 'form' => ContactForm::name()];
    }

    public function searchPlaceholder(): ?string
    {
        return 'Search a note';
    }

    protected function search(Builder $query, string $term): void
    {
        $this->orWhereContains($query, ['title'], $term);
    }
}
