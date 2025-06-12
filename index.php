<?php
include 'config.php';
include 'header.php';

session_start();
// Vérifier si l'utilisateur est déjà connecté
if (isset($_SESSION['user_id'])) {
    // Rediriger en fonction du rôle
    if ($_SESSION['role'] == 'Admin') {
        header("Location: admin.php");
    } else {
        header("Location: user.php");
    }
    exit();
} else {
    // Rediriger vers la page de connexion si l'utilisateur n'est pas connecté
    header("Location: login.php");
    exit();
}


// Fonction pour valider le nom du client (lettres uniquement)
function validateName($name) {
    return preg_match("/^[a-zA-Z\s\-']+$/", $name); // Autorise les lettres, espaces, tirets et apostrophes
}

// Ajouter un compte
if (isset($_POST['ajouter'])) {
    $nom_client = $_POST['nom_client'];
    $solde = $_POST['solde'];

    // Validation du nom du client
    if (!validateName($nom_client)) {
        echo "<p style='color: red;'>Erreur : Le nom du client ne doit contenir que des lettres.</p>";
    } else {
        // Insertion dans la base de données
        $sql = "INSERT INTO compte (nom_client, solde) VALUES ('$nom_client', '$solde')";
        if ($conn->query($sql) === TRUE) {
            echo "<p style='color: green;'>Compte ajouté avec succès.</p>";
        } else {
            echo "<p style='color: red;'>Erreur lors de l'ajout du compte : " . $conn->error . "</p>";
        }
    }
}

// Modifier un compte
if (isset($_POST['modifier'])) {
    $num_compte = $_POST['num_compte'];
    $solde = $_POST['solde'];

    $sql = "UPDATE compte SET solde='$solde' WHERE num_compte='$num_compte'";
    $conn->query($sql);
}

// Supprimer un compte
if (isset($_POST['supprimer'])) {
    $num_compte = $_POST['num_compte'];

    $sql = "DELETE FROM compte WHERE num_compte='$num_compte'";
    $conn->query($sql);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Comptes Bancaires</title>
</head>
<body>
</body>
</html>
<?php include 'footer.php'; ?>
