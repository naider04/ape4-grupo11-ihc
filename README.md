# Sistema Web de Citas Médicas Portable

Prototipo académico de un sistema de citas médicas que funciona **sin servidor de base de datos**:
PHP portable + SQLite embebido. Todo el sistema vive en una carpeta y puede ejecutarse desde un
USB o desde cualquier ruta del equipo.

Proyecto de la asignatura **Interacción Humano-Computador** — UNEMI.

---

## Características

- Inicio de sesión con credenciales (`admin` / `1234`).
- Registro de citas con nombre del paciente, especialidad, fecha y hora.
- Edición y eliminación de citas desde un menú de opciones (⋮) en cada fila, con confirmación antes de borrar.
- Buscador de citas por paciente, especialidad, fecha, hora o estado, sin distinguir mayúsculas ni tildes.
- Reporte de citas leídas directamente desde la base de datos local.
- Base de datos SQLite (`citas_medicas.db`) creada automáticamente en el primer arranque.
- Interfaz web local (`http://127.0.0.1:8080`) con HTML/CSS separados de la lógica.
- Consultas preparadas (PDO) para evitar inyección SQL.

## Estructura del proyecto

```
Sistema_Medico_Portable/
├── php/                        # Intérprete PHP portable (no incluido en el repositorio)
├── css/style.css               # Hoja de estilos de la interfaz
├── index.php                   # Interfaz y lógica de login, registro y reporte
├── db_config.php               # Conexión PDO y creación automática de tablas
├── citas_medicas.db            # Base de datos SQLite (se crea sola si no existe)
├── EJECUTAR_SISTEMA.bat        # Lanzador del servidor local en Windows
└── Guía de configuraciones/    # Notas de instalación y activación del driver
```

## Requisitos

- Windows 10 o superior (64 bits).
- PHP 8.x portable en la carpeta `php/` (ver `Guía de configuraciones/php descarga.txt`).
- Extensión `pdo_sqlite` habilitada en `php/php.ini`.
- Navegador actualizado (Chrome, Edge o Firefox).

## Instalación

1. Descargar PHP 8 (versión Thread Safe, x64) y descomprimirlo dentro de la carpeta del proyecto
   con el nombre `php`.
2. Abrir `php/php.ini` y habilitar la línea `extension=pdo_sqlite` (quitar el `;` inicial).
3. Ejecutar `EJECUTAR_SISTEMA.bat` con doble clic.
4. El navegador abrirá `http://127.0.0.1:8080`.
5. Iniciar sesión con usuario `admin` y contraseña `1234`.

La base de datos `citas_medicas.db` se crea automáticamente y no requiere importar ningún
script SQL externo.

## Uso

1. Ingresar con las credenciales por defecto.
2. Completar el formulario: paciente, especialidad, fecha y hora.
3. Pulsar **Guardar en Base de Datos Local**.
4. Verificar la cita en la tabla del reporte, que se lee desde SQLite.
5. Para modificar o borrar una cita, pulsar el botón **⋮ (tres puntos)** de su fila:
   - **Editar** carga el formulario superior con los datos de la cita y el botón cambia a
     **Guardar cambios** (o **Cancelar edición** para descartar).
   - **Eliminar** abre un diálogo de confirmación; al aceptar, el registro se borra de SQLite
     definitivamente. Esta acción no se puede deshacer.
6. Para localizar una cita, usar la lupa sobre la tabla: escribe el nombre del paciente, la
   especialidad, una fecha (`2026-04-30` o `30/04/2026`), la hora o el estado. El contador
   indica cuántas coinciden y **Limpiar** restaura la lista completa.

## Estructura de la base de datos

| Tabla | Columnas |
|---|---|
| `usuarios` | `id`, `usuario`, `password` |
| `citas` | `id`, `paciente_nombre`, `especialidad`, `fecha`, `hora`, `estado` |

## Notas de seguridad

Este proyecto es un **prototipo académico**. La contraseña se almacena en texto plano y la
aplicación solo atiende un usuario local a la vez. Para un entorno real se recomienda usar
`password_hash()` de PHP, copias de seguridad automáticas del archivo `.db` y una prueba de
usabilidad con usuarios del área de salud.

## Documentación

El manual de instalación, el manual de usuario y el manual técnico se entregan en el documento
PDF de la práctica, junto con las capturas de evidencia del sistema en ejecución.
