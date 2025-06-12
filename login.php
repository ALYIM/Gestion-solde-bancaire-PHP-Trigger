<?php
session_start();
include 'config.php'; // Inclure la configuration de la base de données

$message = ""; // Variable pour stocker les messages d'erreur

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Requête SQL pour récupérer l'utilisateur et son rôle
    $sql = "SELECT users.id, users.username, users.password, roles.name AS role 
            FROM users 
            JOIN roles ON users.role_id = roles.id 
            WHERE username = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    // Vérifier si l'utilisateur existe
    if ($user = $result->fetch_assoc()) {
        // Vérifier le mot de passe
        if (password_verify($password, $user['password'])) {
            // Stocker les informations de l'utilisateur dans la session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // Rediriger en fonction du rôle
            header("Location: " . ($user['role'] == 'Admin' ? "admin.php" : "user.php"));
            exit();
        } else {
            $message = "Nom d'utilisateur ou mot de passe incorrect.";
        }
    } else {
        $message = "Nom d'utilisateur ou mot de passe incorrect.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion</title>
    <link rel="stylesheet" href="style_login.css">
    
</head>
<body>
    <div class="login-container">
        <h2>Connexion</h2>

        <?php if ($message): ?>
            <p class="error-message"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>

        <form method="post">
            <label for="username">Nom d'utilisateur:</label>
            <input type="text" id="username" name="username" required>
<br>
            <label for="password">Mot de passe:</label>
            <input type="password" id="password" name="password" required>

            <input type="submit" value="Se connecter">
        </form>
        <br>
        <div class="forgot-password">
            <a href="#">Mot de passe oublié?</a>
        </div>
    </div>
</body>
</html>    

