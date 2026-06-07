<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Controlador que agrupa la logica de amistad en la API.
 */
class AmistadController extends Controller
{
    /**
     * Gestiona datos relacionados con amigos y solicitudes.
     */
    public function amigos(Request $request)
    {
        $usuarioId = $request->user()->id_usuario;
        [$columnaEmisor, $columnaReceptor] = $this->columnasUsuarios();

        $amistades = DB::table('amistades')
            ->whereIn('estado', ['aceptada', 'aceptado'])
            ->where(function ($query) use ($usuarioId, $columnaEmisor, $columnaReceptor) {
                $query->where($columnaEmisor, $usuarioId)
                    ->orWhere($columnaReceptor, $usuarioId);
            })
            ->get();

        $amigos = $amistades->map(function ($amistad) use ($usuarioId, $columnaEmisor, $columnaReceptor) {
                $idAmigo = (int) $amistad->{$columnaEmisor} === (int) $usuarioId
                    ? $amistad->{$columnaReceptor}
                    : $amistad->{$columnaEmisor};
                $amigo = Usuario::with('competitivo')->find($idAmigo);

                if (!$amigo) {
                    return null;
                }

                $amigo->id_amistad = $amistad->id_amistad;

                return $amigo;
            })
            ->filter()
            ->values();

        return response()->json($amigos);
    }

    /**
     * Ejecuta la logica principal de esta parte del proyecto.
     */
    public function recibidas(Request $request)
    {
        [$columnaEmisor, $columnaReceptor] = $this->columnasUsuarios();

        $solicitudes = DB::table('amistades')
            ->where($columnaReceptor, $request->user()->id_usuario)
            ->where('estado', 'pendiente')
            ->get()
            ->map(function ($amistad) use ($columnaEmisor) {
                $amistad->emisor = Usuario::find($amistad->{$columnaEmisor});
                return $amistad;
            });

        return response()->json($solicitudes);
    }

    /**
     * Ejecuta la logica principal de esta parte del proyecto.
     */
    public function enviadas(Request $request)
    {
        [$columnaEmisor, $columnaReceptor] = $this->columnasUsuarios();

        $solicitudes = DB::table('amistades')
            ->where($columnaEmisor, $request->user()->id_usuario)
            ->where('estado', 'pendiente')
            ->get()
            ->map(function ($amistad) use ($columnaReceptor) {
                $amistad->receptor = Usuario::find($amistad->{$columnaReceptor});
                return $amistad;
            });

        return response()->json($solicitudes);
    }

    /**
     * Cuenta notificaciones pendientes del apartado Amigos.
     *
     * Suma solicitudes de amistad, invitaciones a sala e invitaciones a equipo.
     *
     * @param Request $request Peticion autenticada.
     * @return \Illuminate\Http\JsonResponse Totales de notificaciones nuevas.
     */
    public function notificaciones(Request $request)
    {
        [, $columnaReceptor] = $this->columnasUsuarios();
        $usuarioId = $request->user()->id_usuario;

        $consulta = DB::table('amistades')
            ->where($columnaReceptor, $usuarioId)
            ->where('estado', 'pendiente');

        if (Schema::hasColumn('amistades', 'visto_por_receptor')) {
            $consulta->where(function ($query) {
                $query->where('visto_por_receptor', false)
                    ->orWhereNull('visto_por_receptor');
            });
        }

        $totalAmistades = $consulta->count();
        $totalInvitacionesSala = $this->totalInvitacionesSala($usuarioId);
        $totalInvitacionesEquipo = $this->totalInvitacionesEquipo($usuarioId);
        $total = $totalAmistades + $totalInvitacionesSala + $totalInvitacionesEquipo;

        return response()->json([
            'hay_nuevas' => $total > 0,
            'total' => $total,
            'amistades' => $totalAmistades,
            'salas' => $totalInvitacionesSala,
            'equipos' => $totalInvitacionesEquipo
        ]);
    }

