<?php
require_once 'config.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

function getAuditData($filters = []) {
    $conn = getDBConnection();
    $sql = "SELECT type_action as action, date_action, num_compte as numero_compte, nom_client, solde_ancien as ancien_solde, solde_nouveau as nouveau_solde, utilisateur FROM audit_compte WHERE 1=1";
    $types = ""; $params = [];

    if (!empty($filters['action'])) { $sql .= " AND type_action = ?"; $types .= "s"; $params[] = $filters['action']; }
    if (!empty($filters['date_start'])) { $sql .= " AND DATE(date_action) >= ?"; $types .= "s"; $params[] = $filters['date_start']; }
    if (!empty($filters['date_end'])) { $sql .= " AND DATE(date_action) <= ?"; $types .= "s"; $params[] = $filters['date_end']; }
    if (!empty($filters['user'])) { $sql .= " AND utilisateur LIKE ?"; $types .= "s"; $params[] = '%'.$filters['user'].'%'; }

    $sql .= " ORDER BY date_action DESC";
    try {
        $stmt = $conn->prepare($sql);
        if ($types) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {
        error_log("getAuditData error: ".$e->getMessage());
        return [];
    }
}

function getAuditStats() {
    $conn = getDBConnection();
    $sql = "SELECT type_action as action, COUNT(*) as count FROM audit_compte GROUP BY type_action";
    try {
        $res = $conn->query($sql);
        $stats = ['ajout'=>0,'modification'=>0,'suppression'=>0,'total'=>0];
        while ($row = $res->fetch_assoc()) {
            $stats[$row['action']] = (int)$row['count'];
            $stats['total'] += (int)$row['count'];
        }
        return $stats;
    } catch (Exception $e) {
        error_log("getAuditStats error: ".$e->getMessage());
        return ['ajout'=>0,'modification'=>0,'suppression'=>0,'total'=>0];
    }
}

$filters = [];
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $filters = [
        'action'=>$_POST['action'] ?? '',
        'date_start'=>$_POST['date_start'] ?? '',
        'date_end'=>$_POST['date_end'] ?? '',
        'user'=>$_POST['user'] ?? ''
    ];
}

