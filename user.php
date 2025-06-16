<?php
session_start();

// Vérification de base pour l'accès utilisateur
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Rediriger les admins vers admin.php
$admin_roles = [1, '1', 'Admin'];
if (in_array($_SESSION['role'] ?? '', $admin_roles)) {
    header('Location: admin.php');
    exit();
}

include 'config.php';

function enregistrerAction($type_action, $num_compte, $nom_client, $solde_ancien, $solde_nouv, $utilisateur) {
    global $conn;
    $sql = "INSERT INTO audit_compte (type_action, date_mise_a_jour, num_compte, nom_client, solde_ancien, solde_nouv, utilisateur) 
            VALUES (?, NOW(), ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sisdds", $type_action, $num_compte, $nom_client, $solde_ancien, $solde_nouv, $utilisateur);
    $stmt->execute();
}

// Traitement du formulaire d'ajout
if (isset($_POST['ajouter'])) {
    $nom_client = $_POST['nom_client'];
    $solde = $_POST['solde'];
    $sql = "INSERT INTO compte (nom_client, solde) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sd", $nom_client, $solde);

    if ($stmt->execute()) {
        $num_compte = $conn->insert_id;
        $message = ['type' => 'success', 'text' => 'Compte ajouté avec succès'];
    } else {
        $message = ['type' => 'error', 'text' => 'Erreur lors de l\'ajout du compte : ' . $conn->error];
    }
}

// Traitement du formulaire de modification
if (isset($_POST['modifier'])) {
    $num_compte = $_POST['num_compte'];
    $solde = $_POST['solde'];

    $sql = "SELECT solde, nom_client FROM compte WHERE num_compte = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $num_compte);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $solde_ancien = $row['solde'];
    $nom_client = $row['nom_client'];

    $sql = "UPDATE compte SET solde = ? WHERE num_compte = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("di", $solde, $num_compte);

    if ($stmt->execute()) {
        $message = ['type' => 'success', 'text' => 'Compte modifié avec succès'];
    } else {
        $message = ['type' => 'error', 'text' => 'Erreur lors de la modification : ' . $conn->error];
    }
}

// Traitement du formulaire de suppression
if (isset($_POST['supprimer'])) {
    $num_compte = $_POST['num_compte'];

    $sql = "SELECT solde, nom_client FROM compte WHERE num_compte = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $num_compte);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $solde_ancien = $row['solde'];
    $nom_client = $row['nom_client'];

    $sql = "DELETE FROM compte WHERE num_compte = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $num_compte);

    if ($stmt->execute()) {
        $message = ['type' => 'success', 'text' => 'Compte supprimé avec succès'];
    } else {
        $message = ['type' => 'error', 'text' => 'Erreur lors de la suppression : ' . $conn->error];
    }
}

