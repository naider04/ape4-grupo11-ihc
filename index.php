<?php
// index.php
require_once 'db_config.php';
session_start();

$msg = "";

// Lógica de Login
if (isset($_POST['login'])) {
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE usuario = ? AND password = ?");
    $stmt->execute([$_POST['user'], $_POST['pass']]);
    $user = $stmt->fetch();
    if ($user) {
        $_SESSION['usuario'] = $user['usuario'];
    } else {
        $msg = "<p style='color:red;'>Acceso denegado. Verifique sus credenciales.</p>";
    }
}

// Cerrar Sesión
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

// Lógica de Registro de Cita
if (isset($_POST['agendar'])) {
    $sql = "INSERT INTO citas (paciente_nombre, especialidad, fecha, hora) VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$_POST['paciente'], $_POST['especialidad'], $_POST['fecha'], $_POST['hora']]);
    $msg = "<p style='color:green;'>Cita registrada correctamente en SQLite.</p>";
}

// Lógica de Eliminación de Cita
if (isset($_POST['eliminar']) && isset($_SESSION['usuario'])) {
    $stmt = $pdo->prepare("DELETE FROM citas WHERE id = ?");
    $stmt->execute([(int) $_POST['eliminar']]);
    if ($stmt->rowCount() > 0) {
        $msg = "<p style='color:#b8860b;'>La cita fue eliminada correctamente.</p>";
    } else {
        $msg = "<p style='color:red;'>No se encontró la cita que intenta eliminar.</p>";
    }
}

// Carga de la cita a editar (formulario precargado)
$editando = null;
if (isset($_GET['editar'])) {
    $stmt = $pdo->prepare("SELECT * FROM citas WHERE id = ?");
    $stmt->execute([(int) $_GET['editar']]);
    $editando = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    if ($editando === null) {
        $msg = "<p style='color:red;'>La cita que intenta editar ya no existe.</p>";
    }
}

// Lógica de Edición de Cita
if (isset($_POST['actualizar']) && isset($_SESSION['usuario'])) {
    $stmt = $pdo->prepare("UPDATE citas SET paciente_nombre = ?, especialidad = ?, fecha = ?, hora = ? WHERE id = ?");
    $stmt->execute([
        $_POST['paciente'],
        $_POST['especialidad'],
        $_POST['fecha'],
        $_POST['hora'],
        (int) $_POST['cita_id'],
    ]);
    if ($stmt->rowCount() > 0) {
        $msg = "<p style='color:green;'>La cita fue actualizada correctamente.</p>";
    } else {
        $msg = "<p style='color:#b8860b;'>No se detectaron cambios en la cita.</p>";
    }
    $editando = null; // vuelve al modo de registro
}

// Especialidades disponibles (se conserva una no listada al editar)
$especialidades = ['Medicina General', 'Pediatría', 'Odontología'];
if ($editando && !in_array($editando['especialidad'], $especialidades, true)) {
    $especialidades[] = $editando['especialidad'];
}

// Búsqueda de citas (filtra por paciente, especialidad, fecha, hora o estado)
function normalizar_texto($texto) {
    $texto = mb_strtolower((string) $texto, 'UTF-8');
    return strtr($texto, [
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
        'ü' => 'u', 'ñ' => 'n', 'à' => 'a', 'è' => 'e', 'ì' => 'i',
        'ò' => 'o', 'ù' => 'u', 'ç' => 'c',
    ]);
}

$busqueda = trim($_GET['q'] ?? $_POST['q'] ?? '');

$stmt = $pdo->query("SELECT * FROM citas ORDER BY id DESC");
$citas = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total_citas = count($citas);

