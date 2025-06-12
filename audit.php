<?php
include 'config.php';
include 'header.php';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Audit des Opérations</title>
</head>
<body>
    <h1>Audit des Opérations</h1>
    <a href="index.php" class="button">Retour</a>
<br>
<br>
   <h2>Audit des actions</h2>
<table>
    <thead>
        <tr>
            <th>Type d'Action</th>
            <th>Date</th>
            <th>Numéro Compte</th>
            <th>Nom Client</th>
            <th>Ancien Solde</th>
            <th>Nouveau Solde</th>
            <th>Utilisateur</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $sql = "SELECT * FROM audit_compte WHERE utilisateur != 'admin'";
        $result = $conn->query($sql);

        if ($result === false) {
            die("Erreur SQL : " . $conn->error);
        }

        while ($row = $result->fetch_assoc()) {
            echo "<tr>
                    <td>{$row['type_action']}</td>
                    <td>{$row['date_mise_a_jour']}</td>
                    <td>{$row['num_compte']}</td>
                    <td>{$row['nom_client']}</td>
                    <td>{$row['solde_ancien']}</td>
                    <td>{$row['solde_nouv']}</td>
                    <td>{$row['utilisateur']}</td>
                  </tr>";
        }
        ?>
    </tbody>
</table>

<br>
    <h2>Statistiques des Opérations</h2>
    <br>
    <?php
    
    $counts = $conn->query("SELECT 
        SUM(type_action='ajout') AS insertions,
        SUM(type_action='modification') AS modifications,
        SUM(type_action='suppression') AS suppressions 
        FROM audit_compte")->fetch_assoc();

    echo "Insertions : " . $counts['insertions'] . "<br>";
    echo "Modifications : " . $counts['modifications'] . "<br>";
    echo "Suppressions : " . $counts['suppressions'] . "<br>";
    ?>
</body>
</html>
<?php include 'footer.php'; ?>
