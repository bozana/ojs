<?php

namespace APP\observers\listeners;

use APP\observers\events\UsageEvent;
use PKP\observers\listeners\PKPUsageEventLogListener;

class UsageEventLogListener extends PKPUsageEventLogListener
{
    /**
     * Create the event listener.
     *
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Handle the event.
     *
     * @param  APP\observers\events\UsageEvent $usageEvent
     */
    public function handle(UsageEvent $usageEvent)
    {
        parent::handle($usageEvent);
    }
}
