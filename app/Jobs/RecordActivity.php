<?php

namespace App\Jobs;

use App\Models\Activity;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RecordActivity implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var int
     */
    public $tries = 2;

    /**
     * @var mixed
     */
    protected $user;

    /**
     * @var array
     */
    protected $data;

    /**
     * Create a new job instance.
     * @param mixed $user
     * @param array $data
     */
    public function __construct($user, array $data)
    {
        $this->user = $user;
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // if ($this->user) {
        //     $this->user->activities()
        //         ->create($this->data);
        // } else {
            Activity::create($this->data);
        // }
    }
}
