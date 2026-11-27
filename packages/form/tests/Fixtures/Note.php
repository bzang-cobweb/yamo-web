<?php

namespace Bza\Form\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

/**
 * A record of the test list; its table is created by the test.
 *
 * @property int $id
 * @property string $title
 */
class Note extends Model
{
    public const TABLE = 'form_test_notes';

    protected $table = self::TABLE;

    protected $guarded = [];

    public $timestamps = false;
}