if ($busqueda !== '') {
    $aguja = normalizar_texto($busqueda);
    $citas = array_values(array_filter($citas, function ($cita) use ($aguja) {
        $estado = $cita['estado'] ?: 'Registrada';
        $fecha_legible = date('d/m/Y', strtotime($cita['fecha']));
        $campos = [
            $cita['paciente_nombre'],
            $cita['especialidad'],
            $cita['fecha'],
            $fecha_legible,
            $cita['hora'],
            $estado,
        ];
        foreach ($campos as $campo) {
            if (strpos(normalizar_texto($campo), $aguja) !== false) {
                return true;
            }
        }
        return false;
    }));
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Sistema Médico Prototipo</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<div class="container">
    <?php if (!isset($_SESSION['usuario'])): ?>
        <div class="login-form">
            <h2>Acceso al Sistema Médico</h2>
            <?= $msg ?>
            <form method="POST">
                <input type="text" name="user" placeholder="Usuario (admin)" required>
                <input type="password" name="pass" placeholder="Contraseña (1234)" required>
                <button type="submit" name="login">Ingresar</button>
            </form>
        </div>

    <?php else: ?>
        <div class="header">
            <span>Bienvenido: <strong><?= $_SESSION['usuario'] ?></strong></span>
            <a href="?logout=1" class="logout">Cerrar Sesión</a>
        </div>

        <?php $modo_edicion = $editando !== null; ?>
        <div class="appointment-form">
            <h2 id="form-cita"><?= $modo_edicion ? 'Editar Cita Médica (ID ' . $editando['id'] . ')' : 'Agendar Cita Médica' ?></h2>
            <?= $msg ?>
            <form method="POST" action="index.php">
                <?php if ($modo_edicion): ?>
                    <input type="hidden" name="cita_id" value="<?= $editando['id'] ?>">
                <?php endif; ?>
                <input type="hidden" name="q" value="<?= htmlspecialchars($busqueda, ENT_QUOTES, 'UTF-8') ?>">
                <input type="text" name="paciente" placeholder="Nombre completo del paciente" required
                       value="<?= $modo_edicion ? htmlspecialchars($editando['paciente_nombre'], ENT_QUOTES, 'UTF-8') : '' ?>">
                <select name="especialidad">
                    <?php foreach ($especialidades as $esp): ?>
                        <option value="<?= htmlspecialchars($esp, ENT_QUOTES, 'UTF-8') ?>"
                            <?= $modo_edicion && $editando['especialidad'] === $esp ? 'selected' : '' ?>>
                            <?= htmlspecialchars($esp) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div style="display:flex; gap:10px;">
                    <input type="date" name="fecha" required
                           value="<?= $modo_edicion ? htmlspecialchars($editando['fecha'], ENT_QUOTES, 'UTF-8') : '' ?>">
                    <input type="time" name="hora" required
                           value="<?= $modo_edicion ? htmlspecialchars($editando['hora'], ENT_QUOTES, 'UTF-8') : '' ?>">
                </div>
                <button type="submit" name="<?= $modo_edicion ? 'actualizar' : 'agendar' ?>">
                    <?= $modo_edicion ? 'Guardar cambios' : 'Guardar en Base de Datos Local' ?>
                </button>
                <?php if ($modo_edicion): ?>
                    <a href="index.php<?= $busqueda !== '' ? '?q=' . urlencode($busqueda) : '' ?>" class="btn-secundario">Cancelar edición</a>
                <?php endif; ?>
            </form>
        </div>

        <h2>Reporte de Citas (Persistencia Local)</h2>

        <form method="GET" class="search-form" role="search">
            <span class="search-icon" aria-hidden="true">&#128269;</span>
            <input type="search" name="q" value="<?= htmlspecialchars($busqueda) ?>"
                   placeholder="Buscar por paciente, especialidad, fecha o estado..." aria-label="Buscar citas">
            <button type="submit" class="btn-buscar">Buscar</button>
            <?php if ($busqueda !== ''): ?>
                <a href="index.php" class="btn-limpiar">Limpiar</a>
            <?php endif; ?>
        </form>

        <?php if ($busqueda !== ''): ?>
            <p class="resultado-busqueda">
                <?= count($citas) ?> de <?= $total_citas ?> cita(s) coinciden con
                <strong>&laquo;<?= htmlspecialchars($busqueda) ?>&raquo;</strong>.
            </p>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Paciente</th>
                    <th>Especialidad</th>
                    <th>Fecha y Hora</th>
                    <th>Estado</th>
                    <th>Opciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($citas)): ?>
                    <tr>
                        <td colspan="6" class="sin-resultados">
                            <?= $busqueda !== ''
                                ? 'No se encontraron citas que coincidan con la búsqueda.'
                                : 'Aún no hay citas registradas.' ?>
                        </td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($citas as $row):
                    $estado = $row['estado'] ?: 'Registrada'; ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= htmlspecialchars($row['paciente_nombre']) ?></td>
                        <td><?= $row['especialidad'] ?></td>
                        <td><?= $row['fecha'] ?> | <?= $row['hora'] ?></td>
                        <td><span class="estado <?= strtolower($estado) ?>"><?= $estado ?></span></td>
                        <td class="celda-opciones">
                            <div class="menu-wrap">
                                <button type="button" class="btn-menu" aria-haspopup="true" aria-expanded="false"
                                        aria-label="Más opciones para la cita de <?= htmlspecialchars($row['paciente_nombre'], ENT_QUOTES, 'UTF-8') ?>">&#8942;</button>
                                <div class="menu-acciones" role="menu" hidden>
                                    <a class="menu-item" role="menuitem"
                                       href="index.php?editar=<?= $row['id'] ?><?= $busqueda !== '' ? '&amp;q=' . urlencode($busqueda) : '' ?>#form-cita">
                                        <span class="menu-icono">&#9998;</span> Editar
                                    </a>
                                    <form method="POST" action="index.php" class="form-eliminar"
                                          data-paciente="<?= htmlspecialchars($row['paciente_nombre'], ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="q" value="<?= htmlspecialchars($busqueda, ENT_QUOTES, 'UTF-8') ?>">
                                        <button type="submit" name="eliminar" value="<?= $row['id'] ?>"
                                                class="menu-item menu-item-peligro" role="menuitem">
                                            <span class="menu-icono">&#128465;</span> Eliminar
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php if (isset($_SESSION['usuario'])): ?>
<dialog id="dialogo-eliminar" class="dialogo-eliminar">
    <h3>Eliminar cita</h3>
    <p id="texto-eliminar">Esta acción no se puede deshacer.</p>
    <div class="dialogo-botones">
        <button type="button" id="cancelar-eliminar" class="btn-secundario">Cancelar</button>
        <button type="button" id="confirmar-eliminar" class="btn-peligro">Sí, eliminar</button>
    </div>
