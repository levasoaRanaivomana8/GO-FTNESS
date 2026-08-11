<?php
/** @var string $title */
/** @var array $user */
/** @var string $q */
/** @var array $rows */

$content = function () use ($q, $rows, $baseUrl) {
?>

<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0">Abonnements / Factures</h4>
        <div class="text-muted small">Recherche par facture, nom, prénom, téléphone.</div>
    </div>
    <a class="btn btn-primary" href="<?= htmlspecialchars($baseUrl) ?>/admin/abonnements/create">+ Nouvel abonnement</a>
</div>

<form class="row g-2 mb-3" method="get" action="<?= htmlspecialchars($baseUrl) ?>/admin/abonnements">
    <div class="col-12 col-md-6">
        <input class="form-control" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="ex: GF-000123, Rakoto, 034...">
    </div>
    <div class="col-12 col-md-3 d-grid">
        <button class="btn btn-outline-secondary" type="submit">Rechercher</button>
    </div>
    <div class="col-12 col-md-3 d-grid">
        <a class="btn btn-outline-light" href="<?= htmlspecialchars($baseUrl) ?>/admin/abonnements">Réinitialiser</a>
    </div>
</form>

<div class="table-responsive">
    <table class="table table-dark table-hover align-middle">
        <thead>
            <tr>
                <th>#</th>
                <th>Facture</th>
                <th>Abonné</th>
                <th>Type</th>
                <th>Mode</th>
                <th>Montant</th>
                <th>Période</th>
                <th>Status</th>
                <th class="text-end">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= (int)$r['idabonnement'] ?></td>
                    <td class="fw-semibold"><?= htmlspecialchars($r['numero_facture'] ?? '-') ?></td>
                    <td><?= htmlspecialchars(($r['nom'] ?? '') . ' ' . ($r['prenom'] ?? '')) ?></td>
                    <td><?= htmlspecialchars($r['nomtype'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($r['nommode'] ?? '-') ?></td>
                    <td><?= number_format((float)$r['montant'], 0, ',', ' ') ?> Ar</td>
                    <td>
                        <span class="small text-muted">Du</span> <?= htmlspecialchars($r['datedebut']) ?><br>
                        <span class="small text-muted">Au</span> <?= htmlspecialchars($r['datefin']) ?>
                    </td>
                    <td><?= htmlspecialchars($r['status']) ?></td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-info" href="<?= htmlspecialchars($baseUrl) ?>/admin/abonnements/show?id=<?= (int)$r['idabonnement'] ?>">Voir</a>
                    </td>
                </tr>
            <?php endforeach; ?>

            <?php if (!$rows): ?>
                <tr>
                    <td colspan="9" class="text-center text-muted py-4">Aucun abonnement trouvé.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
};

require __DIR__ . '/../../layouts/app.php';
