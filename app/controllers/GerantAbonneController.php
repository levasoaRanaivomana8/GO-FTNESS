<?php

declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use Core\Auth;
use App\Models\Abonne;

/**
 * Espace Gérant: CRUD abonnés (ajout/modif/suppression).
 *
 * Sécurité:
 * - accès gérant uniquement
 * - formulaires protégés CSRF
 */
final class GerantAbonneController extends Controller
{
    public function index(): void
    {
        Auth::requireRole('gerant');

        $q = trim((string)($_GET['q'] ?? ''));
        $rows = Abonne::search($q);

        $this->render('gerant/abonnes/index', [
            'title'   => 'Gérer abonnés',
            'active'  => 'gerant_abonnes',
            'user'    => Auth::user(),
            'baseUrl' => rtrim(BASE_URL, '/'),
            'flash'   => $this->pullFlash(),
            'csrf'    => $this->csrfToken(),
            'q'       => $q,
            'rows'    => $rows,
        ]);
    }

    public function create(): void
    {
        Auth::requireRole('gerant');

        $this->render('gerant/abonnes/form', [
            'title'   => 'Nouvel abonné',
            'active'  => 'gerant_abonnes',
            'user'    => Auth::user(),
            'baseUrl' => rtrim(BASE_URL, '/'),
            'flash'   => $this->pullFlash(),
            'csrf'    => $this->csrfToken(),
            'mode'    => 'create',
            'row'     => null,
            'error'   => null,
        ]);
    }

    public function store(): void
    {
        Auth::requireRole('gerant');
        $this->requireCsrf();

        $data = [
            'nom'       => trim((string)($_POST['nom'] ?? '')),
            'prenom'    => trim((string)($_POST['prenom'] ?? '')),
            'tel'       => trim((string)($_POST['tel'] ?? '')),
            'adresse'   => trim((string)($_POST['adresse'] ?? '')),
            'datedebut' => trim((string)($_POST['datedebut'] ?? '')),
        ];

        if ($data['nom'] === '' || $data['prenom'] === '') {
            $this->render('gerant/abonnes/form', [
                'title'   => 'Nouvel abonné',
                'active'  => 'gerant_abonnes',
                'user'    => Auth::user(),
                'baseUrl' => rtrim(BASE_URL, '/'),
                'flash'   => $this->pullFlash(),
                'csrf'    => $this->csrfToken(),
                'mode'    => 'create',
                'row'     => $data,
                'error'   => 'Nom et prénom sont obligatoires.',
            ]);
            return;
        }

        Abonne::create($data);
        $this->flash('success', 'Abonné ajouté avec succès.');
        $this->redirect('/gerant/abonnes');
    }

    public function edit(): void
    {
        Auth::requireRole('gerant');

        $id = (int)($_GET['id'] ?? 0);
        $row = $id > 0 ? Abonne::find($id) : null;

        if (!$row) {
            http_response_code(404);
            exit('Abonné introuvable');
        }

        $this->render('gerant/abonnes/form', [
            'title'   => 'Modifier abonné',
            'active'  => 'gerant_abonnes',
            'user'    => Auth::user(),
            'baseUrl' => rtrim(BASE_URL, '/'),
            'flash'   => $this->pullFlash(),
            'csrf'    => $this->csrfToken(),
            'mode'    => 'edit',
            'row'     => $row,
            'error'   => null,
        ]);
    }

    public function update(): void
    {
        Auth::requireRole('gerant');
        $this->requireCsrf();

        $id = (int)($_POST['idabonne'] ?? 0);
        if ($id <= 0) {
            http_response_code(400);
            exit('Paramètre manquant');
        }

        $data = [
            'nom'       => trim((string)($_POST['nom'] ?? '')),
            'prenom'    => trim((string)($_POST['prenom'] ?? '')),
            'tel'       => trim((string)($_POST['tel'] ?? '')),
            'adresse'   => trim((string)($_POST['adresse'] ?? '')),
            'datedebut' => trim((string)($_POST['datedebut'] ?? '')),
        ];

        if ($data['nom'] === '' || $data['prenom'] === '') {
            $this->render('gerant/abonnes/form', [
                'title'   => 'Modifier abonné',
                'active'  => 'gerant_abonnes',
                'user'    => Auth::user(),
                'baseUrl' => rtrim(BASE_URL, '/'),
                'flash'   => $this->pullFlash(),
                'csrf'    => $this->csrfToken(),
                'mode'    => 'edit',
                'row'     => array_merge($data, ['idabonne' => $id]),
                'error'   => 'Nom et prénom sont obligatoires.',
            ]);
            return;
        }

        Abonne::update($id, $data);
        $this->flash('success', 'Abonné mis à jour.');
        $this->redirect('/gerant/abonnes');
    }

    public function delete(): void
    {
        Auth::requireRole('gerant');
        $this->requireCsrf();

        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            Abonne::delete($id);
        }

        $this->flash('success', 'Abonné supprimé.');
        $this->redirect('/gerant/abonnes');
    }
}