</dialog>

<script>
(function () {
    'use strict';

    // --- Menú de tres puntos -------------------------------------------------
    var menus = Array.prototype.slice.call(document.querySelectorAll('.menu-wrap'));

    function cerrarTodos(excepto) {
        menus.forEach(function (wrap) {
            if (wrap === excepto) return;
            var boton = wrap.querySelector('.btn-menu');
            var menu = wrap.querySelector('.menu-acciones');
            if (menu) menu.hidden = true;
            if (boton) boton.setAttribute('aria-expanded', 'false');
        });
    }

    menus.forEach(function (wrap) {
        var boton = wrap.querySelector('.btn-menu');
        var menu = wrap.querySelector('.menu-acciones');
        if (!boton || !menu) return;

        boton.addEventListener('click', function (evento) {
            evento.stopPropagation();
            var estabaAbierto = !menu.hidden;
            cerrarTodos(null);
            if (!estabaAbierto) {
                menu.hidden = false;
                boton.setAttribute('aria-expanded', 'true');
            }
        });

        // Los clics dentro del menú no deben cerrarlo antes de actuar
        menu.addEventListener('click', function (evento) {
            evento.stopPropagation();
        });
    });

    document.addEventListener('click', function () { cerrarTodos(null); });
    document.addEventListener('keydown', function (evento) {
        if (evento.key === 'Escape') cerrarTodos(null);
    });

    // --- Confirmación de eliminación ----------------------------------------
    var dialogo = document.getElementById('dialogo-eliminar');
    if (!dialogo) return;

    var texto = document.getElementById('texto-eliminar');
    var formularioPendiente = null;

    document.querySelectorAll('.form-eliminar').forEach(function (formulario) {
        formulario.addEventListener('submit', function (evento) {
            evento.preventDefault();
            formularioPendiente = formulario;
            var paciente = formulario.getAttribute('data-paciente') || 'este paciente';
            texto.textContent = 'Se eliminará definitivamente la cita de ' + paciente +
                '. Esta acción no se puede deshacer.';
            dialogo.showModal();
        });
    });

    document.getElementById('confirmar-eliminar').addEventListener('click', function () {
        if (formularioPendiente) formularioPendiente.submit();
    });

    document.getElementById('cancelar-eliminar').addEventListener('click', function () {
        dialogo.close();
    });

    dialogo.addEventListener('close', function () {
        formularioPendiente = null;
    });
})();
</script>
<?php endif; ?>

</body>
</html>