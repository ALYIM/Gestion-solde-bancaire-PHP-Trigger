<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Utilisateur') {
    header("Location: login.php");
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
    $sql = "INSERT INTO compte (nom_client, solde, user_mode) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sds", $nom_client, $solde, $_SESSION['username']);

    if ($stmt->execute()) {
        $num_compte = $conn->insert_id;
        //enregistrerAction('Ajout', $num_compte, $nom_client, 0, $solde, $_SESSION['username']);
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
       // enregistrerAction('Modification', $num_compte, $nom_client, $solde_ancien, $solde, $_SESSION['username']);
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
      //  enregistrerAction('Suppression', $num_compte, $nom_client, $solde_ancien, 0, $_SESSION['username']);
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
    <title>Interface Utilisateur</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: grid;
            grid-template-columns: 1fr 200px;
            min-height: 100vh;
            background-color: #f5f5f5;
        }

        .main-content {
            padding: 20px;
            overflow-y: auto;
            height: 100vh;
            box-sizing: border-box;
        }

        .user-sidebar {
            background: #fff;
            padding: 20px;
            border-left: 1px solid #ddd;
            display: flex;
            flex-direction: column;
            height: 100vh;
            box-sizing: border-box;
            position: sticky;
            top: 0;
        }

        .user-profile {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 20px;
        }

        .avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #4ca1af, #2c3e50);
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .username {
            font-weight: bold;
            color: #333;
            text-align: center;
            margin-bottom: 20px;
        }

        .logout-btn {
            display: block;
            width: 100%;
            padding: 10px;
            background-color: #dc3545;
            color: white;
            border: none;
            border-radius: 4px;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
            margin-top: auto;
        }

        .logout-btn:hover {
            background-color: #c82333;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        form {
            background: white;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #3d8b99;
        }

        .message {
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
        }

        button {
            padding: 8px 15px;
            background-color: #4ca1af;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        button:hover {
            background-color: #3d8b99;
        }

        input[type="text"], 
        input[type="number"] {
            width: 100%;
            padding: 8px;
            margin: 5px 0 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }

        label {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="main-content">
        <div class="header">
            <h1>Gestion des Comptes</h1>
        </div>

        <?php if(isset($message)): ?>
            <div class="message <?= $message['type'] ?>"><?= $message['text'] ?></div>
        <?php endif; ?>

        <h2>Ajouter un Compte</h2>
        <form method="post">
            <label for="nom_client">Nom Client:</label>
            <input type="text" id="nom_client" name="nom_client" required>

            <label for="solde">Solde:</label>
            <input type="number" step="0.01" id="solde" name="solde" required>

            <button type="submit" name="ajouter">Ajouter</button>
        </form>

        <h2>Modifier un Compte</h2>
        <form method="post">
            <label for="num_compte">Numéro Compte:</label>
            <input type="number" id="num_compte" name="num_compte" required>

            <label for="solde">Nouveau Solde:</label>
            <input type="number" step="0.01" id="solde" name="solde" required>

            <button type="submit" name="modifier">Modifier</button>
        </form>

        <h2>Supprimer un Compte</h2>
        <form method="post">
            <label for="num_compte">Numéro Compte:</label>
            <input type="number" id="num_compte" name="num_compte" required>

            <button type="submit" name="supprimer">Supprimer</button>
        </form>

        <h2>Liste des Comptes</h2>
        <table>
            <thead>
                <tr>
                    <th>Numéro Compte</th>
                    <th>Nom Client</th>
                    <th>Solde</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql = "SELECT * FROM compte";
                $result = $conn->query($sql);

                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        echo "<tr>
                                <td>{$row['num_compte']}</td>
                                <td>{$row['nom_client']}</td>
                                <td>{$row['solde']}</td>
                              </tr>";
                    }
                } else {
                    echo "<tr><td colspan='3'>Aucun compte trouvé</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <div class="user-sidebar">
        <div class="user-profile">
            <div class="avatar"><?= $initial ?></div>
            <div class="username"><?= htmlspecialchars($username) ?></div>
            <div class="user-role">Utilisateur</div> <br>
            <a href="logout.php" class="logout-btn">Déconnexion</a>
        </div>
        
    </div>
</body>
</html>