    /**
     * Marca como vistas las notificaciones del apartado Amigos.
     *
     * Actualiza solicitudes de amistad e invitaciones pendientes cuando
     * existen las columnas de visto correspondientes.
     *
     * @param Request $request Peticion autenticada.
     * @return \Illuminate\Http\JsonResponse Confirmacion de marcado.
     */
    public function marcarNotificacionesVistas(Request $request)
    {
        [, $columnaReceptor] = $this->columnasUsuarios();

        if (Schema::hasColumn('amistades', 'visto_por_receptor')) {
            DB::table('amistades')
                ->where($columnaReceptor, $request->user()->id_usuario)
                ->where('estado', 'pendiente')
                ->update(['visto_por_receptor' => true]);
        }

        $usuarioId = $request->user()->id_usuario;

        if (Schema::hasTable('participantes_partido') && Schema::hasColumn('participantes_partido', 'visto_por_invitado')) {
            DB::table('participantes_partido')
                ->where('id_usuario', $usuarioId)
                ->where('estado_participacion', 'pendiente')
                ->update(['visto_por_invitado' => true]);
        }

        if (Schema::hasTable('equipo_usuarios') && Schema::hasColumn('equipo_usuarios', 'visto_por_invitado')) {
            DB::table('equipo_usuarios')
                ->where('id_usuario', $usuarioId)
                ->where('rol_en_equipo', 'invitado')
                ->update(['visto_por_invitado' => true]);
        }

        return response()->json(['mensaje' => 'Notificaciones vistas']);
    }

    /**
     * Ejecuta la logica principal de esta parte del proyecto.
     */
    public function enviar(Request $request, $idUsuario)
    {
        $emisor = $request->user();
        $receptor = Usuario::findOrFail($idUsuario);

        if ((int) $emisor->id_usuario === (int) $receptor->id_usuario) {
            return response()->json(['mensaje' => 'No puedes enviarte una solicitud a ti mismo'], 422);
        }

        [$columnaEmisor, $columnaReceptor] = $this->columnasUsuarios();
        $amistadExistente = DB::table('amistades')->where(function ($query) use ($emisor, $receptor, $columnaEmisor, $columnaReceptor) {
            $query->where($columnaEmisor, $emisor->id_usuario)
                ->where($columnaReceptor, $receptor->id_usuario);
        })->orWhere(function ($query) use ($emisor, $receptor, $columnaEmisor, $columnaReceptor) {
            $query->where($columnaEmisor, $receptor->id_usuario)
                ->where($columnaReceptor, $emisor->id_usuario);
        })->first();

        if ($amistadExistente) {
            if ($amistadExistente->estado === 'rechazada') {
                DB::table('amistades')
                    ->where('id_amistad', $amistadExistente->id_amistad)
                    ->update($this->datosSolicitud($emisor->id_usuario, $receptor->id_usuario, 'pendiente'));

                return response()->json([
                    'mensaje' => 'Solicitud enviada correctamente'
                ], 201);
            }

            $mensaje = in_array($amistadExistente->estado, ['aceptada', 'aceptado'], true)
                ? 'Ya sois amigos'
                : 'Ya existe una solicitud entre estos usuarios';

            return response()->json(['mensaje' => $mensaje], 422);
        }

        DB::table('amistades')->insert($this->datosSolicitud($emisor->id_usuario, $receptor->id_usuario, 'pendiente'));

        return response()->json([
            'mensaje' => 'Solicitud enviada correctamente'
        ], 201);
    }

    /**
     * Ejecuta la logica principal de esta parte del proyecto.
     */
    public function aceptar(Request $request, $id)
    {
        $amistad = DB::table('amistades')->where('id_amistad', $id)->first();

        if (!$amistad) {
            return response()->json(['mensaje' => 'Solicitud no encontrada'], 404);
        }

        [, $columnaReceptor] = $this->columnasUsuarios();

        if ((int) $amistad->{$columnaReceptor} !== (int) $request->user()->id_usuario) {
            return response()->json(['mensaje' => 'No puedes aceptar esta solicitud'], 403);
        }

        DB::table('amistades')
            ->where('id_amistad', $id)
            ->update($this->datosRespuesta('aceptada'));

        return response()->json(['mensaje' => 'Solicitud aceptada']);
    }

    /**
     * Ejecuta la logica principal de esta parte del proyecto.
     */
    public function rechazar(Request $request, $id)
    {
        $amistad = DB::table('amistades')->where('id_amistad', $id)->first();

        if (!$amistad) {
            return response()->json(['mensaje' => 'Solicitud no encontrada'], 404);
        }

        [, $columnaReceptor] = $this->columnasUsuarios();

        if ((int) $amistad->{$columnaReceptor} !== (int) $request->user()->id_usuario) {
            return response()->json(['mensaje' => 'No puedes rechazar esta solicitud'], 403);
        }

        DB::table('amistades')
            ->where('id_amistad', $id)
            ->update($this->datosRespuesta('rechazada'));

        return response()->json(['mensaje' => 'Solicitud rechazada']);
    }

