

<?php
// backup Kevin 21-12/2024

require_once 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: registrar.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT username, email, created_at FROM users WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $user_id]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: registrar.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="description" content="Panel de usuario">
    <meta name="keywords" content="panel, usuario, whatsapp groups">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Usuario</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="styles.css">
    <!-- FontAwesome para íconos de redes sociales -->
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
    <h1 class="text-center mb-4">Panel de Usuario</h1>
    <?php include 'panel_menu.php'; ?>

    <div class="bg-white p-4 shadow-sm">
        <p><strong>Nombre de Usuario:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
        <p><strong>Fecha de creación de la cuenta:</strong> <?php echo htmlspecialchars($user['created_at']); ?></p>
    </div>
</div>

<?php include 'footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
