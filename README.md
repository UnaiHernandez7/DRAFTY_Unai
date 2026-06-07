# DRAFTY

## Descripcion del proyecto

DRAFTY es una aplicacion web orientada a la gestion y organizacion de partidos de futbol amateur. El proyecto permite a los usuarios encontrar partidos cercanos, crear salas, unirse a equipos, participar en torneos y consultar su progreso competitivo dentro de una plataforma centralizada.

La aplicacion ha sido desarrollada como proyecto final de Desarrollo Web, integrando un frontend moderno con React y un backend basado en Laravel. El objetivo principal es ofrecer una experiencia completa para jugadores que buscan organizar partidos de forma sencilla, gestionar equipos y competir con otros usuarios.

## Objetivos

- Facilitar la creacion y busqueda de partidos de futbol.
- Permitir la gestion de equipos, invitaciones y salas de partido.
- Incorporar funcionalidades sociales como amistades, solicitudes e invitaciones.
- Ofrecer un sistema competitivo con estadisticas, rankings y rangos.
- Centralizar la informacion de partidos, torneos, equipos y usuarios en una unica plataforma.

## Funcionalidades principales

### Gestion de usuarios

- Registro de usuarios con verificacion mediante codigo.
- Inicio de sesion con nombre de usuario o correo electronico.
- Recuperacion y cambio de contrasena.
- Perfil de usuario con datos personales y preferencias deportivas.

### Partidos

- Creacion de partidos personalizados.
- Busqueda de partidos por cercania, ciudad, fecha y tipo de futbol.
- Unirse a partidos publicos o mediante codigo privado.
- Sala de partido con participantes, alineaciones, chat y gestion de posiciones.
- Invitaciones a otros usuarios segun amistad o posicion favorita.

### Equipos

- Creacion y administracion de equipos.
- Union a equipos publicos.
- Invitaciones a equipos privados.
- Gestion de miembros, roles y capitanes.
- Historial y ranking interno del equipo.

### Amigos y notificaciones

- Envio, aceptacion y rechazo de solicitudes de amistad.
- Listado de amigos, solicitudes recibidas y solicitudes enviadas.
- Notificaciones visuales para solicitudes de amistad, invitaciones a salas e invitaciones a equipos.

### Competitivo

- Activacion del modo competitivo.
- Busqueda de partidas competitivas.
- Sistema de puntos, rangos y progreso.
- Rankings globales y rankings entre amigos.
- Estadisticas competitivas como victorias, derrotas, goles, MVP y porterias a cero.

### Torneos

- Creacion y consulta de torneos.
- Inscripcion de equipos.
- Generacion de brackets.
- Gestion de resultados, goles y clasificaciones.

## Tecnologias utilizadas

### Frontend

- React
- Vite
- React Router
- Leaflet y React Leaflet
- CSS modular por componentes

### Backend

- PHP 8.2
- Laravel 12
- Laravel Sanctum
- Eloquent ORM
- Migraciones, seeders y factories

### Base de datos y entorno

- MySQL 8
- Docker
- Caddy
- Composer
- npm

## Estructura del proyecto

```text
DRAFTY_Unai/
+-- frontend/              # Aplicacion cliente desarrollada con React y Vite
+-- src/DRAFTY/            # Backend principal desarrollado con Laravel
|   +-- app/               # Modelos, controladores, requests y mails
|   +-- database/          # Migraciones, seeders y factories
|   +-- routes/            # Rutas de la API
|   +-- resources/         # Vistas y recursos de Laravel
+-- php/                   # Configuracion del contenedor PHP
+-- docker-compose.yml     # Servicios Docker del proyecto
+-- README.md              # Documentacion principal
```

## Instalacion y ejecucion

### Requisitos previos

- PHP 8.2 o superior
- Composer
- Node.js y npm
- MySQL
- Docker, si se desea ejecutar el entorno mediante contenedores

### Backend

Desde la carpeta del backend:

```bash
cd src/DRAFTY
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan serve
```

### Frontend

Desde la carpeta del frontend:

```bash
cd frontend
npm install
npm run dev
```

### Ejecucion con Docker

Desde la raiz del proyecto:

```bash
docker compose up -d
```

Este comando levanta los servicios definidos para el servidor web, PHP y la base de datos.

## Base de datos

El proyecto utiliza migraciones de Laravel para crear la estructura de la base de datos. Tambien incluye seeders y factories para generar datos iniciales, como usuarios, campos, equipos y partidos de ejemplo.

Comandos principales:

```bash
php artisan migrate
php artisan db:seed
```

## API y autenticacion

La comunicacion entre frontend y backend se realiza mediante una API REST desarrollada en Laravel. Las rutas protegidas utilizan Laravel Sanctum, por lo que los usuarios autenticados acceden mediante token.

La API gestiona operaciones relacionadas con:

- Autenticacion y perfil.
- Usuarios y amistades.
- Partidos y salas.
- Equipos e invitaciones.
- Competitivo y rankings.
- Torneos y resultados.

## Documentacion del codigo

El backend esta documentado mediante PHPDoc para facilitar la lectura del codigo y permitir la generacion posterior de documentacion tecnica. El frontend incluye comentarios basicos en espanol para explicar estados, funciones y bloques principales de la interfaz.

## Estado del proyecto

DRAFTY se encuentra en una version funcional orientada a su presentacion como proyecto final. Incluye las funcionalidades principales necesarias para registrar usuarios, gestionar partidos, equipos, torneos, amistades y competicion.

## Autor

Proyecto desarrollado por Unai como trabajo final de Desarrollo Web.
