<?php

declare(strict_types=1);

namespace App\Models;

use Config\DB;
use PDO;

final class AdminDashboard
{
    /** Period resolver (filter). */
    public static function resolvePeriod(array $q): array
    {
        // start/end (YYYY-MM-DD) priorité si custom
        $start = isset($q['start']) ? trim((string)$q['start']) : '';
        $end   = isset($q['end'])   ? trim((string)$q['end'])   : '';

        if ($start !== '' && $end !== '') {
            return [$start, $end];
        }

        $period = isset($q['period']) ? (string)$q['period'] : 'month';

        // Par défaut: mois courant
        $today = date('Y-m-d');

        if ($period === '7d') {
            return [date('Y-m-d', strtotime('-6 days')), $today];
        }

        if ($period === '30d') {
            return [date('Y-m-d', strtotime('-29 days')), $today];
        }

        if ($period === '12m') {
            return [date('Y-m-01', strtotime('-11 months')), $today];
        }

        // month (mois courant)
        return [date('Y-m-01'), $today];
    }

    /** Month resolver for charts pie/bar (YYYY-MM). */
    public static function resolveMonth(array $q): string
    {
        $m = isset($q['month']) ? trim((string)$q['month']) : '';
        if ($m !== '' && preg_match('/^\d{4}-\d{2}$/', $m)) return $m;
        return date('Y-m');
    }

