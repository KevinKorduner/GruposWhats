<?php
require_once 'db.php';
session_start();

// Generar CSRF token
function generateCsrfToken() {
    $token = bin2hex(random_bytes(32));
    $_SESSION['csrf_token_reg'] = $token;
    return $token;
}
function verifyCsrfToken($token) {
    return (isset($_SESSION['csrf_token_reg']) && hash_equals($_SESSION['csrf_token_reg'], $token));
}

// Generar captcha solo si no existe
if (!isset($_SESSION['captcha_a']) || !isset($_SESSION['captcha_b'])) {
    $_SESSION['captcha_a'] = rand(1,9);
    $_SESSION['captcha_b'] = rand(1,9);
}

$errors = [];

// Mantener campos si hay errores
$username = $_POST['username'] ?? '';
$email = $_POST['email'] ?? '';
$clave = $_POST['clave'] ?? '';
$acepta_terminos = isset($_POST['acepta_terminos']) ? true : false;

$csrf_token = $_SESSION['csrf_token_reg'] ?? generateCsrfToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token_post = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrf_token_post)) {
        $errors[] = "Token CSRF inválido, recarga la página.";
    } else {
        $username = trim(filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING));
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $clave = $_POST['clave'] ?? '';
        $acepta_terminos = isset($_POST['acepta_terminos']);
        $captcha_response = $_POST['captcha'] ?? '';

        // Validaciones
        if ($username === '') {
            $errors[] = "El nombre de usuario es obligatorio.";
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "El campo 'email' debe ser un email válido.";
        }

        if (strlen($clave) < 6) {
            $errors[] = "La clave debe tener al menos 6 caracteres.";
        }

        if (!$acepta_terminos) {
            $errors[] = "Debes aceptar los términos de uso.";
        }

        // Verificar captcha
        $a = $_SESSION['captcha_a'];
        $b = $_SESSION['captcha_b'];
        if ((int)$captcha_response !== ($a + $b)) {
            $errors[] = "La respuesta del captcha es incorrecta.";
        }

        if (empty($errors)) {
            // Insertar usuario
            $password_hash = password_hash($clave, PASSWORD_BCRYPT);

            $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (:username, :email, :password_hash)");
            $result = $stmt->execute([
                ':username' => $username,
                ':email' => $email,
                ':password_hash' => $password_hash
            ]);

            if ($result) {
                // Obtener ID del usuario creado
                $user_id = $pdo->lastInsertId();
                $_SESSION['user_id'] = $user_id;
                // Redirigir a panel.php
                header("Location: panel.php");
                exit;
            } else {
                $errors[] = "Error al registrar el usuario en la base de datos.";
            }
        }

        // Si hay errores, no regeneramos el captcha ni reseteamos campos
        // Los campos quedan según lo introducido
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="description" content="Página de registro de usuarios">
    <meta name="keywords" content="registro, crear cuenta, whatsapp groups">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Cuenta</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="styles.css">
    <!-- FontAwesome para íconos (usado en el footer) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php">GruposWats</a>
        <?php include 'menu.php'; ?>
        <button class="navbar-toggler ms-auto" type="button" data-bs-toggle="collapse" 
          data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" 
          aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <a href="index.php" class="btn btn-light ms-auto">Volver al listado</a>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <h1 class="text-center mb-4">Registrar Nueva Cuenta</h1>

    <?php if(!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach($errors as $e) echo "<p>$e</p>"; ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="bg-white p-4 shadow-sm">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
        <div class="mb-3">
            <label for="username" class="form-label">Nombre de Usuario</label>
            <input type="text" class="form-control" name="username" id="username" required value="<?php echo htmlspecialchars($username); ?>">
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">Email (Principal)</label>
            <input type="email" class="form-control" name="email" id="email" required value="<?php echo htmlspecialchars($email); ?>">
        </div>
        <div class="mb-3">
            <label for="clave" class="form-label">Clave (mínimo 6 caracteres)</label>
            <input type="password" class="form-control" name="clave" id="clave" required value="<?php echo htmlspecialchars($clave); ?>">
        </div>
        <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" name="acepta_terminos" id="acepta_terminos" <?php if($acepta_terminos) echo 'checked'; ?>>
            <label class="form-check-label" for="acepta_terminos">Acepto los <a href="#">términos de uso</a></label>
        </div>
        <div class="mb-3">
            <?php 
            $a = $_SESSION['captcha_a'];
            $b = $_SESSION['captcha_b'];
            ?>
            <label class="form-label">Captcha: ¿Cuánto es <?php echo $a; ?> + <?php echo $b; ?>?</label>
            <input type="number" class="form-control" name="captcha" required>
        </div>
        <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
            <button type="submit" class="btn btn-primary">Registrar cuenta</button>
            <a href="recuperar.php" class="btn btn-secondary">Recuperar clave</a>
        </div>
    </form>
</div>

<?php include 'footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
