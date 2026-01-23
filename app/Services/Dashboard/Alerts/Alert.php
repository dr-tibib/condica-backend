<?php

namespace App\Services\Dashboard\Alerts;

class Alert
{
    public function __construct(
        public string $title,
        public string $message,
        public string $actionUrl,
        public string $actionLabel,
        public string $type = 'warning' // warning, info, danger, success
    ) {}
}
