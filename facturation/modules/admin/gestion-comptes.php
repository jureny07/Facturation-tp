<?php
// modules/admin/gestion-comptes.php — Liste des comptes utilisateurs

require_once __DIR__ . '/../../auth/session.php';
exiger_role(ROLE_SUPERADMIN);
require_once __DIR__ . '/../../includes/fonctions-auth.php';

$utilisateurs = charger_utilisateurs();

$titre = "Gestion des Comptes";
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
  <h2>⊛ Gestion des Comptes Utilisateurs</h2>
  <p><?= count($utilisateurs) ?> compte(s) enregistré(s)</p>
</div>

<div style="margin-bottom:1rem;">
  <a href="/facturation/modules/admin/ajouter-compte.php" class="btn btn-primary">⊕ Ajouter un Compte</a>
</div>

<div class="card">
  <div class="table-wrapper">
    <table>
      <thead>
        <tr>
          <th>Identifiant</th>
          <th>Nom complet</th>
          <th>Rôle</th>
          <th>Créé le</th>
          <th>Statut</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($utilisateurs as $u): ?>
        <tr>
          <td class="td-mono"><?= htmlspecialchars($u['identifiant']) ?></td>
          <td><?= htmlspecialchars($u['nom_complet']) ?></td>
          <td>
            <span class="badge <?=
              $u['role'] === ROLE_SUPERADMIN ? 'badge-warn' :
              ($u['role'] === ROLE_MANAGER   ? 'badge-ok'   : '')
            ?>"><?= ucfirst($u['role']) ?></span>
          </td>
          <td class="td-mono"><?= htmlspecialchars($u['date_creation']) ?></td>
          <td>
            <span class="badge <?= $u['actif'] ? 'badge-ok' : 'badge-danger' ?>">
              <?= $u['actif'] ? 'Actif' : 'Inactif' ?>
            </span>
          </td>
          <td>
            <?php if ($u['identifiant'] !== $_SESSION['identifiant']): ?>
            <form method="POST" action="/facturation/modules/admin/supprimer-compte.php"
                  onsubmit="return confirm('Supprimer le compte « <?= htmlspecialchars($u['identifiant']) ?> » ?');">
              <input type="hidden" name="identifiant" value="<?= htmlspecialchars($u['identifiant']) ?>">
              <button type="submit" class="btn btn-danger" style="padding:.3rem .7rem;font-size:.78rem;">✕ Supprimer</button>
            </form>
            <?php else: ?>
            <span style="color:var(--text-muted);font-size:.78rem;">(vous-même)</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
