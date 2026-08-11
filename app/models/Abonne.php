<?php

declare(strict_types=1);

namespace App\Models;

use Config\DB;
use PDO;

final class Abonne
{
    public static function search(string $q = ''): array
    {
        $pdo = DB::pdo();
        $q = trim($q);

        if ($q === '') {
            $st = $pdo->query("SELECT * FROM abonne ORDER BY idabonne DESC LIMIT 500");
            return $st->fetchAll(PDO::FETCH_ASSOC);
        }

        $like = '%' . $q . '%';
        $st = $pdo->prepare("\
            SELECT *
            FROM abonne
            WHERE nom LIKE :q OR prenom LIKE :q OR tel LIKE :q
            ORDER BY idabonne DESC
            LIMIT 500
        ");
        $st->execute([':q' => $like]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function find(int $idabonne): ?array
    {
        $pdo = DB::pdo();
        $st = $pdo->prepare("SELECT * FROM abonne WHERE idabonne=:id LIMIT 1");
        $st->execute([':id' => $idabonne]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function create(array $data): int
    {
        $pdo = DB::pdo();

        // idsalle: main salle par défaut = 1
        $sql = "\
            INSERT INTO abonne (idsalle, nom, prenom, tel, adresse, datedebut)
            VALUES (1, :nom, :prenom, :tel, :adresse, :datedebut)
        ";

        $st = $pdo->prepare($sql);
        $st->execute([
            ':nom' => $data['nom'],
            ':prenom' => $data['prenom'],
            ':tel' => ($data['tel'] !== '' ? $data['tel'] : null),
            ':adresse' => ($data['adresse'] !== '' ? $data['adresse'] : null),
            ':datedebut' => ($data['datedebut'] !== '' ? $data['datedebut'] : null),
        ]);

        return (int)$pdo->lastInsertId();
    }

    public static function update(int $idabonne, array $data): void
    {
        $pdo = DB::pdo();
        $sql = "\
            UPDATE abonne
            SET nom=:nom, prenom=:prenom, tel=:tel, adresse=:adresse, datedebut=:datedebut
            WHERE idabonne=:id
            LIMIT 1
        ";
        $st = $pdo->prepare($sql);
        $st->execute([
            ':nom' => $data['nom'],
            ':prenom' => $data['prenom'],
            ':tel' => ($data['tel'] !== '' ? $data['tel'] : null),
            ':adresse' => ($data['adresse'] !== '' ? $data['adresse'] : null),
            ':datedebut' => ($data['datedebut'] !== '' ? $data['datedebut'] : null),
            ':id' => $idabonne,
        ]);
    }

    public static function delete(int $idabonne): void
    {
        $pdo = DB::pdo();

        // sécurité: si l'abonné a des abonnements, on refuse (FK)
        $st = $pdo->prepare("DELETE FROM abonne WHERE idabonne=:id LIMIT 1");
        $st->execute([':id' => $idabonne]);
    }
}
