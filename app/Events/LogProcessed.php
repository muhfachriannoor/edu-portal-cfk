<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LogProcessed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var string
     */
    public $type;

    /**
     * @var array
     */
    public $data;

    /**
     * Create a new event instance.
     * @param array $data
     * @param string $type
     */
    public function __construct(array $data, string $type)
    {
        $this->type = $type;
        $this->data = $data;
    }
}
