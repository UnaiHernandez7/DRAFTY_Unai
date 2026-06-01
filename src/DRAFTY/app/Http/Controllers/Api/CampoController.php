<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campo;
use Illuminate\Http\Request;

class CampoController extends Controller
{
    public function index()
    {
        return response()->json(Campo::all());
    }

    public function store(Request $request)
    {
        $campo = Campo::create($request->all());
        return response()->json($campo, 201);
    }

    public function show($id)
    {
        return response()->json(Campo::findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $campo = Campo::findOrFail($id);
        $campo->update($request->all());

        return response()->json($campo);
    }

    public function destroy($id)
    {
        Campo::findOrFail($id)->delete();
        return response()->json(['mensaje' => 'Campo eliminado']);
    }
}