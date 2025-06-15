<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "banque";

// Crée une connexion MySQLi et la retourne
function getDBConnection() {
    global $host, $user, $pass, $dbname;
    
    static $conn = null;
    
    if ($conn === null) {
        $conn = new mysqli($host, $user, $pass, $dbname);
        
        if ($conn->connect_error) {
            die("Connexion échouée: " . $conn->connect_error);
        }
    }
    
    return $conn;
}

// Initialise la connexion automatiquement
$conn = getDBConnection();
?>