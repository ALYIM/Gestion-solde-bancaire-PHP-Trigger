<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header("Location: login.php");
    exit();
}
include 'config.php'; // Inclure la configuration de la base de données
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Interface Admin</title>
    <link rel="stylesheet" href="style.css">
    
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Bienvenue, Admin</h1>
            <a href="logout.php" class="logout-button">Déconnexion</a>
            <br>
            <br>
            <a href="audit.php" class="button">Voir Audit</a>
        </div>
<br>
<br>
        <h2>Créer un utilisateur</h2>
        <br>
        <form method="post" action="create_user.php">
            <label for="username">Nom d'utilisateur:</label>
            <input type="text" id="username" name="username" required>
            <br>
            <br>

            <label for="password">Mot de passe:</label>
            <input type="password" id="password" name="password" required>
<br>
<br>
            <label for="role">Rôle:</label>
            <select id="role" name="role" required>
                <option value="1">Admin</option>
                <option value="2">Utilisateur</option>
            </select>

            <button type="submit">Créer</button>
        </form>
<br>
<br>

        <h2>Liste des employés et les admins </h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nom d'utilisateur</th>
                    <th>Rôle</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Récupérer la liste des utilisateurs avec leurs rôles
                $sql = "SELECT users.id, users.username, roles.name AS role 
                        FROM users 
                        JOIN roles ON users.role_id = roles.id";
                $result = $conn->query($sql);

                if ($result === false) {
                    die("Erreur SQL : " . $conn->error);
                }

                while ($row = $result->fetch_assoc()) {
                    echo "<tr>
                            <td>{$row['id']}</td>
                            <td>{$row['username']}</td>
                            <td>{$row['role']}</td>
                          </tr>";
                }
                ?>
            </tbody>
        </table>
<br>
<br>

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

                if ($result === false) {
                    die("Erreur SQL : " . $conn->error);
                }

                while ($row = $result->fetch_assoc()) {
                    echo "<tr>
                            <td>{$row['num_compte']}</td>
                            <td>{$row['nom_client']}</td>
                            <td>{$row['solde']}</td>
                          </tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</body>
</html>
