<?php
session_start();
require_once 'config.php'; 


// Vérification de connexion
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Vérification cohérente du rôle admin
$allowed_admin_roles = [1, '1', 'Admin']; // Tous les formats possibles

if (!in_array($_SESSION['role'] ?? '', $allowed_admin_roles)) {
    error_log("Accès refusé: rôle invalide. Valeur du rôle: " . ($_SESSION['role'] ?? 'non défini') . 
              ", ID utilisateur: " . ($_SESSION['user_id'] ?? 'non défini'));
    header('Location: login.php');
    exit();
}

// Récupérer le nom d'utilisateur de la session
$username = $_SESSION['username'] ?? 'Admin';
$user_initial = strtoupper(substr($username, 0, 1));


// Récupérer les statistiques
try {
    // Compter les utilisateurs
    $stmt = $conn->query("SELECT COUNT(*) as total FROM users");
    $totalUsers = $stmt->fetch_assoc()['total'];
    
    // Compter les comptes
     $stmt = $conn->query("SELECT COUNT(*) as total FROM compte"); 
    $totalAccounts = $stmt->fetch_assoc()['total'];
    
    // Calculer le solde total
    $stmt = $conn->query("SELECT SUM(solde) as total FROM compte");
    $totalBalance = $stmt->fetch_assoc()['total'] ?: 0;
    
    // Récupérer la liste des utilisateurs
    $stmt = $conn->query("SELECT id, username, role FROM users ORDER BY id");
    $users = [];
    while ($row = $stmt->fetch_assoc()) {
        $users[] = $row;
    }
    // Récupérer la liste des comptes
    // Récupérer la liste des comptes
    $stmt = $conn->query("SELECT num_compte, nom_client, solde FROM compte ORDER BY num_compte");
    $accounts = [];
    while ($row = $stmt->fetch_assoc()) {
        $accounts[] = $row;
    }
    
    // Récupérer les dernières transactions pour le dashboard
    $stmt = $conn->query("SELECT * FROM transactions ORDER BY created_at DESC LIMIT 10");
    $recentTransactions = [];
    while ($row = $stmt->fetch_assoc()) {
        $recentTransactions[] = $row;
    }
    
} catch (Exception $e) {
    // Gestion des erreurs
    $totalUsers = 0;
    $totalAccounts = 0;
    $totalBalance = 0;
    $users = [];
    $accounts = [];
    $recentTransactions = [];
    error_log("Erreur base de données: " . $e->getMessage());
}

$sql_users = "SELECT * FROM users ORDER BY id DESC";
$result_users = $conn->query($sql_users);