    /**
     * Elimina el recurso indicado cuando el usuario tiene permiso.
     */
    public function destroy(Request $request, $id)
    {
        $amistad = DB::table('amistades')->where('id_amistad', $id)->first();

        if (!$amistad) {
            return response()->json(['mensaje' => 'Amistad no encontrada'], 404);
        }

        [$columnaEmisor, $columnaReceptor] = $this->columnasUsuarios();
        $usuarioId = $request->user()->id_usuario;

        if ((int) $amistad->{$columnaEmisor} !== (int) $usuarioId && (int) $amistad->{$columnaReceptor} !== (int) $usuarioId) {
            return response()->json(['mensaje' => 'No puedes eliminar esta amistad'], 403);
        }

        DB::table('amistades')->where('id_amistad', $id)->delete();

        return response()->json(['mensaje' => 'Amistad eliminada']);
    }

    /**
     * Gestiona informacion de usuarios.
     */
    private function columnasUsuarios(): array
    {
        return Schema::hasColumn('amistades', 'id_usuario_emisor')
            ? ['id_usuario_emisor', 'id_usuario_receptor']
            : ['id_usuario', 'id_amigo'];
    }

    /**
     * Cuenta invitaciones nuevas a salas para un usuario.
     *
     * @param int $usuarioId Identificador del usuario invitado.
     * @return int Total de invitaciones pendientes no vistas.
     */
    private function totalInvitacionesSala(int $usuarioId): int
    {
        if (!Schema::hasTable('participantes_partido')) {
            return 0;
        }

        $consulta = DB::table('participantes_partido')
            ->where('id_usuario', $usuarioId)
            ->where('estado_participacion', 'pendiente');

        if (Schema::hasColumn('participantes_partido', 'visto_por_invitado')) {
            $consulta->where(function ($query) {
                $query->where('visto_por_invitado', false)
                    ->orWhereNull('visto_por_invitado');
            });
        }

        return $consulta->count();
    }

    /**
     * Cuenta invitaciones nuevas a equipos para un usuario.
     *
     * @param int $usuarioId Identificador del usuario invitado.
     * @return int Total de invitaciones pendientes no vistas.
     */
    private function totalInvitacionesEquipo(int $usuarioId): int
    {
        if (!Schema::hasTable('equipo_usuarios')) {
            return 0;
        }

        $consulta = DB::table('equipo_usuarios')
            ->where('id_usuario', $usuarioId)
            ->where('rol_en_equipo', 'invitado');

        if (Schema::hasColumn('equipo_usuarios', 'visto_por_invitado')) {
            $consulta->where(function ($query) {
                $query->where('visto_por_invitado', false)
                    ->orWhereNull('visto_por_invitado');
            });
        }

        return $consulta->count();
    }

    /**
     * Ejecuta la logica principal de esta parte del proyecto.
     */
    private function datosSolicitud(int $idEmisor, int $idReceptor, string $estado): array
    {
        [$columnaEmisor, $columnaReceptor] = $this->columnasUsuarios();
        $datos = [
            $columnaEmisor => $idEmisor,
            $columnaReceptor => $idReceptor,
            'estado' => $estado,
        ];

        if (Schema::hasColumn('amistades', 'fecha_solicitud')) {
            $datos['fecha_solicitud'] = now();
        }

        if (Schema::hasColumn('amistades', 'fecha_respuesta')) {
            $datos['fecha_respuesta'] = null;
        }

        if (Schema::hasColumn('amistades', 'fecha_creacion')) {
            $datos['fecha_creacion'] = now();
        }

        if (Schema::hasColumn('amistades', 'visto_por_receptor')) {
            $datos['visto_por_receptor'] = false;
        }

        return $datos;
    }

    /**
     * Ejecuta la logica principal de esta parte del proyecto.
     */
    private function datosRespuesta(string $estado): array
    {
        $datos = ['estado' => $estado];

        if (Schema::hasColumn('amistades', 'fecha_respuesta')) {
            $datos['fecha_respuesta'] = now();
        }

        if (Schema::hasColumn('amistades', 'visto_por_receptor')) {
            $datos['visto_por_receptor'] = true;
        }

        return $datos;
    }
}
