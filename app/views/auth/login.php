<?php

/** @var string $baseUrl */
/** @var string|null $error */
/** @var string|null $csrf */
?>
<!doctype html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | GO-FITNESS</title>

    <link rel="stylesheet" href="<?= htmlspecialchars($baseUrl) ?>/assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= htmlspecialchars($baseUrl) ?>/assets/css/app.css">
    <link rel="stylesheet" href="<?= htmlspecialchars($baseUrl) ?>/assets/vendor/sweetalert2/sweetalert2.min.css">
    <link rel="stylesheet" href="<?= htmlspecialchars($baseUrl) ?>/assets/vendor/icons-1.13.1/font/bootstrap-icons.min.css">
</head>

<body class="bg-dark d-flex justify-content-center align-items-center vh-100">

    <div class="card p-4 shadow" style="width:420px;">
        <div class="text-center mb-3">
            <img src="<?= htmlspecialchars($baseUrl) ?>/assets/images/logo.png" alt="GO-FITNESS" style="height:120px;">
        </div>

        <h4 class="gf-brand-title text-center m-0">GO-FITNESS</h4>
        <p class="text-center text-muted mb-3">Connexion sécurisée</p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= htmlspecialchars(BASE_URL) ?>/login" novalidate>
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>">

            <div class="mb-3">
                <label class="form-label">Username, email ou matricule</label>
                <input name="username" class="form-control" autocomplete="username" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Mot de passe</label>

                <div class="position-relative">
                    <input
                        type="password"
                        name="password"
                        id="passwordInput"
                        class="form-control pe-5"
                        autocomplete="current-password"
                        required>

                    <button
                        type="button"
                        id="togglePassword"
                        class="btn p-0 border-0 bg-transparent"
                        style="position:absolute; top:50%; right:14px; transform:translateY(-50%); line-height:1;"
                        aria-label="Afficher / masquer le mot de passe">
                        <i class="bi bi-eye-slash" id="togglePasswordIcon" style="font-size:18px; color:#777;"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-danger w-100">Se connecter</button>
            <div class="text-center mt-3">
                <button type="button" class="btn btn-link p-0" id="forgotBtn">Mot de passe oublié ?</button>
                <div class="small text-muted mt-1">Contactez l’administrateur via WhatsApp.</div>
            </div>
        </form>
    </div>

    <!-- JS -->
    <script src="<?= htmlspecialchars($baseUrl) ?>/assets/vendor/sweetalert2/sweetalert2.all.min.js"></script>
    <script>
        (() => {
            // Toggle password
            const input = document.getElementById('passwordInput');
            const btn = document.getElementById('togglePassword');
            const icon = document.getElementById('togglePasswordIcon');

            if (btn && input && icon) {
                btn.addEventListener('click', () => {
                    const isPassword = input.type === 'password';
                    input.type = isPassword ? 'text' : 'password';
                    icon.classList.toggle('bi-eye');
                    icon.classList.toggle('bi-eye-slash');
                });
            }

            // Forgot popup (WhatsApp only)
            const forgotBtn = document.getElementById('forgotBtn');
            if (forgotBtn && window.Swal) {
                forgotBtn.addEventListener('click', () => {
                    Swal.fire({
                        background: '#0f141b',
                        color: '#fff',
                        showConfirmButton: false,
                        showCloseButton: true,
                        width: 420,
                        html: `
            <div style="text-align:center">
              <h5 style="font-weight:800;margin-bottom:10px;">Mot de passe oublié ?</h5>
              <p style="color:rgba(255,255,255,.70);font-size:14px;margin-bottom:16px;">
                Contactez l’administrateur pour réinitialiser votre accès.
              </p>
              <a target="_blank"
                 href="https://wa.me/261385911846"
                 style="display:block;background:#25D366;color:#fff;padding:10px;border-radius:10px;text-decoration:none;font-weight:700;">
                📱 Contacter via WhatsApp
              </a>
            </div>
          `
                    });
                });
            }
        })();
    </script>

</body>

</html>
