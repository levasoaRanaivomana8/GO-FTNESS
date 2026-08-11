<?php

declare(strict_types=1);

namespace App\Models;

use Config\DB;
use PDO;

final class Activite
{
    /**
     * @return array<int,array<string,mixed>>
     */
    public static function list(string $start, string $end): array
    {
        $pdo = DB::pdo();
        $sql = "SELECT idactivite, titre, start_at, end_at, description, image_url
                FROM activites
                WHERE start_at < :end AND end_at > :start
                ORDER BY start_at ASC";
        $st = $pdo->prepare($sql);
        $st->execute([':start' => $start, ':end' => $end]);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @param array{titre:string,start_at:string,end_at:string,description?:string,image_url?:string,created_by?:int} $data
     * @return array{ok:bool,id?:int,error?:string}
     */
    public static function create(array $data): array
    {
        $titre = trim((string)($data['titre'] ?? ''));
        $start = trim((string)($data['start_at'] ?? ''));
        $end   = trim((string)($data['end_at'] ?? ''));

        if ($titre === '' || $start === '' || $end === '') {
            return ['ok' => false, 'error' => 'Titre, début et fin sont obligatoires.'];
        }

        $pdo = DB::pdo();
        $st = $pdo->prepare("INSERT INTO activites (titre, start_at, end_at, description, image_url, created_by)
                            VALUES (:t, :s, :e, :d, :img, :by)");
        $st->execute([
            ':t' => $titre,
            ':s' => $start,
            ':e' => $end,
            ':d' => (string)($data['description'] ?? ''),
            ':img' => (string)($data['image_url'] ?? ''),
            ':by' => (int)($data['created_by'] ?? 0) ?: null,
        ]);

        return ['ok' => true, 'id' => (int)$pdo->lastInsertId()];
    }

    public static function delete(int $id): void
    {
        $pdo = DB::pdo();
        $st = $pdo->prepare('DELETE FROM activites WHERE idactivite = :id');
        $st->execute([':id' => $id]);
    }
}
