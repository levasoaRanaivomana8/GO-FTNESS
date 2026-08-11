<?php

declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use Core\Auth;
use App\Models\Abonne;
use App\Models\Abonnement;
use App\Models\RefData;

final class AbonnementController extends Controller
{
    public function index(): void
    {
        Auth::requireRole('admin');

        $q = trim((string)($_GET['q'] ?? ''));
        $rows = Abonnement::search($q);

        $this->render('admin/abonnements/index', [
            'title'  => 'Abonnements / Factures',
            'active' => 'admin_abonnements',
            'user'   => Auth::user(),
            'baseUrl'=> rtrim(BASE_URL, '/'),
            'flash'  => $this->pullFlash(),
            'csrf'   => $this->csrfToken(),
            'q'      => $q,
            'rows'   => $rows,
        ]);
    }

    public function create(): void
    {
        Auth::requireRole('admin');

        $abonnes = Abonne::search('');
        $types   = RefData::types();
        $modes   = RefData::modes();

        $this->render('admin/abonnements/create', [
            'title'   => 'Nouveau abonnement',
            'active'  => 'admin_abonnements',
            'user'    => Auth::user(),
            'baseUrl' => rtrim(BASE_URL, '/'),
            'flash'   => $this->pullFlash(),
            'csrf'    => $this->csrfToken(),
            'abonnes' => $abonnes,
            'types'   => $types,
            'modes'   => $modes,
            'error'   => null,
            'old'     => null,
        ]);
    }

    public function store(): void
    {
        Auth::requireRole('admin');
        $this->requireCsrf();

        $idabonne = (int)($_POST['idabonne'] ?? 0);
        $idtype   = (int)($_POST['idtype'] ?? 0);
        $idmode   = (int)($_POST['idmode'] ?? 0);
        $montant  = trim((string)($_POST['montant'] ?? ''));
        $datedebut = trim((string)($_POST['datedebut'] ?? ''));

        $old = [
            'idabonne' => $idabonne,
            'idtype' => $idtype,
            'idmode' => $idmode,
            'montant' => $montant,
            'datedebut' => $datedebut,
        ];

        if ($idabonne <= 0 || $idtype <= 0 || $idmode <= 0) {
            $this->reRenderCreate('Veuillez sélectionner abonné, type et mode.', $old);
            return;
        }

        $result = Abonnement::create([
            'idabonne'  => $idabonne,
            'idtype'    => $idtype,
            'idmode'    => $idmode,
            'montant'   => $montant,
            'datedebut' => $datedebut,
            'created_by'=> (int)(Auth::user()['iduser'] ?? 0),
        ]);

        if (!$result['ok']) {
            $this->reRenderCreate($result['error'] ?? 'Erreur création abonnement.', $old);
            return;
        }

        $this->flash('success', 'Paiement enregistré. Facture générée.');
        $this->redirect('/admin/abonnements/show?id=' . (int)$result['idabonnement']);
    }

    public function show(): void
    {
        Auth::requireRole('admin');

        $id = (int)($_GET['id'] ?? 0);
        $row = $id > 0 ? Abonnement::find($id) : null;
        if (!$row) {
            http_response_code(404);
            exit('Abonnement introuvable');
        }

        $this->render('admin/abonnements/show', [
            'title'  => 'Facture ' . ($row['numero_facture'] ?? ''),
            'active' => 'admin_abonnements',
            'user'   => Auth::user(),
            'baseUrl'=> rtrim(BASE_URL, '/'),
            'flash'  => $this->pullFlash(),
            'csrf'   => $this->csrfToken(),
            'row'    => $row,
        ]);
    }

    private function reRenderCreate(string $error, array $old): void
    {
        $abonnes = Abonne::search('');
        $types   = RefData::types();
        $modes   = RefData::modes();

        $this->render('admin/abonnements/create', [
            'title'   => 'Nouveau abonnement',
            'active'  => 'admin_abonnements',
            'user'    => Auth::user(),
            'baseUrl' => rtrim(BASE_URL, '/'),
            'flash'   => $this->pullFlash(),
            'csrf'    => $this->csrfToken(),
            'abonnes' => $abonnes,
            'types'   => $types,
            'modes'   => $modes,
            'error'   => $error,
            'old'     => $old,
        ]);
    }
}
