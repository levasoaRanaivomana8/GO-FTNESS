<?php

declare(strict_types=1);

namespace App\Models;

use Config\DB;
use PDO;

final class RefData
{
    public static function types(): array
    {
        $pdo = DB::pdo();
        return $pdo->query("SELECT idtype, nomtype FROM typepaiement ORDER BY idtype ASC")
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function modes(): array
    {
        $pdo = DB::pdo();
        return $pdo->query("SELECT idmode, nommode, duree_jours FROM modepaiement ORDER BY idmode ASC")
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function tarif(int $idtype, int $idmode): ?float
    {
        $pdo = DB::pdo();
        $st = $pdo->prepare("SELECT montant FROM tarifs WHERE idtype=:t AND idmode=:m LIMIT 1");
        $st->execute([':t' => $idtype, ':m' => $idmode]);
        $v = $st->fetchColumn();
        return $v !== false ? (float)$v : null;
    }

    public static function modeDuration(int $idmode): ?int
    {
        $pdo = DB::pdo();
        $st = $pdo->prepare("SELECT duree_jours FROM modepaiement WHERE idmode=:m LIMIT 1");
        $st->execute([':m' => $idmode]);
        $v = $st->fetchColumn();
        return $v !== false ? (int)$v : null;
    }
}
