<?php
session_start();
include 'config.php';

$message = "";

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

// Dans la partie traitement du formulaire
if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password'])) {
            // Après vérification des identifiants
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role']; // Doit être 1 ou 'Admin' pour les administrateurs
            
            // Redirection intelligente
            if ($user['role'] == 'Admin') {
                header("Location: admin.php");
            } else {
                header("Location: user.php");
            }
            exit();
        } else {
            $message = "Mot de passe incorrect";
        }
    } else {
        $message = "Nom d'utilisateur introuvable";
    }
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion | Système d'authentification</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4895ef;
            --error-color: #f72585;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --success-color: #4cc9f0;
            --border-radius: 10px;
            --box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .login-container {
            background: white;
            width: 100%;
            max-width: 450px;
            padding: 40px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            transform: translateY(0);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .login-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
        }

        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
        }

        h2 {
            color: var(--dark-color);
            text-align: center;
            margin-bottom: 30px;
            font-size: 28px;
            position: relative;
        }

        h2::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 50px;
            height: 3px;
            background: var(--accent-color);
            border-radius: 3px;
        }

        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: var(--dark-color);
            font-weight: 500;
        }

        input {
            width: 100%;
            padding: 12px 15px 12px 40px;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            font-size: 16px;
            transition: var(--transition);
        }

        input:focus {
            border-color: var(--accent-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
        }

        .input-icon {
            position: absolute;
            left: 15px;
            top: 38px;
            color: #777;
        }

        button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: var(--border-radius);
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            margin-top: 10px;
        }

        button:hover {
            background: linear-gradient(to right, var(--secondary-color), var(--primary-color));
            transform: translateY(-2px);
        }

        .error-message {
            color: var(--error-color);
            background-color: rgba(247, 37, 133, 0.1);
            padding: 12px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            text-align: center;
            border-left: 4px solid var(--error-color);
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }

        .forgot-password {
            text-align: center;
            margin-top: 20px;
        }

        .forgot-password a {
            color: var(--accent-color);
            text-decoration: none;
            font-size: 14px;
            transition: var(--transition);
        }

        .forgot-password a:hover {
            color: var(--secondary-color);
            text-decoration: underline;
        }

        .additional-options {
            margin-top: 30px;
            text-align: center;
            font-size: 14px;
            color: #666;
        }

        .social-login {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 20px;
        }

        .social-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
            transition: var(--transition);
            cursor: pointer;
        }

        .social-btn:hover {
            transform: translateY(-3px);
        }

        .facebook { background-color: #3b5998; }
        .google { background-color: #db4437; }
        .twitter { background-color: #1da1f2; }

        .register-link {
            margin-top: 20px;
            text-align: center;
        }

        .register-link a {
            color: var(--primary-color);
            font-weight: 600;
            text-decoration: none;
            transition: var(--transition);
        }

        .register-link a:hover {
            color: var(--secondary-color);
            text-decoration: underline;
        }

        /* Responsive design */
        @media (max-width: 480px) {
            .login-container {
                padding: 30px 20px;
            }
            
            h2 {
                font-size: 24px;
            }
        }

        /* Animation for the form */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .login-container form {
            animation: fadeIn 0.6s ease-out;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Connexion</h2>

        <?php if ($message): ?>
            <p class="error-message">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($message); ?>
            </p>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label for="username">Nom d'utilisateur</label>
                <i class="fas fa-user input-icon"></i>
                <input type="text" id="username" name="username" required placeholder="Entrez votre nom d'utilisateur">
            </div>

            <div class="form-group">
                <label for="password">Mot de passe</label>
                <i class="fas fa-lock input-icon"></i>
                <input type="password" id="password" name="password" required placeholder="Entrez votre mot de passe">
            </div>

            <button type="submit">
                <i class="fas fa-sign-in-alt"></i> Se connecter
            </button>
        </form>

        <div class="forgot-password">
            <a href="#"><i class="fas fa-key"></i> Mot de passe oublié ?</a>
        </div>

        <div class="additional-options">
            <p>Ou connectez-vous avec</p>
            <div class="social-login">
                <div class="social-btn facebook">
                    <i class="fab fa-facebook-f"></i>
                </div>
                <div class="social-btn google">
                    <i class="fab fa-google"></i>
                </div>
                <div class="social-btn twitter">
                    <i class="fab fa-twitter"></i>
                </div>
            </div>
        </div>

        <div class="register-link">
            <p>Pas encore de compte ? <a href="register.php">S'inscrire</a></p>
        </div>
    </div>

    <script>
        // Animation pour les boutons sociaux
        document.querySelectorAll('.social-btn').forEach(btn => {
            btn.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-3px) scale(1.1)';
            });
            
            btn.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });

        // Focus sur le premier champ au chargement
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('username').focus();
        });

        // Animation d'erreur si message existe
        <?php if ($message): ?>
            setTimeout(() => {
                document.querySelector('.error-message').style.opacity = '1';
            }, 100);
        <?php endif; ?>
    </script>
</body>
</html>