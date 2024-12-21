<?php
require_once 'db.php';

session_start();
function generateCsrfToken() {
    $token = bin2hex(random_bytes(32));
    $_SESSION['csrf_token'] = $token;
    return $token;
}
function verifyCsrfToken($token) {
    return (isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token));
}

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$errors = [];
$success = false;

// Publicar grupo
if ($action === 'publish' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (!verifyCsrfToken($csrf_token)) {
        $errors[] = "Token CSRF inválido, recarga la página.";
    } else {
        $name = trim(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING));
        $country_code = strtoupper(trim(filter_input(INPUT_POST, 'country_code', FILTER_SANITIZE_STRING)));
        $whatsapp_link = filter_input(INPUT_POST, 'whatsapp_link', FILTER_SANITIZE_URL);
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        if ($name === '') {
            $errors[] = "El nombre del grupo es obligatorio.";
        }
        if ($country_code === '' || strlen($country_code) > 5) {
            $errors[] = "El código de país es obligatorio y debe tener como máximo 5 caracteres.";
        }
        if (!preg_match('/^https:\/\/chat\.whatsapp\.com\//', $whatsapp_link)) {
            $errors[] = "Debes introducir un enlace válido de invitación de WhatsApp (https://chat.whatsapp.com/...).";
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare("INSERT INTO `groups` (name, country_code, whatsapp_link, ip) VALUES (:name, :cc, :link, :ip)");
            $inserted = $stmt->execute([
                ':name' => $name,
                ':cc' => $country_code,
                ':link' => $whatsapp_link,
                ':ip' => $ip
            ]);
            if ($inserted) {
                $success = true;
            } else {
                $errors[] = "Error al guardar el grupo en la base de datos.";
            }
        }
    }
}

if ($action === 'publish' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $csrf_token = generateCsrfToken();
} elseif ($action === 'publish' && !$success) {
    $csrf_token = generateCsrfToken();
}

// Lógica para la búsqueda AJAX (action=search)
if ($action === 'search') {
    $searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
    $sql = "SELECT * FROM `groups` WHERE 1=1";
    $params = [];
    if ($searchTerm !== '') {
        $sql .= " AND (name LIKE :search OR country_code LIKE :search)";
        $params[':search'] = "%$searchTerm%";
    }
    $sql .= " ORDER BY id DESC";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v, PDO::PARAM_STR);
    }
    $stmt->execute();
    $groups = $stmt->fetchAll();

    if (empty($groups)) {
        echo '<div class="alert alert-info">No se encontraron grupos con esos criterios.</div>';
    } else {
        echo '<div class="list-group mb-4">';
        foreach($groups as $g) {
            echo '<div class="list-group-item d-flex justify-content-between align-items-center group-detail-trigger"
                 data-name="'.htmlspecialchars($g['name']).'" 
                 data-country="'.htmlspecialchars($g['country_code']).'" 
                 data-link="'.htmlspecialchars($g['whatsapp_link']).'"
                 data-date="'.htmlspecialchars($g['created_at']).'">
                <div>
                    <h5 class="mb-1">'.htmlspecialchars($g['name']).'</h5>
                    <p class="mb-0"><strong>País: '.htmlspecialchars($g['country_code']).'</strong></p>
                    <p class="mb-0"><small class="text-muted">Publicado: '.htmlspecialchars($g['created_at']).'</small></p>
                </div>
                <span class="badge bg-primary">ID: '.$g['id'].'</span>
            </div>';
        }
        echo '</div>';
    }
    exit; // Salir ya que es una respuesta AJAX
}

// Lógica para el listado normal
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) && (int)$_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$limit = 10; 
$offset = ($page - 1) * $limit;

$sqlCount = "SELECT COUNT(*) as total FROM `groups` WHERE 1=1";
$params = [];
if ($search !== '') {
    $sqlCount .= " AND (name LIKE :search OR country_code LIKE :search)";
    $params[':search'] = "%$search%";
}
$stmtCount = $pdo->prepare($sqlCount);
$stmtCount->execute($params);
$totalRows = (int)$stmtCount->fetchColumn();
$totalPages = ceil($totalRows / $limit);

$sqlData = "SELECT * FROM `groups` WHERE 1=1";
if ($search !== '') {
    $sqlData .= " AND (name LIKE :search OR country_code LIKE :search)";
}
$sqlData .= " ORDER BY id DESC LIMIT :limit OFFSET :offset";
$stmtData = $pdo->prepare($sqlData);

