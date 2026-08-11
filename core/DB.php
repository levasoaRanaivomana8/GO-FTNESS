<?php
declare(strict_types=1);

namespace Core;

use PDO;

final class DB
{
    public static function pdo(): PDO
    {
        return \Config\DB::pdo();
    }
}