$username = $_SESSION['username'];
$initial = strtoupper(substr($username, 0, 1));
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interface Utilisateur</title>
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
            overflow-x: hidden;
        }

        .container {
            display: grid;
            grid-template-columns: 1fr 280px;
            min-height: 100vh;
            gap: 0;
        }

        .main-content {
            padding: 2rem;
            overflow-y: auto;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
        }

        .header {
            text-align: center;
            margin-bottom: 3rem;
            animation: slideDown 0.8s ease-out;
        }

        .header h1 {
            font-size: 3rem;
            color: white;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
            margin-bottom: 0.5rem;
        }

        .header .subtitle {
            color: rgba(255, 255, 255, 0.8);
            font-size: 1.1rem;
        }

        /* Animations */
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        /* Formulaire d'ajout */
        .form-container {
            display: flex;
            justify-content: center;
            margin-bottom: 3rem;
            animation: fadeInUp 1s ease-out 0.3s both;
        }

        .add-form {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 2.5rem;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            max-width: 450px;
            width: 100%;
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }

        .add-form:hover {
            transform: translateY(-5px);
            box-shadow: 0 25px 50px rgba(0,0,0,0.15);
        }

        .add-form h2 {
            text-align: center;
            color: #333;
            margin-bottom: 2rem;
            font-size: 1.8rem;
            position: relative;
        }

        .add-form h2::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 50px;
            height: 3px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 2px;
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #555;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .form-group input {
            width: 100%;
            padding: 1rem;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            transform: translateY(-2px);
        }

        .btn-primary {
            width: 100%;
            padding: 1rem 2rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .btn-primary:hover::before {
            left: 100%;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }

        /* Liste des comptes */
        .accounts-section {
            animation: fadeInUp 1s ease-out 0.6s both;
        }

        .section-title {
            color: white;
            font-size: 2rem;
            margin-bottom: 2rem;
            text-align: center;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .table-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 1.5rem 1rem;
            text-align: left;
            font-weight: 600;
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            padding: 1.5rem 1rem;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }

        tr:hover td {
            background: rgba(102, 126, 234, 0.05);
            transform: scale(1.01);
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .btn-action {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-edit {
            background: linear-gradient(135deg, #ffeaa7, #fdcb6e);
            color: #2d3436;
        }

        .btn-edit:hover {
            background: linear-gradient(135deg, #fdcb6e, #e17055);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(253, 203, 110, 0.4);
        }

        .btn-delete {
            background: linear-gradient(135deg, #fd79a8, #e84393);
            color: white;
        }

        .btn-delete:hover {
            background: linear-gradient(135deg, #e84393, #d63031);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(232, 67, 147, 0.4);
        }

        /* Sidebar */
        .user-sidebar {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border-left: 1px solid rgba(255, 255, 255, 0.2);
            padding: 2rem;
            display: flex;
            flex-direction: column;
            animation: slideInRight 1s ease-out 0.9s both;
        }

        .user-profile {
            text-align: center;
            margin-bottom: 2rem;
        }

        .avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 2rem;
            font-weight: bold;
            margin: 0 auto 1rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
            animation: pulse 2s infinite;
        }

        .avatar:hover {
            transform: scale(1.1);
            box-shadow: 0 15px 40px rgba(0,0,0,0.3);
        }

        .username {
            font-weight: 600;
            color: white;
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
        }

        .user-role {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.9rem;
            margin-bottom: 2rem;
        }

        .logout-btn {
            background: linear-gradient(135deg, #fd79a8, #e84393);
            color: white;
            padding: 1rem;
            border: none;
            border-radius: 10px;
            text-decoration: none;
            text-align: center;
            font-weight: 600;
            margin-top: auto;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .logout-btn:hover {
            background: linear-gradient(135deg, #e84393, #d63031);
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(232, 67, 147, 0.3);
        }

        /* Messages */
        .message {
            padding: 1rem 1.5rem;
            margin: 1rem 0;
            border-radius: 10px;
            font-weight: 500;
            animation: fadeInUp 0.5s ease-out;
            position: relative;
            overflow: hidden;
        }

        .message::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
        }

        .success {
            background: rgba(46, 204, 113, 0.1);
            color: #27ae60;
            border: 1px solid rgba(46, 204, 113, 0.2);
        }

        .success::before {
            background: #27ae60;
        }

        .error {
            background: rgba(231, 76, 60, 0.1);
            color: #e74c3c;
            border: 1px solid rgba(231, 76, 60, 0.2);
        }

        .error::before {
            background: #e74c3c;
        }

        /* Modals */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            backdrop-filter: blur(5px);
            animation: fadeIn 0.3s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background: white;
            margin: 10% auto;
            padding: 2rem;
            border-radius: 20px;
            width: 90%;
            max-width: 400px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            position: relative;
            animation: modalSlideIn 0.3s ease-out;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-50px) scale(0.8);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .close {
            position: absolute;
            right: 1rem;
            top: 1rem;
            font-size: 1.5rem;
            cursor: pointer;
            color: #999;
            transition: color 0.3s ease;
        }

        .close:hover {
            color: #333;
        }

        .modal h3 {
            margin-bottom: 1.5rem;
            color: #333;
            text-align: center;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container {
                grid-template-columns: 1fr;
                grid-template-rows: 1fr auto;
            }
            
            .user-sidebar {
                order: 2;
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
                padding: 1rem;
            }
            
            .user-profile {
                display: flex;
                align-items: center;
                gap: 1rem;
                margin-bottom: 0;
            }
            
            .avatar {
                width: 50px;
                height: 50px;
                font-size: 1.2rem;
                margin: 0;
            }
        }

        /* Effets de particules */
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
        }

        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: rgba(255, 255, 255, 0.5);
            border-radius: 50%;
            animation: float 6s infinite linear;
        }

        @keyframes float {
            0% {
                transform: translateY(100vh) rotate(0deg);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translateY(-100px) rotate(360deg);
                opacity: 0;
            }
        }
    </style>
</head>
<body>
    <div class="particles" id="particles"></div>
    
    <div class="container">
        <div class="main-content">
            <div class="header">
                <h1><i class="fas fa-university"></i> Gestion des Comptes</h1>
                
            </div>

            <?php if(isset($message)): ?>
                <div class="message <?= $message['type'] ?>">
                    <i class="fas fa-<?= $message['type'] == 'success' ? 'check-circle' : 'exclamation-triangle' ?>"></i>
                    <?= $message['text'] ?>
                </div>
            <?php endif; ?>

            <div class="form-container">
                <div class="add-form">
                    <h2><i class="fas fa-plus-circle"></i> Ajouter un Compte</h2>
                    <form method="post">
                        <div class="form-group">
                            <label for="nom_client"><i class="fas fa-user"></i> Nom(s) et Prénom(s) Client :</label>
                            <input type="text" id="nom_client" name="nom_client" required>
                        </div>

                        <div class="form-group">
                            <label for="solde"><i class="fas fa-euro-sign"></i> Solde:</label>
                            <input type="number" step="0.01" id="solde" name="solde" required>
                        </div>

                        <button type="submit" name="ajouter" class="btn-primary">
                            <i class="fas fa-plus"></i> Ajouter le Compte
                        </button>
                    </form>
                </div>
            </div>

            <div class="accounts-section">
                <h2 class="section-title"><i class="fas fa-list"></i> Liste des Comptes</h2>
                <div class="table-container">
                    <table>
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
                                            <td>
                                                <div class='action-buttons'>
                                                    <button class='btn-action btn-edit' onclick='openModifyModal({$row['num_compte']}, {$row['solde']})'>
                                                        <i class='fas fa-edit'></i> Modifier
                                                    </button>
                                                    <button class='btn-action btn-delete' onclick='openDeleteModal({$row['num_compte']}, \"{$row['nom_client']}\")'>
                                                        <i class='fas fa-trash'></i> Supprimer
                                                    </button>
                                                </div>
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
            </div>
        </div>

        <div class="user-sidebar">
            <div class="user-profile">
                <div class="avatar"><?= $initial ?></div>
                <div>
                    <div class="username"><i class="fas fa-user-circle"></i> <?= htmlspecialchars($username) ?></div>
                    <div class="user-role"><i class="fas fa-id-badge"></i> Utilisateur</div>
                </div>
            </div>
            <a href="logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Déconnexion
            </a>
        </div>
    </div>

    <!-- Modal pour modification -->
    <div id="modifyModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('modifyModal')">&times;</span>
            <h3><i class="fas fa-edit"></i> Modifier le Compte</h3>
            <form method="post">
                <input type="hidden" id="modify_num_compte" name="num_compte">
                <div class="form-group">
                    <label for="modify_solde"><i class="fas fa-euro-sign"></i> Nouveau Solde:</label>
                    <input type="number" step="0.01" id="modify_solde" name="solde" required>
                </div>
                <button type="submit" name="modifier" class="btn-primary">
                    <i class="fas fa-save"></i> Modifier
                </button>
            </form>
        </div>
    </div>

    <!-- Modal pour suppression -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('deleteModal')">&times;</span>
            <h3><i class="fas fa-exclamation-triangle"></i> Supprimer le Compte</h3>
            <p id="deleteMessage" style="text-align: center; margin-bottom: 2rem;"></p>
            <form method="post">
                <input type="hidden" id="delete_num_compte" name="num_compte">
                <button type="submit" name="supprimer" class="btn-primary" style="background: linear-gradient(135deg, #fd79a8, #e84393); margin-bottom: 1rem;">
                    <i class="fas fa-trash"></i> Confirmer la suppression
                </button>
                <button type="button" onclick="closeModal('deleteModal')" class="btn-primary" style="background: linear-gradient(135deg, #74b9ff, #0984e3);">
                    <i class="fas fa-times"></i> Annuler
                </button>
            </form>
        </div>
    </div>

    <script>
        // Création des particules flottantes
        function createParticles() {
            const particlesContainer = document.getElementById('particles');
            const particleCount = 50;

            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 6 + 's';
                particle.style.animationDuration = (Math.random() * 3 + 3) + 's';
                particlesContainer.appendChild(particle);
            }
        }

        // Fonctions pour les modals
        function openModifyModal(numCompte, solde) {
            document.getElementById('modify_num_compte').value = numCompte;
            document.getElementById('modify_solde').value = solde;
            document.getElementById('modifyModal').style.display = 'block';
        }

        function openDeleteModal(numCompte, nomClient) {
            document.getElementById('delete_num_compte').value = numCompte;
            document.getElementById('deleteMessage').innerHTML = 
                `<i class="fas fa-question-circle" style="color: #e74c3c; font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
                 Êtes-vous sûr de vouloir supprimer le compte <strong>n°${numCompte}</strong> de <strong>${nomClient}</strong> ?`;
            document.getElementById('deleteModal').style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Fermer les modals en cliquant à l'extérieur
        window.onclick = function(event) {
            const modifyModal = document.getElementById('modifyModal');
            const deleteModal = document.getElementById('deleteModal');
            if (event.target == modifyModal) {
                modifyModal.style.display = 'none';
            }
            if (event.target == deleteModal) {
                deleteModal.style.display = 'none';
            }
        }

        // Animation au chargement
        document.addEventListener('DOMContentLoaded', function() {
            createParticles();
            
            // Animation des éléments du tableau
            const tableRows = document.querySelectorAll('tbody tr');
            tableRows.forEach((row, index) => {
                row.style.animationDelay = (index * 0.1) + 's';
                row.style.animation = 'fadeInUp 0.6s ease-out both';
            });
        });

        // Effet de survol sur les boutons
        document.querySelectorAll('.btn-primary, .btn-action').forEach(button => {
            button.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px) scale(1.05)';
            });
            
            button.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });
    </script>
</body>
</html>