if ($search !== '') {
    $stmtData->bindValue(':search', "%$search%", PDO::PARAM_STR);
}
$stmtData->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
$stmtData->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
$stmtData->execute();
$groups = $stmtData->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="description" content="Directorio de grupos de WhatsApp para encontrar y unirse a comunidades de diversos intereses.">
    <meta name="keywords" content="grupos whatsapp, enlaces whatsapp, directorio whatsapp, unirse a grupos, comunidades, chat whatsapp, grupos online">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grupos de WhatsApp</title>
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
            <?php if($action !== 'publish'): ?>
            <!-- Campo de búsqueda sin botón, filtrará con JS -->
            <input class="form-control ms-auto" type="search" placeholder="Buscar grupos..." id="searchInput" style="max-width: 250px;">
            
            <a href="?action=publish" class="btn btn-warning">Publica tu grupo</a>
            <a href="registrar.php" class="btn btn-success">Registrarse</a>
            <a href="login.php" class="btn btn-info ms-2">Ingresar</a>
            <?php else: ?>
            <a href="index.php" class="btn btn-light ms-auto">Ver listado</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="container mt-4">

    <?php if($action === 'publish'): ?>
        <h1 class="text-center mb-4">Publica tu Grupo de WhatsApp</h1>

        <?php if(!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach($errors as $e) echo "<p>$e</p>"; ?>
            </div>
        <?php endif; ?>

        <?php if($success): ?>
            <div class="alert alert-success">
                ¡Tu grupo se ha publicado con éxito! <a href="index.php">Volver al listado</a>
            </div>
        <?php else: ?>
        <form action="?action=publish" method="POST" class="bg-white p-4 shadow-sm">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token ?? ''); ?>">
            <div class="mb-3">
                <label for="name" class="form-label">Nombre del Grupo</label>
                <input type="text" class="form-control" name="name" id="name" placeholder="Ej: Amigos de Viaje" required>
            </div>
            <div class="mb-3">
                <label for="country_code" class="form-label">Código de País (ej: MX, AR, ES)</label>
                <input type="text" class="form-control" name="country_code" id="country_code" maxlength="5" required>
            </div>
            <div class="mb-3">
                <label for="whatsapp_link" class="form-label">Enlace de Invitación de WhatsApp</label>
                <input type="url" class="form-control" name="whatsapp_link" id="whatsapp_link" placeholder="https://chat.whatsapp.com/XXXXXX" required>
            </div>
            <button type="submit" class="btn btn-primary">Publicar</button>
        </form>
        <?php endif; ?>

    <?php else: ?>
        <h1 class="text-center mb-4">Listado de Grupos de WhatsApp</h1>
        <p class="text-secondary">Total de grupos encontrados: <?php echo $totalRows; ?></p>
        <div class="result-container">
        <?php if (empty($groups)): ?>
            <div class="alert alert-info">No se encontraron grupos con esos criterios.</div>
        <?php else: ?>
            <div class="list-group mb-4">
            <?php foreach($groups as $g): ?>
                <div class="list-group-item d-flex justify-content-between align-items-center group-detail-trigger"
                     data-name="<?php echo htmlspecialchars($g['name']); ?>" 
                     data-country="<?php echo htmlspecialchars($g['country_code']); ?>" 
                     data-link="<?php echo htmlspecialchars($g['whatsapp_link']); ?>"
                     data-date="<?php echo htmlspecialchars($g['created_at']); ?>">
                    <div>
                        <h5 class="mb-1"><?php echo htmlspecialchars($g['name']); ?></h5>
                        <p class="mb-0"><strong>País: <?php echo htmlspecialchars($g['country_code']); ?></strong></p>
                        <p class="mb-0"><small class="text-muted">Publicado: <?php echo htmlspecialchars($g['created_at']); ?></small></p>
                    </div>
                    <span class="badge bg-primary">ID: <?php echo $g['id']; ?></span>
                </div>
            <?php endforeach; ?>
            </div>
            
            <?php if ($totalPages > 1): ?>
            <nav>
                <ul class="pagination justify-content-center">
                    <?php for($i=1; $i<=$totalPages; $i++):
                        $active = ($i === $page) ? 'active' : '';
                        $url = 'index.php?search=' . urlencode($search) . '&page=' . $i;
                    ?>
                        <li class="page-item <?php echo $active; ?>">
                            <a class="page-link" href="<?php echo $url; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
            <?php endif; ?>
        <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>

<!-- Modal para detalles del grupo -->
<div class="modal fade" id="groupModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><span id="modalGroupName"></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <p><strong>País:</strong> <span id="modalGroupCountry"></span></p>
        <p><strong>Fecha de Publicación:</strong> <span id="modalGroupDate"></span></p>
        <p><strong>Enlace del Grupo:</strong><br><a href="#" id="modalGroupLink" target="_blank" rel="noopener noreferrer"></a></p>
      </div>
      <div class="modal-footer">
        <button type="button" id="copyLinkBtn" class="btn btn-secondary">Copiar URL del grupo</button>
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Cerrar ventana</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Abrir modal con detalles
document.addEventListener('click', (e) => {
    if(e.target.closest('.group-detail-trigger')) {
        const el = e.target.closest('.group-detail-trigger');
        const name = el.getAttribute('data-name');
        const country = el.getAttribute('data-country');
        const date = el.getAttribute('data-date');
        const link = el.getAttribute('data-link');

        document.getElementById('modalGroupName').textContent = name;
        document.getElementById('modalGroupCountry').textContent = country;
        document.getElementById('modalGroupDate').textContent = date;
        const linkEl = document.getElementById('modalGroupLink');
        linkEl.textContent = link;
        linkEl.href = link;

        const myModal = new bootstrap.Modal(document.getElementById('groupModal'));
        myModal.show();
    }
});

// Copiar enlace
document.getElementById('copyLinkBtn').addEventListener('click', () => {
    const link = document.getElementById('modalGroupLink').textContent;
    navigator.clipboard.writeText(link).then(() => {
        alert('Enlace copiado al portapapeles');
    }, () => {
        alert('No se pudo copiar el enlace');
    });
});

// Filtrar al tipear
const searchInput = document.getElementById('searchInput');
const resultContainer = document.querySelector('.result-container');

if (searchInput) {
    searchInput.addEventListener('input', () => {
        const term = searchInput.value.trim();
        fetch(`index.php?action=search&search=${encodeURIComponent(term)}`)
            .then(response => response.text())
            .then(html => {
                resultContainer.innerHTML = html;
            });
    });
}
</script>
</body>
</html>
