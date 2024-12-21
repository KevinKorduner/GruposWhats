

---

## 1. Resumen General

Este proyecto es un **directorio de grupos de WhatsApp** donde los usuarios pueden:
1. **Buscar** y **filtrar** grupos de WhatsApp por nombre o país.
2. **Publicar** (agregar) nuevos grupos, indicando el nombre del grupo, país y enlace de invitación.
3. **Registrarse** en la plataforma para tener acceso a un panel de usuario.
4. **Iniciar sesión** (o mantenerse autenticado) y **cerrar sesión** (logout).
5. Acceder a un **Panel de Usuario**, donde se puede ver información personal y contar con un submenú para:
   - Volver al panel.
   - Agregar grupo rápidamente.
   - Cerrar sesión.

Además, cuenta con un **sistema de captcha** simple (suma de dos números) para evitar registros automatizados, **CSRF tokens** para mayor seguridad en los formularios y un **footer** y **navbar** consistentes con estilo Bootstrap.

---

## 2. Estructura de Archivos

La estructura típica del proyecto (carpeta raíz) es la siguiente:

```
project/
├─ db.php
├─ footer.php
├─ index.php
├─ login.php          (opcional si deseas una página de login específica)
├─ logout.php
├─ menu.php
├─ panel.php
├─ panel_menu.php
├─ registrar.php
├─ recuperar.php      (opcional, para recuperar contraseña)
├─ styles.css
└─ (otros archivos de Bootstrap, FontAwesome, etc. si los incluyes localmente)
```

A continuación, se describen las **páginas y archivos principales**.

---

## 3. Descripción de Cada Archivo

### 3.1. db.php

- **Función**: Contiene la **conexión a la base de datos** usando PDO.  
- **Personalización**: Aquí se configuran los datos de conexión (`$db_host`, `$db_user`, `$db_pass`, `$db_name`).

```php
// db.php
try {
    $pdo = new PDO("mysql:host=HOST;dbname=DB_NAME;charset=utf8mb4", "USER", "PASS", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
```

---

### 3.2. menu.php

- **Función**: Es el **menú de navegación principal** que aparece junto al logo ("GruposWats").  
- **Contenido**: Incluye enlaces (ejemplo: "WhatsApp", "Discord", "Foro", "Soporte", "FAQ" desplegable).  
- **Uso**: Es incluido en la parte superior de las páginas (`index.php`, `panel.php`, etc.) dentro de la barra de navegación de Bootstrap.

```php
// menu.php (ejemplo simplificado)
<ul class="navbar-nav ms-3">
    <li class="nav-item"><a class="nav-link active" href="#">Whatsapp</a></li>
    <li class="nav-item"><a class="nav-link" href="#">Discord</a></li>
    <li class="nav-item"><a class="nav-link" href="#">Foro</a></li>
    <li class="nav-item"><a class="nav-link" href="#">Soporte</a></li>
    <!-- ... -->
</ul>
```

---

### 3.3. footer.php

- **Función**: Es el **footer** común que aparece al final de todas las páginas.  
- **Contenido**:
  - Breve descripción del sitio,  
  - Palabras clave (SEO),  
  - Enlaces de ayuda (Foro, Soporte),  
  - Íconos de redes sociales (FontAwesome),  
  - Autoría y año actual.

```php
// footer.php (ejemplo simplificado)
<footer class="footer bg-dark text-light py-4 mt-5">
  <div class="container">
    <!-- Secciones, descripción, enlaces, redes sociales, etc. -->
    <div class="text-center mt-4">
        Creado por Kevin Korduner © 2024
    </div>
  </div>
</footer>
```

---

### 3.4. styles.css

- **Función**: Contiene los estilos personalizados del proyecto. Se usa junto a **Bootstrap** para estilizar la página.
- **Contenido**: Reglas de hover, menú activo, footer, modal, efectos de sombra, etc.

```css
/* styles.css (extracto) */
.nav-link:hover, .dropdown-item:hover {
    background: #ffe5b4; /* Naranja claro */
    color: #000 !important;
}
/* ... */
```

---

### 3.5. index.php

- **Función**: Página **principal** del sitio. Muestra:
  1. Un campo de **búsqueda** para filtrar grupos de WhatsApp (con AJAX).
  2. Listado de grupos con **paginación** cuando no se está realizando búsqueda dinámica.
  3. Un **enlace** o **botón** para **Publicar tu grupo** (`?action=publish`).
  4. Botones para **Registrarse** y **Ingresar**.
  5. Modal emergente para mostrar detalles de cada grupo (nombre, país, fecha, enlace, botón “Copiar URL”).
- **Lógica**:
  - Maneja la carga normal (paginada) y la **búsqueda** AJAX (`action=search`).
  - Maneja la lógica de publicación (`action=publish`), aunque a veces está derivada al formulario dentro del propio `index.php` o en otra página.
  - Incluye `menu.php` y `footer.php` para mantener la consistencia del diseño.

---

### 3.6. registrar.php

- **Función**: Página para **registrar** una nueva cuenta de usuario.
- **Campos**: 
  - Username,  
  - Email,  
  - Clave (password),  
  - Checkbox de términos de uso,  
  - **Captcha** de suma simple (p.e. “¿Cuánto es 4 + 3?”).
- **Lógica**:
  - Usa un **CSRF token** para seguridad.  
  - Si todo es válido, inserta los datos en la tabla `users` (con **password_hash**).  
  - Inicia sesión (`$_SESSION['user_id'] = ...`) y redirige al `panel.php` tras el registro exitoso.  
  - Si hay errores, los muestra y mantiene los campos llenos para que el usuario corrija.

---

### 3.7. panel.php

