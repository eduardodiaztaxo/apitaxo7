<?php

namespace App\Services\Dump\Tables;

interface DumpSQLiteInterface
{

    public function __construct(\PDO $pdo);

    public function runFromController(): void;

    public function createTable(): void;

    public function insert(array|\Illuminate\Http\Resources\Json\AnonymousResourceCollection $data): void;
}
