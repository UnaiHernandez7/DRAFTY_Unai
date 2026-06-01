<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UsuarioController;
use App\Http\Controllers\Api\EquipoController;
use App\Http\Controllers\Api\CampoController;
use App\Http\Controllers\Api\PartidoController;
use App\Http\Controllers\Api\TorneoController;
use App\Http\Controllers\Api\EstadisticaController;
use App\Http\Controllers\Api\CompetitivoController;
use App\Http\Controllers\Api\AmistadController;
use App\Http\Controllers\Api\PagoController;
use App\Http\Controllers\Api\ResultadoPartidoController;
use App\Http\Controllers\Api\GolPartidoController;
use App\Http\Controllers\Api\MvpController;
use App\Http\Controllers\Api\ValoracionJugadorController;

// Rutas públicas (no requieren login)

Route::post('/register', [AuthController::class, 'register']);
Route::post('/verificar-codigo', [AuthController::class, 'verificarCodigo']);
Route::post('/reenviar-codigo', [AuthController::class, 'reenviarCodigo']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/usuarios/nombre-disponible', [UsuarioController::class, 'comprobarNombreUsuario']);

Route::get('/partidos/cercanos', [PartidoController::class, 'cercanos']);
Route::get('/partidos', [PartidoController::class, 'index']);
Route::get('/partidos/{id}', [PartidoController::class, 'show']);

Route::get('/campos', [CampoController::class, 'index']);
Route::get('/campos/{id}', [CampoController::class, 'show']);

Route::get('/torneos', [TorneoController::class, 'index']);
Route::get('/torneos/{id}', [TorneoController::class, 'show']);

// Rutas protegidas (requieren token)

Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/perfil', [AuthController::class, 'perfil']);

    // Usuarios
    Route::get('/usuarios/buscar', [UsuarioController::class, 'buscar']);
    Route::get('/usuarios/{id}/valoraciones', [ValoracionJugadorController::class, 'usuario']);
    Route::apiResource('usuarios', UsuarioController::class);
    Route::patch('/perfil', [UsuarioController::class, 'actualizarPerfil']);
    Route::get('/amigos', [AmistadController::class, 'amigos']);
    Route::get('/amistades/recibidas', [AmistadController::class, 'recibidas']);
    Route::get('/amistades/enviadas', [AmistadController::class, 'enviadas']);
    Route::get('/amistades/notificaciones', [AmistadController::class, 'notificaciones']);
    Route::post('/amistades/notificaciones/vistas', [AmistadController::class, 'marcarNotificacionesVistas']);
    Route::post('/amistades/enviar/{id_usuario}', [AmistadController::class, 'enviar']);
    Route::put('/amistades/{id}/aceptar', [AmistadController::class, 'aceptar']);
    Route::put('/amistades/{id}/rechazar', [AmistadController::class, 'rechazar']);
    Route::delete('/amistades/{id}', [AmistadController::class, 'destroy']);

    // Partidos
    Route::apiResource('partidos', PartidoController::class)->except(['index', 'show']);
    Route::get('/mis-partidos', [PartidoController::class, 'misPartidos']);
    Route::get('/mis-partidos-detalle', [PartidoController::class, 'misPartidosDetalle']);
    Route::get('/historial-partidos', [PartidoController::class, 'historialPartidos']);
    Route::get('/partidos-invitaciones', [PartidoController::class, 'invitaciones']);
    Route::get('/admin/partidos', [PartidoController::class, 'adminIndex']);
    
    // Unirse a partido
    Route::post('/partidos/{id}/unirse', [PartidoController::class, 'unirse']);
    Route::post('/partidos/{id}/invitar-amigo/{id_usuario}', [PartidoController::class, 'invitarAmigo']);
    Route::put('/partidos/{id}/invitacion/aceptar', [PartidoController::class, 'aceptarInvitacion']);
    Route::delete('/partidos/{id}/invitacion', [PartidoController::class, 'rechazarInvitacion']);
    Route::post('/partidos/codigo/{codigo}/unirse', [PartidoController::class, 'unirsePorCodigo']);
    Route::post('/partidos/{id}/salir', [PartidoController::class, 'salir']);
    Route::get('/partidos/{id}/sala', [PartidoController::class, 'sala']);
    Route::get('/partidos/{id}/mensajes', [PartidoController::class, 'mensajes']);
    Route::post('/partidos/{id}/mensajes', [PartidoController::class, 'enviarMensaje']);
    Route::patch('/partidos/{id}/posicion', [PartidoController::class, 'cambiarPosicion']);
    Route::patch('/partidos/{id}/formacion', [PartidoController::class, 'cambiarFormacion']);
    Route::patch('/partidos/{id}/resultado', [PartidoController::class, 'cambiarResultado']);
    Route::patch('/partidos/{id}/cancelar', [PartidoController::class, 'cancelar']);
    Route::post('/partidos/{id}/comprobar-cancelacion', [ResultadoPartidoController::class, 'comprobarCancelacion']);
    Route::get('/partidos/{id}/resultado', [ResultadoPartidoController::class, 'show']);
    Route::post('/partidos/{id}/resultado', [ResultadoPartidoController::class, 'store']);
    Route::put('/partidos/{id}/resultado/confirmar', [ResultadoPartidoController::class, 'confirmar']);
    Route::post('/partidos/{id}/goles', [GolPartidoController::class, 'store']);
    Route::delete('/goles/{id}', [GolPartidoController::class, 'destroy']);
    Route::get('/partidos/{id}/mvp', [MvpController::class, 'index']);
    Route::post('/partidos/{id}/mvp/votar', [MvpController::class, 'votar']);
    Route::post('/partidos/{id}/valoraciones', [ValoracionJugadorController::class, 'store']);

    // Campos
    Route::apiResource('campos', CampoController::class)->except(['index', 'show']);

    // Equipos
    Route::apiResource('equipos', EquipoController::class);
    Route::get('/mis-equipos', [EquipoController::class, 'misEquipos']);
    Route::get('/equipos-invitaciones', [EquipoController::class, 'invitaciones']);
    Route::get('/equipos/{id}/jugadores', [EquipoController::class, 'jugadores']);
    Route::get('/equipos/{id}/partidos', [EquipoController::class, 'partidos']);
    Route::get('/equipos/{id}/historial', [EquipoController::class, 'historial']);
    Route::get('/equipos/{id}/torneos-ganados', [EquipoController::class, 'torneosGanados']);
    Route::get('/equipos/{id}/ranking', [EquipoController::class, 'ranking']);
    Route::patch('/equipos/{id}/jugadores/{idUsuario}/rol', [EquipoController::class, 'cambiarRolMiembro']);
    Route::delete('/equipos/{id}/jugadores/{idUsuario}', [EquipoController::class, 'expulsarMiembro']);
    Route::get('/equipos/{id}/mensajes', [EquipoController::class, 'mensajes']);
    Route::post('/equipos/{id}/mensajes', [EquipoController::class, 'enviarMensaje']);
    Route::post('/equipos/{id}/partidos/{idPartido}/unirse', [EquipoController::class, 'unirseAPartido']);
    Route::post('/equipos/{id}/invitar-amigo/{id_usuario}', [EquipoController::class, 'invitarAmigo']);
    Route::put('/equipos/{id}/invitacion/aceptar', [EquipoController::class, 'aceptarInvitacion']);
    Route::delete('/equipos/{id}/invitacion', [EquipoController::class, 'rechazarInvitacion']);
    Route::post('/equipos/{id}/unirse', [EquipoController::class, 'unirse']);
    Route::post('/equipos/{id}/salir', [EquipoController::class, 'salir']);

    // Torneos
    Route::apiResource('torneos', TorneoController::class)->except(['index', 'show']);
    Route::post('/torneos/{id}/unirse', [TorneoController::class, 'unirse']);
    Route::post('/torneos/{id}/inscribir', [TorneoController::class, 'inscribirEquipo']);
    Route::post('/torneos/{id}/iniciar', [TorneoController::class, 'iniciar']);
    Route::get('/torneos/{id}/brackets', [TorneoController::class, 'brackets']);
    Route::get('/torneos/{id}/ranking-goles', [TorneoController::class, 'rankingGoles']);
    Route::get('/torneos/{id}/ranking-porterias', [TorneoController::class, 'rankingPorterias']);
    Route::post('/torneos/partidos/{id}/goles', [TorneoController::class, 'guardarGol']);
    Route::delete('/torneos/goles/{id}', [TorneoController::class, 'eliminarGol']);
    Route::put('/torneos/partidos/{id}/resultado', [TorneoController::class, 'resultadoPartido']);

    // Estadísticas
    Route::get('/mis-estadisticas', [EstadisticaController::class, 'misEstadisticas']);
    Route::apiResource('estadisticas', EstadisticaController::class);

    // Competitivo
    Route::get('/pagos', [PagoController::class, 'index']);
    Route::post('/competitivo/activar', [PagoController::class, 'activarCompetitivo']);
    Route::put('/pagos/{id}/confirmar', [PagoController::class, 'confirmar']);
    Route::put('/pagos/{id}/cancelar', [PagoController::class, 'cancelar']);
    Route::get('/mi-competitivo', [CompetitivoController::class, 'miPerfil']);
    Route::get('/competitivo-rankings', [CompetitivoController::class, 'rankings']);
    Route::get('/competitivo-rankings/amigos', [CompetitivoController::class, 'rankingsAmigos']);
    Route::get('/competitivo/buscar', [PartidoController::class, 'buscarCompetitivo']);
    Route::post('/competitivo/buscar-partida', [PartidoController::class, 'buscarPartidaCompetitiva']);
    Route::apiResource('competitivo', CompetitivoController::class);

});
