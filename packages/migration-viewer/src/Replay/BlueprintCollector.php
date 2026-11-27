<?php

namespace Bza\MigrationViewer\Replay;

use Closure;
use Illuminate\Database\Schema\Blueprint;
use Throwable;

/**
 * Keeps the blueprints the schema builder creates while recording.
 *
 * The service provider feeds it from a container "resolving" hook: the schema
 * builder resolves every Blueprint from the container, whatever the connection.
 */
class BlueprintCollector
{
    /** @var list<Blueprint>|null Null when not recording. */
    private ?array $blueprints = null;

    public function collect(Blueprint $blueprint): void
    {
        if ($this->blueprints !== null) {
            $this->blueprints[] = $blueprint;
        }
    }

    /**
     * Runs the callback and returns the blueprints created meanwhile, with the
     * exception that stopped it, if any.
     *
     * @return array{0: list<Blueprint>, 1: Throwable|null}
     */
    public function record(Closure $callback): array
    {
        $previous = $this->blueprints;
        $this->blueprints = [];
        $error = null;

        try {
            $callback();
        } catch (Throwable $exception) {
            $error = $exception;
        }

        $blueprints = $this->blueprints;
        $this->blueprints = $previous;

        return [$blueprints, $error];
    }
}
