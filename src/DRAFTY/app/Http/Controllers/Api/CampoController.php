<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campo;
use Illuminate\Http\Request;

/**
 * Controlador que agrupa la logica de campo en la API.
 */
class CampoController extends Controller
{
    /**
     * Devuelve el listado principal de recursos.
     */
    public function index()
    {
        return response()->json(Campo::all());
    }

    /**
     * Guarda un nuevo recurso con los datos recibidos.
     */
    public function store(Request $request)
    {
        $campo = Campo::create($request->all());
        return response()->json($campo, 201);
    }

    /**
     * Devuelve el detalle del recurso solicitado.
     */
    public function show($id)
    {
        return response()->json(Campo::findOrFail($id));
    }

    /**
     * Actualiza los datos del recurso indicado.
     */
    public function update(Request $request, $id)
    {
        $campo = Campo::findOrFail($id);
        $campo->update($request->all());

        return response()->json($campo);
    }

    /**
     * Elimina el recurso indicado cuando el usuario tiene permiso.
     */
    public function destroy($id)
    {
        Campo::findOrFail($id)->delete();
        return response()->json(['mensaje' => 'Campo eliminado']);
    }
}