<?php

namespace Formwork\Events;

use Closure;

class EventDispatcher
{
    /**
     * @var array<string, list<Closure>>
     */
    protected array $listeners = [];

    /**
     * Register an event listener
     *
     * @param Closure(Event): void $listener
     */
    public function on(string $eventName, Closure $listener): void
    {
        $this->listeners[$eventName][] = $listener;
    }

    /**
     * Dispatch an event
     */
    public function dispatch(Event $event): void
    {
        $eventName = $event->name();

        if (!isset($this->listeners[$eventName])) {
            return;
        }

        foreach ($this->listeners[$eventName] as $listener) {
            $listener($event);

            if ($event->isPropagationStopped()) {
                break;
            }
        }
    }
}
