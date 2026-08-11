<?php

declare(strict_types=1);

namespace App\Models;

use Config\DB;
use PDO;
use Exception;

final class Abonnement
{
    public static function search(string $q = ''): array
    {
        $pdo = DB::pdo();
        $q = trim($q);

        $base = "\
            SELECT ab.idabonnement, ab.numero_facture, ab.montant, ab.datedebut, ab.datefin, ab.status,
                   a.nom, a.prenom, a.tel,
                   tp.nomtype, mp.nommode,
                   ab.created_at
            FROM abonnement ab
            JOIN abonne a ON a.idabonne = ab.idabonne
            JOIN typepaiement tp ON tp.idtype = ab.idtype
            JOIN modepaiement mp ON mp.idmode = ab.idmode
        ";

        if ($q === '') {
            $st = $pdo->query($base . " ORDER BY ab.idabonnement DESC LIMIT 500");
            return $st->fetchAll(PDO::FETCH_ASSOC);
        }

        $like = '%' . $q . '%';
        $st = $pdo->prepare($base . "\
            WHERE ab.numero_facture LIKE :q
               OR a.nom LIKE :q OR a.prenom LIKE :q OR a.tel LIKE :q
            ORDER BY ab.idabonnement DESC
            LIMIT 500
        ");
        $st->execute([':q' => $like]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function find(int $idabonnement): ?array
    {
        $pdo = DB::pdo();
        $st = $pdo->prepare("\
            SELECT ab.*, a.nom, a.prenom, a.tel, a.adresse,
                   tp.nomtype, mp.nommode, mp.duree_jours,
                   u.username AS created_by_username
            FROM abonnement ab
            JOIN abonne a ON a.idabonne = ab.idabonne
            JOIN typepaiement tp ON tp.idtype = ab.idtype
            JOIN modepaiement mp ON mp.idmode = ab.idmode
            LEFT JOIN users u ON u.iduser = ab.created_by
            WHERE ab.idabonnement=:id
            LIMIT 1
        ");
        $st->execute([':id' => $idabonnement]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Create abonnement.
     * - montant: si vide => lookup tarifs(idtype,idmode)
     * - datedebut: si vide => today
     * - datefin: datedebut + (duree_jours - 1)
     */
    public static function create(array $data): array
    {
        $pdo = DB::pdo();

        $idabonne = (int)($data['idabonne'] ?? 0);
        $idtype   = (int)($data['idtype'] ?? 0);
        $idmode   = (int)($data['idmode'] ?? 0);
        $createdBy = (int)($data['created_by'] ?? 0);

        $datedebut = trim((string)($data['datedebut'] ?? ''));
        if ($datedebut === '') {
            $datedebut = date('Y-m-d');
        }

        $montantRaw = trim((string)($data['montant'] ?? ''));
        $montant = null;
        if ($montantRaw !== '') {
            // accepte "80 000" ou "80000" ou "80000.00"
            $clean = str_replace([' ', ','], ['', '.'], $montantRaw);
            if (!is_numeric($clean)) {
                return ['ok' => false, 'error' => 'Montant invalide'];
            }
            $montant = (float)$clean;
        } else {
            $montant = RefData::tarif($idtype, $idmode);
            if ($montant === null) {
                return ['ok' => false, 'error' => 'Tarif introuvable pour ce type/mode'];
            }
        }

        $duree = RefData::modeDuration($idmode);
        if ($duree === null || $duree <= 0) {
            return ['ok' => false, 'error' => 'Durée mode paiement introuvable'];
        }

        $datefin = date('Y-m-d', strtotime($datedebut . ' +' . max(0, $duree - 1) . ' days'));

        try {
            $pdo->beginTransaction();

            $st = $pdo->prepare("\
                INSERT INTO abonnement (idsalle, idabonne, idtype, idmode, montant, datedebut, datefin, status, created_by)
                VALUES (1, :idabonne, :idtype, :idmode, :montant, :datedebut, :datefin, 'actif', :created_by)
            ");

            $st->execute([
                ':idabonne' => $idabonne,
                ':idtype' => $idtype,
                ':idmode' => $idmode,
                ':montant' => $montant,
                ':datedebut' => $datedebut,
                ':datefin' => $datefin,
                ':created_by' => ($createdBy > 0 ? $createdBy : null),
            ]);

            $id = (int)$pdo->lastInsertId();
            $pdo->commit();

            return ['ok' => true, 'idabonnement' => $id];
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            return ['ok' => false, 'error' => 'DB: ' . $e->getMessage()];
        }
    }
}
