<?php
session_start();
require_once 'config.php';

// Vérification de l'accès admin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', [1, '1', 'Admin'])) {
    $_SESSION['error_message'] = "Accès refusé : droits insuffisants";
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role_id = (int)$_POST['role'];

    // Validation des données
    if (empty($username) || empty($password)) {
        $_SESSION['error_message'] = "Tous les champs sont obligatoires";
        header("Location: admin.php");
        exit();
    } elseif ($password !== $confirm_password) {
        $_SESSION['error_message'] = "Les mots de passe ne correspondent pas";
        header("Location: admin.php");
        exit();
    } elseif (strlen($password) < 8) {
        $_SESSION['error_message'] = "Le mot de passe doit contenir au moins 8 caractères";
        header("Location: admin.php");
        exit();
    } else {
        // Vérifier si l'utilisateur existe déjà
        $check_sql = "SELECT id FROM users WHERE username = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $username);
        $check_stmt->execute();
        $check_stmt->store_result();

        if ($check_stmt->num_rows > 0) {
            $_SESSION['error_message'] = "Ce nom d'utilisateur est déjà pris";
            header("Location: admin.php");
            exit();
        } else {
            // Hacher le mot de passe
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insertion dans la base de données
            $insert_sql = "INSERT INTO users (username, password, role_id) VALUES (?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("ssi", $username, $hashed_password, $role_id);

            if ($insert_stmt->execute()) {
                // Déterminer le type d'utilisateur créé
                $user_type = ($role_id == 1) ? "admin" : "utilisateur";
                $_SESSION['success_message'] = "Création " . $user_type . " avec succès";
                
                // Journalisation de l'action
                enregistrerAction("Création utilisateur", $username, $_SESSION['username']);
                
                // Rediriger vers admin.php
                header("Location: admin.php");
                exit();
            } else {
                $_SESSION['error_message'] = "Erreur lors de la création : " . $conn->error;
                header("Location: admin.php");
                exit();
            }
        }
    }
} else {
    // Si pas de POST, rediriger vers admin
    header("Location: admin.php");
    exit();
}

// Fonction pour journaliser les actions
function enregistrerAction($action, $cible, $auteur) {
    global $conn;
    $sql = "INSERT INTO audit_log (action, target, author, created_at) VALUES (?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $action, $cible, $auteur);
    $stmt->execute();
}
?>