    public static function kpi(string $start, string $end): array
    {
        $pdo = DB::pdo();

        // Abonnés actifs (safe): pas annule + datefin>=today
        $sqlActive = "
            SELECT COUNT(DISTINCT a.idabonne) AS n
            FROM abonnement ab
            JOIN abonne a ON a.idabonne = ab.idabonne
            WHERE ab.status <> 'annule'
              AND ab.datefin >= CURDATE()
        ";
        $active = (int)$pdo->query($sqlActive)->fetchColumn();

        // Revenu sur période (date paiement = created_at)
        $sqlRevenuePeriod = "
            SELECT COALESCE(SUM(montant),0) AS total
            FROM abonnement
            WHERE status <> 'annule'
              AND DATE(created_at) BETWEEN :start AND :end
        ";
        $st = $pdo->prepare($sqlRevenuePeriod);
        $st->execute([':start' => $start, ':end' => $end]);
        $periodRevenue = (float)$st->fetchColumn();

        // Revenu global (sans annule)
        $sqlTotal = "SELECT COALESCE(SUM(montant),0) FROM abonnement WHERE status <> 'annule'";
        $totalRevenue = (float)$pdo->query($sqlTotal)->fetchColumn();

        // Revenu mois courant
        $monthStart = date('Y-m-01');
        $monthEnd   = date('Y-m-t');
        $st = $pdo->prepare("
            SELECT COALESCE(SUM(montant),0)
            FROM abonnement
            WHERE status <> 'annule'
              AND DATE(created_at) BETWEEN :s AND :e
        ");
        $st->execute([':s' => $monthStart, ':e' => $monthEnd]);
        $currentMonthRevenue = (float)$st->fetchColumn();

        return [
            'activeSubscribers'    => $active,
            'revenuePeriod'        => $periodRevenue,
            'revenueCurrentMonth'  => $currentMonthRevenue,
            'revenueTotal'         => $totalRevenue,
        ];
    }

    public static function compareCurrentVsPreviousMonth(): array
    {
        $pdo = DB::pdo();

        $curStart = date('Y-m-01');
        $curEnd   = date('Y-m-t');

        $prevStart = date('Y-m-01', strtotime('-1 month'));
        $prevEnd   = date('Y-m-t',  strtotime('-1 month'));

        $st = $pdo->prepare("
            SELECT COALESCE(SUM(montant),0)
            FROM abonnement
            WHERE status <> 'annule'
              AND DATE(created_at) BETWEEN :s AND :e
        ");

        $st->execute([':s' => $curStart, ':e' => $curEnd]);
        $cur = (float)$st->fetchColumn();

        $st->execute([':s' => $prevStart, ':e' => $prevEnd]);
        $prev = (float)$st->fetchColumn();

        $pct = ($prev > 0) ? (($cur - $prev) / $prev) * 100.0 : null;

        return [
            'current' => $cur,
            'previous' => $prev,
            'pct'     => $pct, // null si prev=0
        ];
    }

    /** Line chart: revenus par mois (N derniers mois) via created_at */
    public static function revenueByMonth(int $months = 12): array
    {
        $pdo = DB::pdo();

        $start = date('Y-m-01', strtotime('-' . ($months - 1) . ' months'));
        $end   = date('Y-m-t');

        $sql = "
            SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym,
                   COALESCE(SUM(montant),0) AS total
            FROM abonnement
            WHERE status <> 'annule'
              AND DATE(created_at) BETWEEN :s AND :e
            GROUP BY ym
            ORDER BY ym ASC
        ";
        $st = $pdo->prepare($sql);
        $st->execute([':s' => $start, ':e' => $end]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        // compléter les mois manquants
        $map = [];
        foreach ($rows as $r) $map[$r['ym']] = (float)$r['total'];

        $labels = [];
        $data   = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $ym = date('Y-m', strtotime("-$i months"));
            $labels[] = $ym;
            $data[]   = $map[$ym] ?? 0.0;
        }

        return ['labels' => $labels, 'data' => $data];
    }

    /** Pie: % revenus par type (Normal/Premium/VIP) pour un mois donné (YYYY-MM) */
    public static function typeSharePie(string $month): array
    {
        $pdo = DB::pdo();
        $start = $month . '-01';
        $end   = date('Y-m-t', strtotime($start));

        $sql = "
            SELECT tp.nomtype AS label, COALESCE(SUM(ab.montant),0) AS total
            FROM abonnement ab
            JOIN typepaiement tp ON tp.idtype = ab.idtype
            WHERE ab.status <> 'annule'
              AND DATE(ab.created_at) BETWEEN :s AND :e
            GROUP BY tp.nomtype
            ORDER BY tp.idtype ASC
        ";
        $st = $pdo->prepare($sql);
        $st->execute([':s' => $start, ':e' => $end]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        $labels = [];
        $values = [];
        $sum = 0.0;

        foreach ($rows as $r) {
            $labels[] = $r['label'];
            $v = (float)$r['total'];
            $values[] = $v;
            $sum += $v;
        }

        $percent = array_map(fn($v) => $sum > 0 ? round(($v / $sum) * 100, 1) : 0.0, $values);

        return [
            'labels'  => $labels,
            'values'  => $values,
            'percent' => $percent,
            'sum'     => $sum,
        ];
    }

    /** Bar: Mensuel vs Séance par type, pour un mois (YYYY-MM) */
    public static function modeByTypeBar(string $month): array
    {
        $pdo = DB::pdo();
        $start = $month . '-01';
        $end   = date('Y-m-t', strtotime($start));

        // On agrège par type + mode
        $sql = "
            SELECT tp.nomtype AS type,
                   mp.nommode AS mode,
                   COUNT(*) AS n,
                   COALESCE(SUM(ab.montant),0) AS total
            FROM abonnement ab
            JOIN typepaiement tp ON tp.idtype = ab.idtype
            JOIN modepaiement mp ON mp.idmode = ab.idmode
            WHERE ab.status <> 'annule'
              AND DATE(ab.created_at) BETWEEN :s AND :e
            GROUP BY tp.nomtype, mp.nommode
            ORDER BY tp.idtype, mp.idmode
        ";
        $st = $pdo->prepare($sql);
        $st->execute([':s' => $start, ':e' => $end]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        $types = ['Normal', 'Premium', 'VIP'];
        $mensuel = array_fill(0, count($types), 0);
        $seance  = array_fill(0, count($types), 0);

        foreach ($rows as $r) {
            $idx = array_search($r['type'], $types, true);
            if ($idx === false) continue;

            $mode = (string)$r['mode'];
            $count = (int)$r['n'];

            if (stripos($mode, 'Mensuel') !== false) $mensuel[$idx] = $count;
            if (stripos($mode, 'Séance') !== false || stripos($mode, 'Seance') !== false) $seance[$idx] = $count;
        }

        return [
            'labels'  => $types,
            'mensuel' => $mensuel,
            'seance'  => $seance,
        ];
    }

    /** Table abonnés actifs triés jours restants (prend l’abonnement actif le plus proche de fin par abonné) */
    public static function activeSubscribersTable(): array
    {
        $pdo = DB::pdo();

        // MariaDB 10.4 supporte window functions ✅
        $sql = "
            SELECT *
            FROM (
                SELECT
                    ab.idabonnement,
                    a.nom,
                    a.prenom,
                    tp.nomtype AS type,
                    mp.nommode AS mode,
                    ab.datedebut,
                    ab.datefin,
                    DATEDIFF(ab.datefin, CURDATE()) AS jours_restants,
                    ROW_NUMBER() OVER (PARTITION BY a.idabonne ORDER BY ab.datefin ASC) AS rn
                FROM abonnement ab
                JOIN abonne a ON a.idabonne = ab.idabonne
                JOIN typepaiement tp ON tp.idtype = ab.idtype
                JOIN modepaiement mp ON mp.idmode = ab.idmode
                WHERE ab.status <> 'annule'
                  AND ab.datefin >= CURDATE()
            ) t
            WHERE t.rn = 1
            ORDER BY t.jours_restants ASC, t.nom ASC
            LIMIT 200
        ";

        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function alerts(): array
    {
        $pdo = DB::pdo();

        $sqlExpire = "
            SELECT COUNT(*) 
            FROM abonnement 
            WHERE status <> 'annule' AND datefin < CURDATE()
        ";
        $expired = (int)$pdo->query($sqlExpire)->fetchColumn();

        $sqlSoon5 = "
            SELECT COUNT(*)
            FROM abonnement
            WHERE status <> 'annule'
              AND datefin >= CURDATE()
              AND DATEDIFF(datefin, CURDATE()) <= 5
        ";
        $soon5 = (int)$pdo->query($sqlSoon5)->fetchColumn();

        $sqlSoon10 = "
            SELECT COUNT(*)
            FROM abonnement
            WHERE status <> 'annule'
              AND datefin >= CURDATE()
              AND DATEDIFF(datefin, CURDATE()) <= 10
        ";
        $soon10 = (int)$pdo->query($sqlSoon10)->fetchColumn();

        return [
            'expired' => $expired,
            'soon5'   => $soon5,
            'soon10'  => $soon10,
        ];
    }
}
