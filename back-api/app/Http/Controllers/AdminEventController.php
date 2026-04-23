<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Zone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminEventController extends Controller
{
    public function index()
    {
        if (!session('admin_logged_in')) return redirect()->route('admin.login');
        
        $events = Event::withCount('registrations')->latest()->get();
        $zones = Zone::all();
        return view('admin.events.index', compact('events', 'zones'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'zone' => 'required',
            'event_date' => 'required|date',
            'duration' => 'required|integer|min:1',
            'capacity' => 'required|integer|min:1',
            'type' => 'required'
        ]);

        $data = $request->all();

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('events', 'public');
            $data['image'] = '/storage/' . $path;
        }

        Event::create($data);

        return redirect()->back()->with('success', 'Evento creado correctamente.');
    }

    public function update(Request $request, $id)
    {
        $event = Event::findOrFail($id);
        $data = $request->all();

        if ($request->hasFile('image')) {
            // Eliminar anterior si existe
            if ($event->image && !str_contains($event->image, 'http')) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $event->image));
            }
            $path = $request->file('image')->store('events', 'public');
            $data['image'] = '/storage/' . $path;
        }

        $event->update($data);
        return redirect()->back()->with('success', 'Evento actualizado.');
    }

    public function toggleActive($id)
    {
        $event = Event::findOrFail($id);
        $event->is_active = !$event->is_active;
        $event->save();
        return redirect()->back()->with('success', 'Estatus actualizado.');
    }

    public function destroy($id)
    {
        Event::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'Evento eliminado.');
    }
}