$users = [];
if ($result_users->num_rows > 0) {
    while($row = $result_users->fetch_assoc()) {
        $users[] = $row;
    }
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interface Admin - Dashboard</title>
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
            display: none;
        }

        .content-section.active {
            display: block !important;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
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

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-group label {
            font-weight: 600;
            color: #555;
        }

        .form-group input, .form-group select {
            padding: 0.75rem 1rem;
            border: 2px solid rgba(102, 126, 234, 0.2);
            border-radius: 10px;
            font-size: 1rem;
            background: rgba(255, 255, 255, 0.9);
            transition: all 0.3s ease;
        }

        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
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

        .status-admin {
            background: rgba(102, 126, 234, 0.1);
            color: #667eea;
        }

        .status-user {
            background: rgba(67, 233, 123, 0.1);
            color: #43e97b;
        }

        .status-active {
            background: rgba(67, 233, 123, 0.1);
            color: #43e97b;
        }

        .status-inactive {
            background: rgba(231, 76, 60, 0.1);
            color: #e74c3c;
        }

        .loading {
            display: none;
            text-align: center;
            padding: 2rem;
            color: #667eea;
        }

        .spinner {
            display: inline-block;
            width: 40px;
            height: 40px;
            border: 4px solid rgba(102, 126, 234, 0.3);
            border-radius: 50%;
            border-top-color: #667eea;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 10px;
            border: 1px solid transparent;
        }

        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }

        .alert-error {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }

        .alert-info {
            color: #0c5460;
            background-color: #d1ecf1;
            border-color: #bee5eb;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
        }

        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem 2rem;
            border-radius: 10px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            z-index: 9999;
            transition: all 0.3s ease;
            animation: slideIn 0.3s ease;
        }

        .notification.success {
            background: #43e97b;
            color: white;
        }

        .notification.error {
            background: #e74c3c;
            color: white;
        }

        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        .quick-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-item {
            background: rgba(255, 255, 255, 0.9);
            padding: 1.5rem;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
        }

        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 0.9rem;
            color: #666;
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
            
            .dashboard-cards {
                grid-template-columns: 1fr;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }

            .top-bar {
                flex-direction: column;
                gap: 1rem;
            }

            .search-bar {
                max-width: 100%;
                margin: 0;
            }

            .user-actions {
                width: 100%;
                justify-content: center;
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
                <h3><?= htmlspecialchars($username) ?></h3>
                <p>Administrateur</p>
            </div>
        </div>
        
        <nav class="nav-menu">
             <a href="#" class="nav-item" data-section="dashboard">
                <i class="fas fa-tachometer-alt"></i>
                Dashboard
            </a>
      
            <a href="#" class="nav-item" data-section="users">
                <i class="fas fa-users"></i>
                Utilisateurs
            </a>
            <a href="#" class="nav-item" data-section="accounts">
                <i class="fas fa-university"></i>
                Comptes
            </a>
            <a href="#" class="nav-item" data-section="create-user">
                <i class="fas fa-user-plus"></i>
                Créer Utilisateur
            </a>
            
            <a href="audit.php" class="nav-item">
                <i class="fas fa-clipboard-list"></i>
                Audit
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
                <a href="audit.php" class="btn btn-secondary">
                    <i class="fas fa-clipboard-list"></i>
                    Audit
                </a>
                <a href="logout.php" class="btn btn-primary">
                    <i class="fas fa-sign-out-alt"></i>
                    Déconnexion
                </a>
            </div>
        </div>

        <!-- Dashboard Section -->
        <div id="dashboard" class="content-section active">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </h2>
            </div>
            
            <div class="dashboard-cards">
                <div class="card">
                    <div class="card-header">
                        <div class="card-icon users">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3 class="card-title">Utilisateurs</h3>
                    </div>
                    <div class="card-value"><?php echo $totalUsers; ?></div>
                    <div class="card-subtitle">Total des utilisateurs</div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <div class="card-icon accounts">
                            <i class="fas fa-university"></i>
                        </div>
                        <h3 class="card-title">Comptes</h3>
                    </div>
                    <div class="card-value"><?php echo $totalAccounts; ?></div>
                    <div class="card-subtitle">Total des comptes</div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <div class="card-icon transactions">
                            <i class="fas fa-exchange-alt"></i>
                        </div>
                        <h3 class="card-title">Solde Total</h3>
                    </div>
                    <div class="card-value"><?php echo number_format($totalBalance, 2, ',', ' ') . ' €'; ?></div>
                    <div class="card-subtitle">Somme de tous les soldes</div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <div class="card-icon audit">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h3 class="card-title">Sécurité</h3>
                    </div>
                    <div class="card-value">100%</div>
                    <div class="card-subtitle">Système sécurisé</div>
                </div>
            </div>

            <!-- Récentes transactions -->
            <div class="content-section" style="display: block;">
                <div class="section-header">
                   
                </div>
                
                <table class="data-table">
                    <thead>
                        
                    </thead>
                    <tbody>
                        <?php foreach ($recentTransactions as $transaction): ?>
                        <tr>
                            <td>
                                <i class="fas <?php echo $transaction['type'] == 'credit' ? 'fa-arrow-up' : 'fa-arrow-down'; ?>" 
                                   style="color: <?php echo $transaction['type'] == 'credit' ? '#43e97b' : '#e74c3c'; ?>"></i>
                                <?php echo ucfirst($transaction['type']); ?>
                            </td>
                            <td><?php echo number_format($transaction['amount'], 2, ',', ' ') . ' €'; ?></td>
                            <td><?php echo htmlspecialchars($transaction['account_number']); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($transaction['created_at'])); ?></td>
                            <td><span class="status-badge status-active">Complété</span></td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($recentTransactions)): ?>
                        
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