$auditData = getAuditData($filters);
$stats = getAuditStats();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Audit des Opérations</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <style>
        body{font-family:'Segoe UI',sans-serif; background:linear-gradient(135deg,#2b5876 0%,#4e4376 100%); color:#fff; min-height:100vh; margin:0; padding:2rem;}
        .container{max-width:1400px;margin:auto;padding:1rem;background:#ffffff10;border-radius:12px;backdrop-filter:blur(6px);box-shadow:0 4px 20px rgba(0,0,0,0.2);}
        .header{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;margin-bottom:2rem;}
        .header h1{font-size:2rem;display:flex;align-items:center;gap:0.5rem;}
        .header-actions .btn{margin-left:1rem;padding:0.5rem 1rem;border:none;border-radius:6px;cursor:pointer;transition:.3s;}
        .btn-primary{background:#00c9a7;color:#fff;}
        .btn-secondary{background:#4e54c8;color:#fff;}
        .btn:hover{opacity:.85;transform:scale(1.05);}
        .stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;margin-bottom:2rem;}
        .stat-card{background:#ffffff15;padding:1rem;border-radius:10px;transition:transform .3s;backdrop-filter:blur(4px);}
        .stat-card:hover{transform:scale(1.03);}
        .stat-header{display:flex;align-items:center;justify-content:space-between;}
        .stat-icon i{font-size:1.8rem;color:#00ffe0;}
        .stat-value{font-size:2rem;font-weight:bold;margin:.5rem 0;}
        .stat-change{font-size:.9rem;color:#ddd;}
        .audit-table{width:100%;border-collapse:collapse;background:#ffffff0a;backdrop-filter:blur(3px);color:#fff;border-radius:8px;overflow:hidden;box-shadow:0 0 10px #00000040;}
        .audit-table th,.audit-table td{padding:.75rem;text-align:left;border-bottom:1px solid #ffffff20;}
        .audit-table th{background:#4e54c8;cursor:pointer;user-select:none;}
        .audit-table tbody tr:hover{background:#ffffff10;}
        .action-badge{padding:.3rem .6rem;border-radius:20px;font-weight:bold;display:inline-flex;align-items:center;gap:.5rem;}
        .action-ajout{background:#27ae60;color:#fff;}
        .action-modification{background:#f1c40f;color:#000;}
        .action-suppression{background:#e74c3c;color:#fff;}
        .amount-positive{color:#2ecc71;font-weight:bold;}
        .amount-negative{color:#e74c3c;font-weight:bold;}
        .no-data{text-align:center;margin-top:2rem;color:#eee;}
        .no-data i{font-size:3rem;margin-bottom:.5rem;color:#ccc;}
        #searchUser{padding:8px;margin:1rem 0;border-radius:5px;width:100%;max-width:300px;}
        @media (max-width:768px){ .header{flex-direction:column;align-items:flex-start;gap:1rem;} }
    </style>
</head>
<body>
<div class="container">

  <div class="header">
    <h1><i class="fas fa-shield-alt"></i> Audit des Opérations</h1>
    <div class="header-actions">
      <a href="admin.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Retour Admin</a>
      <button class="btn btn-primary" onclick="location.reload()"><i class="fas fa-sync-alt"></i> Actualiser</button>
    </div>
  </div>

  <input type="text" id="searchUser" placeholder="Rechercher un utilisateur...">

  <?php if (empty($auditData)): ?>
    <div class="no-data"><i class="fas fa-inbox"></i><h3>Aucune donnée d'audit trouvée</h3><p>...</p></div>
  <?php else: ?>
  <table class="audit-table" id="auditTable">
    <thead><tr>
      <?php $cols=['Type d’Action','Date','N° Compte','Nom Client','Ancien Solde','Nouveau Solde','Utilisateur']; ?>
      <?php foreach($cols as $i=>$c): ?>
        <th onclick="sortTable(<?= $i ?>)"><?= $c ?> <i class="fas fa-sort"></i></th>
      <?php endforeach; ?>
    </tr></thead>
    <tbody id="auditTableBody">
      <?php foreach($auditData as $row): 
        $old=number_format($row['ancien_solde'],2,',',' ');
        $new=number_format($row['nouveau_solde'],2,',',' ');
        $diff = floatval($row['nouveau_solde']) - floatval($row['ancien_solde']);
        $diffClass = $diff>=0?'amount-positive':'amount-negative';
        $icon = ['ajout'=>'plus','modification'=>'edit','suppression'=>'trash'][$row['action']] ?? 'question';
      ?>
      <tr>
        <td><span class="action-badge action-<?= $row['action'] ?>"><i class="fas fa-<?= $icon ?>"></i> <?= ucfirst($row['action']) ?></span></td>
        <td><?= date('d/m/Y H:i',strtotime($row['date_action'])) ?></td>
        <td><strong><?= htmlspecialchars($row['numero_compte']) ?></strong></td>
        <td><?= htmlspecialchars($row['nom_client']) ?></td>
        <td class="amount-cell"><?= $old ?> AR</td>
        <td class="amount-cell">
          <?php if($row['action']!=='suppression'): ?>
            <?= $new ?> AR
            <?php if($row['action']==='modification'): ?>
              <br><small class="<?= $diffClass ?>">(<?= ($diff>=0?'+':'') . number_format($diff,2,',',' ') ?> AR)</small>
            <?php endif; ?>
          <?php else: ?>
            <span class="amount-negative">Supprimé</span>
          <?php endif; ?>
        </td>
        <td><?= htmlspecialchars($row['utilisateur']) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>

</div>

<script>
// Rechercher utilisateur
document.getElementById('searchUser').addEventListener('input', function(){
  const val = this.value.toLowerCase();
  document.querySelectorAll('#auditTableBody tr').forEach(r=>{
    r.style.display = r.cells[6].textContent.toLowerCase().includes(val) ? '' : 'none';
  });
});

// Tri colonne
function sortTable(n){
  const table = document.getElementById('auditTable');
  let rows = Array.from(table.tBodies[0].rows);
  const asc = table.getAttribute('data-sort-col')!=n || table.getAttribute('data-sort-dir')==='desc';
  rows.sort((a,b)=>{
    let x = a.cells[n].textContent.trim(), y = b.cells[n].textContent.trim();
    return isNaN(x)||isNaN(y) ? x.localeCompare(y) : parseFloat(x.replace(/[^0-9\+\-\.]/g,'')) - parseFloat(y.replace(/[^0-9\+\-\.]/g,''));
  });
  if(!asc) rows.reverse();
  table.tBodies[0].append(...rows);
  table.setAttribute('data-sort-col',n);
  table.setAttribute('data-sort-dir',asc?'asc':'desc');
}

// Chart.js
const ctx = document.getElementById('statsChart');
new Chart(ctx, {
  type: 'doughnut',
  data: {
    labels: ['Insertions','Modifications','Suppressions'],
    datasets: [{
      data: [<?= $stats['ajout'] ?>,<?= $stats['modification'] ?>,<?= $stats['suppression'] ?>],
      backgroundColor: ['#27ae60','#f1c40f','#e74c3c']
    }]
  },
  options: {plugins:{legend:{labels:{color:'#fff'}}}}
});
</script>

</body>
</html>
