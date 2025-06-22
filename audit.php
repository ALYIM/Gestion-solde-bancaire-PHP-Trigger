<?php
require_once 'config.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
$utilisateur = $_SESSION['username'] ?? 'Système';

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Journal d'Audit - Administration</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 280px;
            height: 100vh;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-right: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            transition: transform 0.3s ease;
        }

        .sidebar.collapsed {
            transform: translateX(-240px);
        }

        .sidebar-header {
            padding: 2rem 1.5rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .admin-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(45deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            font-weight: bold;
        }

        .admin-info h3 {
            font-size: 1.1rem;
            margin-bottom: 0.25rem;
        }

        .admin-info p {
            font-size: 0.9rem;
            color: #666;
        }

        .nav-menu {
            padding: 1rem 0;
        }

        .nav-item {
            display: flex;
            align-items: center;
            padding: 1rem 1.5rem;
            color: #555;
            text-decoration: none;
            transition: all 0.3s ease;
            cursor: pointer;
            border-left: 3px solid transparent;
        }

        .nav-item:hover, .nav-item.active {
            background: rgba(102, 126, 234, 0.1);
            color: #667eea;
            border-left-color: #667eea;
        }

        .nav-item i {
            width: 20px;
            margin-right: 1rem;
            font-size: 1.1rem;
        }

        .main-content {
            margin-left: 280px;
            padding: 2rem;
            transition: margin-left 0.3s ease;
        }

        .main-content.expanded {
            margin-left: 40px;
        }

        .top-bar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 1rem 2rem;
            border-radius: 20px;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .menu-toggle {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #667eea;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 10px;
            transition: background 0.3s ease;
        }

        .menu-toggle:hover {
            background: rgba(102, 126, 234, 0.1);
        }

        .search-bar {
            flex: 1;
            max-width: 400px;
            margin: 0 2rem;
            position: relative;
        }

        .search-bar input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 3rem;
            border: 2px solid rgba(102, 126, 234, 0.2);
            border-radius: 15px;
            font-size: 1rem;
            background: rgba(255, 255, 255, 0.8);
            transition: all 0.3s ease;
        }

        .search-bar input:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .search-bar i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #667eea;
        }

        .user-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 15px;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
        }

        .btn-primary {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.9);
            color: #667eea;
            border: 2px solid rgba(102, 126, 234, 0.2);
        }

        .btn-secondary:hover {
            background: rgba(102, 126, 234, 0.1);
            border-color: #667eea;
        }

        .btn-danger {
            background: linear-gradient(45deg, #e74c3c, #c0392b);
            color: white;
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(231, 76, 60, 0.3);
        }

        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 2rem;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 45px rgba(0, 0, 0, 0.15);
        }

        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
        }

        .card-icon {
            width: 50px;
            height: 50px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .card-icon.users { background: linear-gradient(45deg, #4facfe, #00f2fe); }
        .card-icon.accounts { background: linear-gradient(45deg, #43e97b, #38f9d7); }
        .card-icon.transactions { background: linear-gradient(45deg, #fa709a, #fee140); }
        .card-icon.audit { background: linear-gradient(45deg, #a8edea, #fed6e3); }

        .card-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #333;
        }

        .card-value {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 0.5rem;
        }

        .card-subtitle {
            font-size: 0.9rem;
            color: #666;
        }

        .content-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid rgba(102, 126, 234, 0.1);
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #333;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .data-table th {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            position: sticky;
            top: 0;
        }

        .data-table td {
            padding: 1rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            transition: background 0.3s ease;
        }

        .data-table tr:hover td {
            background: rgba(102, 126, 234, 0.05);
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .action-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .action-ajout {
            background: rgba(39, 174, 96, 0.1);
            color: #27ae60;
            border: 1px solid rgba(39, 174, 96, 0.2);
        }

        .action-modification {
            background: rgba(241, 196, 15, 0.1);
            color: #f39c12;
            border: 1px solid rgba(241, 196, 15, 0.2);
        }

        .action-suppression {
            background: rgba(231, 76, 60, 0.1);
            color: #e74c3c;
            border: 1px solid rgba(231, 76, 60, 0.2);
        }

        .amount-positive {
            color: #27ae60;
            font-weight: bold;
        }

        .amount-negative {
            color: #e74c3c;
            font-weight: bold;
        }

        .no-data {
            text-align: center;
            padding: 3rem;
            color: #666;
            font-style: italic;
        }

        .no-data i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #aaa;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .stat-title {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 0.5rem;
        }

        .stat-value {
            font-size: 1.8rem;
            font-weight: bold;
            color: #667eea;
        }

        .stat-change {
            font-size: 0.8rem;
            color: #666;
            margin-top: 0.5rem;
        }

        .filter-form {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 1.5rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
        }

        .form-row {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .form-group {
            flex: 1;
            min-width: 0;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #555;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid rgba(102, 126, 234, 0.2);
            border-radius: 10px;
            font-size: 1rem;
            background: rgba(255, 255, 255, 0.9);
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .form-row {
                flex-direction: column;
                gap: 1rem;
            }
        }
    </style>
</head>


<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="admin-avatar"><?php echo strtoupper(substr($_SESSION['username'] ?? 'A', 0, 1)); ?></div>
            <div class="admin-info">
                <h3><?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></h3>
                <p>Administrateur</p>
            </div>
        </div>
        
        <nav class="nav-menu">
            <a href="admin.php" class="nav-item">
                <i class="fas fa-tachometer-alt"></i>
                Dashboard
            </a>
            <a href="#" class="nav-item active">
                <i class="fas fa-clipboard-list"></i>
                Journal d'Audit
            </a>
            <a href="logout.php" class="nav-item" style="margin-top: 2rem; color: #e74c3c;">
                <i class="fas fa-sign-out-alt"></i>
                Déconnexion
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <!-- Top Bar -->
        <div class="top-bar">
            <button class="menu-toggle" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="search-bar">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Rechercher..." id="searchInput">
            </div>
            
            <div class="user-actions">
                <a href="admin.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i>
                    Retour
                </a>
                <button class="btn btn-primary" onclick="location.reload()">
                    <i class="fas fa-sync-alt"></i>
                    Actualiser
                </button>
            </div>
        </div>

        <!-- Audit Content -->
        <div class="content-section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-clipboard-list"></i>
                    Journal des Opérations
                </h2>
            </div>

            <!-- Statistiques -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-title">Total des Actions</div>
                    <div class="stat-value"><?= $stats['total'] ?></div>
                    <div class="stat-change">Toutes les opérations</div>
                </div>
                <div class="stat-card">
                    <div class="stat-title">Ajouts</div>
                    <div class="stat-value"><?= $stats['ajout'] ?></div>
                    <div class="stat-change">Créations de comptes</div>
                </div>
                <div class="stat-card">
                    <div class="stat-title">Modifications</div>
                    <div class="stat-value"><?= $stats['modification'] ?></div>
                    <div class="stat-change">Mises à jour de comptes</div>
                </div>
                <div class="stat-card">
                    <div class="stat-title">Suppressions</div>
                    <div class="stat-value"><?= $stats['suppression'] ?></div>
                    <div class="stat-change">Comptes supprimés</div>
                </div>
            </div>

          

            <!-- Tableau des logs -->
            <?php if (empty($auditData)): ?>
                <div class="no-data">
                    <i class="fas fa-inbox"></i>
                    <h3>Aucune donnée d'audit trouvée</h3>
                    <p>Les actions apparaîtront ici lorsqu'elles seront enregistrées</p>
                </div>
            <?php else: ?>
                <table class="data-table" id="auditTable">
                    <thead>
                        <tr>
                            <th onclick="sortTable(0)">Action <i class="fas fa-sort"></i></th>
                            <th onclick="sortTable(1)">Date <i class="fas fa-sort"></i></th>
                            <th onclick="sortTable(2)">N° Compte <i class="fas fa-sort"></i></th>
                            <th onclick="sortTable(3)">Client <i class="fas fa-sort"></i></th>
                            <th onclick="sortTable(4)">Ancien Solde <i class="fas fa-sort"></i></th>
                            <th onclick="sortTable(5)">Nouveau Solde <i class="fas fa-sort"></i></th>
                            <th onclick="sortTable(6)">Utilisateur <i class="fas fa-sort"></i></th>
                        </tr>
                    </thead>
                    <tbody id="auditTableBody">
                        <?php foreach ($auditData as $row): 
                            $old = number_format($row['ancien_solde'], 2, ',', ' ');
                            $new = number_format($row['nouveau_solde'], 2, ',', ' ');
                            $diff = floatval($row['nouveau_solde']) - floatval($row['ancien_solde']);
                            $diffClass = $diff >= 0 ? 'amount-positive' : 'amount-negative';
                            $icon = ['ajout'=>'plus-circle', 'modification'=>'edit', 'suppression'=>'trash-alt'][$row['action']] ?? 'question-circle';
                        ?>
                        <tr>
                            <td>
                                <span class="action-badge action-<?= $row['action'] ?>">
                                    <i class="fas fa-<?= $icon ?>"></i>
                                    <?= ucfirst($row['action']) ?>
                                </span>
                            </td>
                            <td><?= date('d/m/Y H:i', strtotime($row['date_action'])) ?></td>
                            <td><strong><?= htmlspecialchars($row['numero_compte']) ?></strong></td>
                            <td><?= htmlspecialchars($row['nom_client']) ?></td>
                            <td><?= $old ?> AR</td>
                            <td>
                                <?php if ($row['action'] !== 'suppression'): ?>
                                    <?= $new ?> AR
                                    <?php if ($row['action'] === 'modification'): ?>
                                        <br><small class="<?= $diffClass ?>">(<?= ($diff >= 0 ? '+' : '') . number_format($diff, 2, ',', ' ') ?> AR)</small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="amount-negative"><i class="fas fa-ban"></i> Supprimé</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($row['utilisateur']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Toggle sidebar
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            
            if (window.innerWidth > 768) {
                sidebar.classList.toggle('collapsed');
                mainContent.classList.toggle('expanded');
            } else {
                sidebar.classList.toggle('show');
            }
        }

        // Recherche en temps réel
        document.getElementById('searchInput').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#auditTableBody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });

        // Tri des colonnes
        function sortTable(columnIndex) {
            const table = document.getElementById('auditTable');
            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));
            const isAsc = table.getAttribute('data-sort-dir') === 'asc' && 
                          parseInt(table.getAttribute('data-sort-col')) === columnIndex;
            
            rows.sort((a, b) => {
                const aText = a.cells[columnIndex].textContent.trim();
                const bText = b.cells[columnIndex].textContent.trim();
                
                // Pour les colonnes numériques (solde)
                if (columnIndex === 4 || columnIndex === 5) {
                    const aNum = parseFloat(aText.replace(/[^\d,]/g, '').replace(',', '.'));
                    const bNum = parseFloat(bText.replace(/[^\d,]/g, '').replace(',', '.'));
                    return isAsc ? bNum - aNum : aNum - bNum;
                }
                // Pour les dates
                else if (columnIndex === 1) {
                    const aDate = new Date(a.cells[columnIndex].getAttribute('data-sort') || aText);
                    const bDate = new Date(b.cells[columnIndex].getAttribute('data-sort') || bText);
                    return isAsc ? bDate - aDate : aDate - bDate;
                }
                // Pour le texte
                else {
                    return isAsc ? bText.localeCompare(aText) : aText.localeCompare(bText);
                }
            });
            
            // Réinsérer les lignes triées
            rows.forEach(row => tbody.appendChild(row));
            
            // Mettre à jour les indicateurs de tri
            table.querySelectorAll('th i').forEach(icon => {
                icon.className = 'fas fa-sort';
            });
            
            const th = table.querySelector(`th:nth-child(${columnIndex + 1})`);
            th.querySelector('i').className = isAsc ? 'fas fa-sort-up' : 'fas fa-sort-down';
            
            table.setAttribute('data-sort-col', columnIndex);
            table.setAttribute('data-sort-dir', isAsc ? 'desc' : 'asc');
        }

        // Réinitialiser les filtres
        function resetFilters() {
            document.getElementById('action').value = '';
            document.getElementById('user').value = '';
            document.getElementById('date_start').value = '';
            document.getElementById('date_end').value = '';
            document.querySelector('.filter-form').submit();
        }

        // Ajouter les attributs de tri pour les dates
        document.addEventListener('DOMContentLoaded', function() {
            const dateCells = document.querySelectorAll('#auditTable tbody td:nth-child(2)');
            dateCells.forEach(cell => {
                const dateText = cell.textContent.trim();
                const dateParts = dateText.split(' ');
                if (dateParts.length === 2) {
                    const [date, time] = dateParts;
                    const [day, month, year] = date.split('/');
                    const [hours, minutes] = time.split(':');
                    const isoDate = `${year}-${month}-${day}T${hours}:${minutes}`;
                    cell.setAttribute('data-sort', isoDate);
                }
            });
        });
    </script>
</body>
</html>