<!-- Create User Section -->
<div id="create-user" class="content-section">
    <div class="section-header">
        <h2 class="section-title">
            <i class="fas fa-user-plus"></i>
            Créer un Utilisateur
        </h2>
    </div>
    
    <?php
    // Afficher les messages de succès ou d'erreur
    if (isset($_SESSION['success_message'])) {
        echo '<div class="alert alert-success" style="background-color: #d4edda; color: #155724; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px; margin: 15px 0; font-size: 16px; font-weight: bold;">';
        echo '✅ ' . htmlspecialchars($_SESSION['success_message']);
        echo '</div>';
        unset($_SESSION['success_message']);
    }
    if (isset($_SESSION['error_message'])) {
        echo '<div class="alert alert-error" style="background-color: #f8d7da; color: #721c24; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px; margin: 15px 0; font-size: 16px; font-weight: bold;">';
        echo '❌ ' . htmlspecialchars($_SESSION['error_message']);
        echo '</div>';
        unset($_SESSION['error_message']);
    }
    ?>
    
    <form method="post" action="create_user.php" id="createUserForm">
        <div class="form-grid">
            <div class="form-group">
                <label for="username">Nom d'utilisateur</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirmer le mot de passe</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            
            <div class="form-group">
                <label for="role">Rôle</label>
                <select id="role" name="role" required>
                    <option value="">Sélectionner un rôle</option>
                    <option value="1">Admin</option>
                    <option value="2">Utilisateur</option>
                </select>
            </div>
        </div>
        
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-plus"></i>
            Créer l'utilisateur
        </button>
    </form>
</div>

       

        <!-- Users Section -->
<div id="users" class="content-section">
    <div class="section-header">
        <h2 class="section-title">
            <i class="fas fa-users"></i>
            Liste des Utilisateurs
        </h2>
        <button class="btn btn-secondary" onclick="refreshUsers()">
            <i class="fas fa-sync-alt"></i> Actualiser
        </button>
    </div>
    
    <div class="users-stats">
        <p><strong><?php echo count($users); ?></strong> utilisateur(s) dans la base de données</p>
    </div>
    
    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nom d'utilisateur</th>
                <th>Rôle</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($users)): ?>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><strong>#<?php echo htmlspecialchars($user['id']); ?></strong></td>
                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                    
                    <td>
                        <?php 
                        // Conversion role_id en texte
                        $role_text = '';
                        $role_class = '';
                        $role_icon = '';
                        
                        switch($user['role_id']) {
                            case 1:
                                $role_text = 'Administrateur';
                                $role_class = 'status-admin';
                                $role_icon = 'fa-crown';
                                break;
                            case 2:
                                $role_text = 'Utilisateur';
                                $role_class = 'status-user';
                                $role_icon = 'fa-user';
                                break;
                            default:
                                $role_text = 'Non défini';
                                $role_class = 'status-inactive';
                                $role_icon = 'fa-question';
                        }
                        ?>
                        <span class="status-badge <?php echo $role_class; ?>">
                            <i class="fas <?php echo $role_icon; ?>"></i>
                            <?php echo $role_text; ?>
                        </span>
                    </td>
                    <td>
                        <span class="status-badge status-active">
                            <i class="fas fa-check-circle"></i>
                            Actif
                        </span>
                    </td>
                    <td class="action-buttons">
                        <button class="btn btn-secondary btn-sm" onclick="editUser(<?php echo $user['id']; ?>)" title="Modifier">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-warning btn-sm" onclick="changeUserRole(<?php echo $user['id']; ?>, <?php echo $user['role_id']; ?>)" title="Changer le rôle">
                            <i class="fas fa-exchange-alt"></i>
                        </button>
                        <button class="btn btn-danger btn-sm" onclick="confirmDeleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')" title="Supprimer">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
            <tr>
                <td colspan="6" style="text-align: center; color: #666; font-style: italic; padding: 40px;">
                    <i class="fas fa-users-slash" style="font-size: 2em; margin-bottom: 10px; display: block;"></i>
                    <strong>Aucun utilisateur trouvé</strong>
                    <br>
                    <small>Vérifiez la connexion à la base de données</small>
                </td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>


       <!-- Accounts Section -->
