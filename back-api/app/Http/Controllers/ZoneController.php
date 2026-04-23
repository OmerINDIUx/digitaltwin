<?php

namespace App\Http\Controllers;

use App\Models\Zone;
use Illuminate\Http\Request;

class ZoneController extends Controller
{
    public function index()
    {
        if (!session('admin_logged_in')) {
            return redirect()->route('admin.login')->withErrors(['email' => 'Debes iniciar sesión.']);
        }
        $zones = Zone::all();
        return view('admin.zones.index', compact('zones'));
    }

    public function update(Request $request, Zone $zone)
    {
        if (!session('admin_logged_in')) {
            return redirect()->route('admin.login')->withErrors(['email' => 'Sesión expirada.']);
        }

        $data = $request->validate([
            'name'                   => 'required|string|max:100',
            'capacity'               => 'required|integer|min:0',
            'max_reservation_hours'  => 'required|integer|min:1|max:24',
            'opening_hour'           => 'required',
            'closing_hour'           => 'required',
            'rest_days'              => 'nullable|array',
            'rest_start_hour'        => 'nullable',
            'rest_end_hour'          => 'nullable',
            'schedules'              => 'nullable|array',
            'status'                 => 'required|in:active,inactive',
            'image'                  => 'nullable|string',
            'description'            => 'nullable|string',
        ]);

        if (isset($data['rest_days'])) {
            $data['rest_days'] = implode(',', $data['rest_days']);
        } else {
            $data['rest_days'] = null;
        }

        $zone->update($data);

        return back()->with('success', "Zona '{$zone->name}' actualizada correctamente.");
    }

    public function store(Request $request)
    {
        if (!session('admin_logged_in')) {
            return redirect()->route('admin.login')->withErrors(['email' => 'Sesión expirada.']);
        }

        $data = $request->validate([
            'slug'                   => 'required|string|unique:zones,slug|alpha_dash',
            'name'                   => 'required|string|max:100',
            'capacity'               => 'required|integer|min:0',
            'max_reservation_hours'  => 'required|integer|min:1|max:24',
            'opening_hour'           => 'required',
            'closing_hour'           => 'required',
            'icon'                   => 'nullable|string',
            'image'                  => 'nullable|string',
        ]);

        Zone::create($data);

        return back()->with('success', "Nueva zona '{$data['name']}' creada.");
    }
}
