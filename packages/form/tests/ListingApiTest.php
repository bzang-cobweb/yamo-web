<?php

namespace Bza\Form\Tests;

use Bza\Form\Tests\Fixtures\Note;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A list is served page by page, restricted to the search, to an account it lets in.
 */
class ListingApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create(Note::TABLE, function (Blueprint $table): void {
            $table->id();
            $table->string('title');
        });
    }

    private function url(array $query = []): string
    {
        return $this->packageRoute('v1.lists.show', ['list' => 'test.notes', ...$query]);
    }

    public function test_a_list_is_described_with_its_items(): void
    {
        $this->signIn();
        $note = Note::query()->create(['title' => 'Alpha']);

        $this->getJson($this->url())
            ->assertOk()
            ->assertJsonPath('data.name', 'test.notes')
            ->assertJsonPath('data.title', 'Notes')
            ->assertJsonPath('data.empty', 'No notes yet')
            ->assertJsonPath('data.create', ['label' => 'Add a note', 'form' => 'test.contact'])
            ->assertJsonPath('data.search', 'Search a note')
            ->assertJsonPath('data.items.0.id', (string) $note->id)
            ->assertJsonPath('data.items.0.title', 'Alpha')
            ->assertJsonPath('data.items.0.edit', ['form' => 'test.contact', 'record' => (string) $note->id])
            ->assertJsonPath('data.next_cursor', null);
    }

    public function test_items_are_served_by_pages_following_the_cursor(): void
    {
        $this->signIn();
        foreach (['Alpha', 'Beta', 'Gamma'] as $title) {
            Note::query()->create(['title' => $title]);
        }

        $first = $this->getJson($this->url())->assertOk();
        $this->assertSame(['Alpha', 'Beta'], array_column($first->json('data.items'), 'title'));

        $second = $this->getJson($this->url(['cursor' => $first->json('data.next_cursor')]))->assertOk();
        $this->assertSame(['Gamma'], array_column($second->json('data.items'), 'title'));
        $this->assertNull($second->json('data.next_cursor'));

        // An unreadable cursor serves the first page.
        $this->assertSame(['Alpha', 'Beta'], array_column($this->getJson($this->url(['cursor' => 'nonsense']))->json('data.items'), 'title'));
    }

    public function test_the_search_is_case_insensitive_and_literal(): void
    {
        $this->signIn();
        foreach (['Alpha', 'Beta', '100% sure'] as $title) {
            Note::query()->create(['title' => $title]);
        }

        $this->assertSame(['Beta'], array_column($this->getJson($this->url(['search' => '  bEt ']))->json('data.items'), 'title'));
        $this->assertSame(['100% sure'], array_column($this->getJson($this->url(['search' => '%']))->json('data.items'), 'title'));
    }

    public function test_a_visitor_and_an_unknown_list_are_refused(): void
    {
        $this->getJson($this->url())->assertUnauthorized();

        $this->signIn();
        $this->getJson($this->packageRoute('v1.lists.show', ['list' => 'test.missing']))->assertNotFound();
    }
}