<div id="accounts" class="content-section">
    <div class="section-header">
        <h2 class="section-title">
            <i class="fas fa-university"></i>
            Liste des Comptes
        </h2>
        <button class="btn btn-secondary" onclick="refreshAccounts()">
            <i class="fas fa-sync-alt"></i> Actualiser
        </button>
    </div>
    
    <table class="data-table">
        <thead>
            <tr>
                <th><i class="fas fa-hashtag"></i> Numéro Compte</th>
                <th><i class="fas fa-user"></i> Nom et Prénom Client</th>
                <th><i class="fas fa-euro-sign"></i> Solde</th>
                <th><i class="fas fa-cogs"></i> Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $sql = "SELECT * FROM compte ORDER BY num_compte DESC";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $solde_formatted = number_format($row['solde'], 2, ',', ' ');
                    echo "<tr>
                            <td><strong>#{$row['num_compte']}</strong></td>
                            <td>{$row['nom_client']}</td>
                            <td><strong>{$solde_formatted} AR</strong></td>
                            <td class='action-buttons'>
                                <button class='btn btn-secondary btn-sm' onclick='editAccount({$row['num_compte']}, {$row['solde']})'>
                                    <i class='fas fa-edit'></i> Modifier
                                </button>
                                <button class='btn btn-danger btn-sm' onclick='confirmDeleteAccount({$row['num_compte']}, \"{$row['nom_client']}\")'>
                                    <i class='fas fa-trash'></i> Supprimer
                                </button>
                            </td>
                          </tr>";
                }
            } else {
                echo "<tr><td colspan='4' style='text-align: center; font-style: italic; color: #999;'>
                        <i class='fas fa-inbox'></i> Aucun compte trouvé
                      </td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>

        <!-- Transactions Section -->
        <div id="transactions" class="content-section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-exchange-alt"></i>
                    Transactions
                </h2>
                <div>
                    <button class="btn btn-primary" onclick="showNewTransactionForm()">
                        <i class="fas fa-plus"></i> Nouvelle Transaction
                    </button>
                    <button class="btn btn-secondary" onclick="refreshTransactions()">
                        <i class="fas fa-sync-alt"></i> Actualiser
                    </button>
                </div>
            </div>
            
            <!-- Formulaire de nouvelle transaction (caché par défaut) -->
            <div id="newTransactionForm" style="display: none; margin-bottom: 2rem;">
                <form id="transactionForm" method="post" action="process_transaction.php">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="transaction_type">Type</label>
                            <select id="transaction_type" name="transaction_type" required>
                                <option value="">Sélectionner un type</option>
                                <option value="credit">Crédit</option>
                                <option value="debit">Débit</option>
                                <option value="transfer">Transfert</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="account_number">Compte</label>
                            <select id="account_number" name="account_number" required>
                                <option value="">Sélectionner un compte</option>
                                <?php foreach ($accounts as $account): ?>
                                <option value="<?php echo $account['account_number']; ?>">
                                    <?php echo htmlspecialchars($account['account_number'] . ' - ' . $account['client_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="amount">Montant</label>
                            <input type="number" step="0.01" min="0" id="amount" name="amount" required>
                        </div>
                        
                        <div class="form-group" id="destinationAccountGroup" style="display: none;">
                            <label for="destination_account">Compte destinataire</label>
                            <select id="destination_account" name="destination_account">
                                <option value="">Sélectionner un compte</option>
                                <?php foreach ($accounts as $account): ?>
                                <option value="<?php echo $account['account_number']; ?>">
                                    <?php echo htmlspecialchars($account['account_number'] . ' - ' . $account['client_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="3" style="width: 100%; padding: 0.75rem 1rem; border: 2px solid rgba(102, 126, 234, 0.2); border-radius: 10px;"></textarea>
                    </div>
                    
                    <div style="display: flex; gap: 1rem;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-check"></i> Valider
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="hideNewTransactionForm()">
                            <i class="fas fa-times"></i> Annuler
                        </button>
                    </div>
                </form>
            </div>
            
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Type</th>
                        <th>Montant</th>
                        <th>Compte</th>
                        <th>Date</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentTransactions as $transaction): ?>
                    <tr>
                        <td><?php echo $transaction['id']; ?></td>
                        <td>
                            <i class="fas <?php echo $transaction['type'] == 'credit' ? 'fa-arrow-up' : 'fa-arrow-down'; ?>" 
                               style="color: <?php echo $transaction['type'] == 'credit' ? '#43e97b' : '#e74c3c'; ?>"></i>
                            <?php echo ucfirst($transaction['type']); ?>
                        </td>
                        <td><?php echo number_format($transaction['amount'], 2, ',', ' ') . ' €'; ?></td>
                        <td><?php echo htmlspecialchars($transaction['account_number']); ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($transaction['created_at'])); ?></td>
                        <td><span class="status-badge status-active">Complété</span></td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <?php if (empty($recentTransactions)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; color: #666;">
                            Aucune transaction trouvée
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Variables globales
        let sidebarCollapsed = false;
        
        // Toggle sidebar
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            
            sidebarCollapsed = !sidebarCollapsed;
            
            if (window.innerWidth > 768) {
                if (sidebarCollapsed) {
                    sidebar.classList.add('collapsed');
                    mainContent.classList.add('expanded');
                } else {
                    sidebar.classList.remove('collapsed');
                    mainContent.classList.remove('expanded');
                }
            } else {
                sidebar.classList.toggle('show');
            }
        }

        // Show section
        function showSection(sectionId) {
            // Masquer toutes les sections
            const sections = document.querySelectorAll('.content-section');
            sections.forEach(section => {
                section.classList.remove('active');
            });
            
            // Supprimer la classe active de tous les éléments de navigation
            const navItems = document.querySelectorAll('.nav-item');
            navItems.forEach(item => {
                item.classList.remove('active');
            });
            
            // Afficher la section sélectionnée
            const targetSection = document.getElementById(sectionId);
            if (targetSection) {
                targetSection.classList.add('active');
                
                // Marquer l'élément de navigation comme actif
                document.querySelector(`.nav-item[data-section="${sectionId}"]`).classList.add('active');
            }
        }

        // Confirmation de suppression utilisateur
        function confirmDeleteUser(userId) {
            if (confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')) {
                window.location.href = `delete_user.php?id=${userId}`;
            }
        }

        // Confirmation de suppression compte
        function confirmDeleteAccount(accountNumber) {
            if (confirm('Êtes-vous sûr de vouloir supprimer ce compte ?')) {
                window.location.href = `delete_account.php?account_number=${accountNumber}`;
            }
        }

        // Édition utilisateur
        function editUser(userId) {
            // Implémenter la logique d'édition ici
            showNotification('Fonctionnalité d\'édition à implémenter', 'info');
        }

        // Édition compte
        function editAccount(accountNumber) {
            // Implémenter la logique d'édition ici
            showNotification('Fonctionnalité d\'édition à implémenter', 'info');
        }

        // Actualiser les utilisateurs
        function refreshUsers() {
            window.location.reload();
        }

        // Actualiser les comptes
        function refreshAccounts() {
            window.location.reload();
        }

        // Actualiser les transactions
        function refreshTransactions() {
            window.location.reload();
        }

        // Afficher le formulaire de nouvelle transaction
        function showNewTransactionForm() {
            document.getElementById('newTransactionForm').style.display = 'block';
        }

        // Cacher le formulaire de nouvelle transaction
        function hideNewTransactionForm() {
            document.getElementById('newTransactionForm').style.display = 'none';
            document.getElementById('transactionForm').reset();
        }

        // Gérer l'affichage du champ compte destinataire
        document.getElementById('transaction_type').addEventListener('change', function() {
            const destinationGroup = document.getElementById('destinationAccountGroup');
            if (this.value === 'transfer') {
                destinationGroup.style.display = 'block';
                document.getElementById('destination_account').required = true;
            } else {
                destinationGroup.style.display = 'none';
                document.getElementById('destination_account').required = false;
            }
        });

        // Validation du formulaire de création d'utilisateur
        document.getElementById('createUserForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                showNotification('Les mots de passe ne correspondent pas', 'error');
            }
        });

        // Gestionnaire d'événements pour la navigation
        document.addEventListener('DOMContentLoaded', function() {
            // Attacher les événements de navigation
            const navItems = document.querySelectorAll('.nav-item[data-section]');
            navItems.forEach(item => {
                item.addEventListener('click', function(e) {
                    e.preventDefault();
                    const sectionId = this.getAttribute('data-section');
                    showSection(sectionId);
                });
            });
            
            // Gérer le design responsive
            if (window.innerWidth <= 768) {
                document.getElementById('sidebar').classList.remove('show');
            }
        });

        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const activeSection = document.querySelector('.content-section.active');
            
            if (activeSection) {
                const table = activeSection.querySelector('.data-table tbody');
                if (table) {
                    const rows = table.querySelectorAll('tr');
                    rows.forEach(row => {
                        const text = row.textContent.toLowerCase();
                        if (text.includes(searchTerm)) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                }
            }
        });

        // Notification system
        function showNotification(message, type = 'success') {
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.opacity = '0';
                setTimeout(() => {
                    notification.remove();
                }, 300);
            }, 3000);
        }

        // Gérer le redimensionnement de la fenêtre
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                document.getElementById('sidebar').classList.remove('show');
            }
        });
    </script>
</body>
</html>