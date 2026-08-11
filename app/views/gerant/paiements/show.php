<?php
/** @var string $title */
/** @var array $user */
/** @var array $row */

$content = function () use ($row, $baseUrl) {
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0">Facture <?= htmlspecialchars($row['numero_facture'] ?? '') ?></h4>
        <div class="text-muted small">Créée le <?= htmlspecialchars((string)($row['created_at'] ?? '')) ?></div>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-light" href="<?= htmlspecialchars($baseUrl) ?>/gerant/paiements">← Retour</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-12 col-lg-6">
        <div class="card bg-dark border-0 p-3 h-100">
            <div class="fw-semibold mb-2">Abonné</div>
            <div><?= htmlspecialchars(($row['nom'] ?? '') . ' ' . ($row['prenom'] ?? '')) ?></div>
            <div class="text-muted small">Tel: <?= htmlspecialchars($row['tel'] ?? '-') ?></div>
            <div class="text-muted small">Adresse: <?= htmlspecialchars($row['adresse'] ?? '-') ?></div>
        </div>
    </div>

    <div class="col-12 col-lg-6">
        <div class="card bg-dark border-0 p-3 h-100">
            <div class="fw-semibold mb-2">Abonnement</div>
            <div>Type: <span class="fw-semibold"><?= htmlspecialchars($row['nomtype'] ?? '-') ?></span></div>
            <div>Mode: <span class="fw-semibold"><?= htmlspecialchars($row['nommode'] ?? '-') ?></span> <span class="text-muted small">(<?= (int)($row['duree_jours'] ?? 0) ?> j)</span></div>
            <div class="mt-2">Montant: <span class="fw-semibold"><?= number_format((float)$row['montant'], 0, ',', ' ') ?> Ar</span></div>
            <div class="text-muted small mt-1">Période: <?= htmlspecialchars($row['datedebut']) ?> → <?= htmlspecialchars($row['datefin']) ?></div>
            <div class="text-muted small">Status: <?= htmlspecialchars($row['status']) ?></div>
            <div class="text-muted small">Créé par: <?= htmlspecialchars($row['created_by_username'] ?? '-') ?></div>
        </div>
    </div>
</div>

<div class="alert alert-info mt-3">
    ✅ Numéro facture auto: trigger <code>trg_abonnement_facture_ai</code>.<br>
    Prochaine étape possible: génération PDF (Dompdf) + bouton « Imprimer ». 😉
</div>

<?php
};

require __DIR__ . '/../../layouts/app.php';
