<?php
/** @var string $title */
/** @var array $user */
/** @var array $abonnes */
/** @var array $types */
/** @var array $modes */
/** @var string|null $error */
/** @var array|null $old */
/** @var string $csrf */

$old = $old ?? [];

$content = function () use ($abonnes, $types, $modes, $error, $old, $csrf, $baseUrl) {
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0">Nouveau abonnement</h4>
        <div class="text-muted small">Montant auto depuis <code>tarifs</code> si vide. Numéro facture auto (trigger).</div>
    </div>
    <a class="btn btn-outline-light" href="<?= htmlspecialchars($baseUrl) ?>/admin/abonnements">← Retour</a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post" action="<?= htmlspecialchars($baseUrl) ?>/admin/abonnements/create" class="card bg-dark border-0 p-3">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>">
    <div class="row g-3">
        <div class="col-12">
            <label class="form-label">Abonné *</label>
            <select class="form-select" name="idabonne" required>
                <option value="">-- choisir --</option>
                <?php foreach ($abonnes as $a): ?>
                    <?php $sel = ((int)($old['idabonne'] ?? 0) === (int)$a['idabonne']) ? 'selected' : ''; ?>
                    <option value="<?= (int)$a['idabonne'] ?>" <?= $sel ?>>
                        #<?= (int)$a['idabonne'] ?> - <?= htmlspecialchars($a['nom'] . ' ' . $a['prenom']) ?><?= ($a['tel'] ? ' (' . htmlspecialchars($a['tel']) . ')' : '') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-12 col-md-6">
            <label class="form-label">Type *</label>
            <select class="form-select" name="idtype" required>
                <option value="">-- choisir --</option>
                <?php foreach ($types as $t): ?>
                    <?php $sel = ((int)($old['idtype'] ?? 0) === (int)$t['idtype']) ? 'selected' : ''; ?>
                    <option value="<?= (int)$t['idtype'] ?>" <?= $sel ?>><?= htmlspecialchars($t['nomtype']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-12 col-md-6">
            <label class="form-label">Mode *</label>
            <select class="form-select" name="idmode" required>
                <option value="">-- choisir --</option>
                <?php foreach ($modes as $m): ?>
                    <?php $sel = ((int)($old['idmode'] ?? 0) === (int)$m['idmode']) ? 'selected' : ''; ?>
                    <option value="<?= (int)$m['idmode'] ?>" <?= $sel ?>><?= htmlspecialchars($m['nommode']) ?> (<?= (int)$m['duree_jours'] ?> j)</option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-12 col-md-6">
            <label class="form-label">Date début (optionnel)</label>
            <input type="date" class="form-control" name="datedebut" value="<?= htmlspecialchars((string)($old['datedebut'] ?? '')) ?>">
        </div>

        <div class="col-12 col-md-6">
            <label class="form-label">Montant (optionnel)</label>
            <input class="form-control" name="montant" value="<?= htmlspecialchars((string)($old['montant'] ?? '')) ?>" placeholder="ex: 80000">
            <div class="form-text text-muted">Raha avelanao banga dia haka automatique amin'ny <code>tarifs</code>.</div>
        </div>

        <div class="col-12 d-grid">
            <button class="btn btn-primary" type="submit">Créer abonnement</button>
        </div>
    </div>
</form>

<?php
};

require __DIR__ . '/../../layouts/app.php';
