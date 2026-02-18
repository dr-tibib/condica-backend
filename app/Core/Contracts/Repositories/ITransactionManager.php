<?php

declare(strict_types=1);

namespace App\Core\Contracts\Repositories;

interface ITransactionManager
{
    /**
     * Execute a callback within a database transaction.
     *
     * @param callable $callback
     * @return mixed
     */
    public function transaction(callable $callback);
}
