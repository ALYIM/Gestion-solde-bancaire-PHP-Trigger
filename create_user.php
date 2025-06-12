<?php
/*
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header("Location: login.php");
    exit();
}
*/
include 'config.php'; // Inclure la configuration de la base de données

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hacher le mot de passe
    $role_id = $_POST['role']; // 1 pour Admin, 2 pour Utilisateur

    $sql = "INSERT INTO users (username, password, role_id) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $username, $password, $role_id);

    if ($stmt->execute()) {
        echo "Utilisateur créé avec succès.";
    } else {
        echo "Erreur lors de la création de l'utilisateur : " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Créer un utilisateur</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Créer un utilisateur</h1>
        <form method="post">
            <label for="username">Nom d'utilisateur:</label>
            <input type="text" id="username" name="username" required>

            <label for="password">Mot de passe:</label>
            <input type="password" id="password" name="password" required>

            <label for="role">Rôle:</label>
            <select id="role" name="role" required>
                <option value="1">Admin</option>
                <option value="2">Utilisateur</option>
            </select>

            <button type="submit">Créer</button>
        </form>
    </div>
</body>
</html>
