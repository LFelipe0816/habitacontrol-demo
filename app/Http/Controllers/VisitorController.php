<?php

namespace App\Http\Controllers;

use App\Http\Resources\VisitorResource;
use App\Models\Unit;
use App\Models\Visitor;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VisitorController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->hasRole('admin', 'seguridad'), 403);

        return Inertia::render('Security/Index', [
            'visitors' => VisitorResource::collection(Visitor::with('unit')->latest('entered_at')->limit(100)->get()),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->hasRole('admin', 'seguridad'), 403);

        $data = $request->validate([
            'name' => 'required|string|max:120',
            'document' => 'required|string|max:40',
            'unit' => 'required|exists:units,code',
            'host' => 'required|string|max:120',
            'plate' => 'nullable|string|max:20',
        ]);

        Visitor::create([
            'unit_id' => Unit::where('code', $data['unit'])->value('id'),
            'entered_at' => now(),
        ] + collect($data)->except('unit')->all());

        return back()->with('success', 'Entrada registrada');
    }

    public function checkout(Request $request, Visitor $visitor)
    {
        abort_unless($request->user()->hasRole('admin', 'seguridad'), 403);

        $visitor->update(['left_at' => now()]);

        return back()->with('success', "Salida de {$visitor->name}");
    }
}