- **Función**: Página a la que se redirige un usuario tras iniciar sesión o registrarse.  
- **Contenido**:
  - Muestra **datos** del usuario (username, email, fecha de creación).  
  - Incluye un **submenú** (cargado desde `panel_menu.php`) con las opciones:
    1. **Panel** (recarga o vuelve a panel.php),  
    2. **Agregar grupo** (lleva a `index.php?action=publish`),  
    3. **Cerrar sesión** (logout).
- **Seguridad**: Requiere `$_SESSION['user_id']` para mostrar el panel. Si no existe, redirige a `registrar.php`.

---

### 3.8. panel_menu.php

- **Función**: Submenú específico del panel de usuario.
- **Enlaces**:
  - “Panel” -> `panel.php`
  - “Agregar grupo” -> `index.php?action=publish`
  - “Cerrar sesión” -> `logout.php`

Se incluye en `panel.php` justo debajo del título “Panel de Usuario”.

---

### 3.9. logout.php

- **Función**: Archivos para **cerrar sesión** del usuario.
- **Lógica**:
  - Destruye la sesión (`session_unset()`, `session_destroy()`)  
  - Redirige al `index.php` o a la página principal.

```php
// logout.php
session_start();
session_unset();
session_destroy();
header("Location: index.php");
exit;
```

---

### 3.10. recuperar.php (Opcional)

- **Función**: Página para **recuperar** la contraseña (opcional).  
- **Estado**: Puede ser solo un placeholder con un formulario básico, o implementar envío de correo, etc.

---

## 4. Base de Datos

El sistema utiliza **MySQL** (o MariaDB) a través de **PDO**. La configuración se define en `db.php`. Las **tablas principales** son:

1. **groups** (para almacenar los grupos de WhatsApp):  
   - `id INT AUTO_INCREMENT PRIMARY KEY`  
   - `name VARCHAR(255)`  
   - `country_code VARCHAR(5)`  
   - `whatsapp_link VARCHAR(500)`  
   - `ip VARCHAR(45)`  
   - `created_at DATETIME DEFAULT CURRENT_TIMESTAMP`

2. **users** (para almacenar cuentas de usuario):  
   - `id INT AUTO_INCREMENT PRIMARY KEY`  
   - `username VARCHAR(50)`  
   - `email VARCHAR(100)`  
   - `password_hash VARCHAR(255)`  
   - `created_at DATETIME DEFAULT CURRENT_TIMESTAMP`

La estructura y queries de creación se pueden incluir en un script `init.sql` o directamente en la documentación del repositorio.

---

## 5. Tecnologías Utilizadas

- **PHP 7.x/8.x** con **PDO** para la capa de datos (consultas preparadas).
- **MySQL/MariaDB** como base de datos.
- **Bootstrap 5** (CDN) para estilos y componentes responsivos.
- **FontAwesome** (CDN) para íconos en redes sociales y estéticos.
- **HTML5**, **CSS3** (archivo `styles.css`) para la interfaz y el diseño personalizado.
- **JavaScript** (nativo o con Bootstrap Bundle) para el modal, AJAX de búsqueda dinámica y copiar enlace al portapapeles.

---

## 6. Funcionalidades Clave

1. **Búsqueda Dinámica (AJAX)**: 
   - En `index.php`, al tipear en el campo de búsqueda, se manda un request `?action=search&search=termino` que retorna fragmentos HTML con los grupos coincidentes.
   - Filtra en tiempo real, sin necesidad de recargar la página.

2. **Modal Detalle de Grupo**: 
   - Al hacer clic en un grupo, se abre un modal con nombre, país, fecha y enlace clicable.
   - Botón de "Copiar URL del grupo" usando `navigator.clipboard`.

3. **Publicar Grupo** (`?action=publish`):
   - Formulario para añadir nombre del grupo, país (código), y link de invitación.
   - Guarda la IP del usuario y valida el formato del link (`https://chat.whatsapp.com/...`).

4. **Registro de Usuario** (`registrar.php`):
   - Captcha de suma simple.  
   - Validación de datos y uso de `password_hash`.
   - Inicia la sesión en caso de registro exitoso y redirige a `panel.php`.

5. **Panel de Usuario** (`panel.php`):
   - Muestra datos del usuario.  
   - Submenú con opciones “Panel”, “Agregar grupo”, “Cerrar sesión”.

6. **Cerrar Sesión** (`logout.php`):
   - Destruye la sesión y redirige al índice.

---

## 7. Cómo Ejecutar el Proyecto

1. **Clonar** este repositorio:  
   ```bash
   git clone https://github.com/tu-usuario/sistema-grupos-whatsapp.git
   ```
2. **Configurar la base de datos**:  
   - Crear la base de datos en MySQL o MariaDB.  
   - Ejecutar los scripts de creación de tabla (ver `init.sql` o las queries en la documentación).  
   - Ajustar las credenciales en `db.php`.
3. **Subir los archivos** del proyecto a tu servidor local o hosting compatible con PHP.
4. **Navegar** a la URL principal (por ej., `http://localhost/sistema-grupos-whatsapp/index.php`).
5. **Probar**:  
   - Registrar un usuario en `registrar.php`.  
   - Publicar grupos en `index.php?action=publish`.  
   - Probar la búsqueda dinámica.  
   - Iniciar sesión y entrar al panel (`panel.php`).

---

## 8. Posibles Mejoras

- **Autenticación avanzada**: añadir un sistema de login específico, en lugar de registrar e iniciar sesión automáticamente.  
- **Recuperar contraseña**: implementar envío de correo, token de recuperación.  
- **Panel de administración**: para moderar o eliminar grupos.  
- **Verificación de enlace**: comprobar si un enlace de WhatsApp sigue activo.  
- **Internacionalización**: permitir más idiomas, etc.

---



---
