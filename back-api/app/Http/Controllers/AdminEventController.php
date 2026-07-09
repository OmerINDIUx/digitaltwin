<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Zone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class AdminEventController extends Controller
{
    public function index()
    {
        if (!session('admin_logged_in')) return redirect()->route('admin.login');
        
        $events = Event::with([
            'registrations' => fn ($query) => $query->latest(),
        ])->withCount([
            'activeRegistrations as registrations_count',
        ])->latest()->get();
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
            'type' => 'required',
            'attachments.*' => 'file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,webp|max:10240',
        ]);

        $data = $request->except(['attachments']);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('events', 'public');
            $this->mirrorToPublicStorage($path);
            $data['image'] = '/storage/' . $path;
        }

        $data['attachments'] = $this->storeAttachments($request);

        Event::create($data);

        return redirect()->back()->with('success', 'Evento creado correctamente.');
    }

    public function update(Request $request, $id)
    {
        $event = Event::findOrFail($id);
        $request->validate([
            'attachments.*' => 'file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,webp|max:10240',
            'remove_attachments' => 'array',
            'remove_attachments.*' => 'string',
        ]);

        $data = $request->except(['attachments', 'remove_attachments']);

        if ($request->hasFile('image')) {
            // Eliminar anterior si existe
            if ($event->image && !str_contains($event->image, 'http')) {
                $oldImagePath = preg_replace('#^/?storage/#', '', (string) $event->getRawOriginal('image'));
                if ($oldImagePath) {
                    Storage::disk('public')->delete($oldImagePath);
                    $this->deletePublicStorageMirror($oldImagePath);
                }
            }
            $path = $request->file('image')->store('events', 'public');
            $this->mirrorToPublicStorage($path);
            $data['image'] = '/storage/' . $path;
        }

        $data['attachments'] = $this->mergeAttachments(
            $event,
            $request->input('remove_attachments', []),
            $this->storeAttachments($request)
        );

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

    public function updateRegistrationStatus(Request $request, EventRegistration $registration)
    {
        if (!session('admin_logged_in')) return redirect()->route('admin.login');

        $data = $request->validate([
            'status' => 'required|in:aceptado,rechazado,por_pagar,pagado',
        ]);

        $registration->update($data);
        $registration->event?->syncActiveAttendeeCount();

        return redirect()->back()
            ->with('success', 'Estatus de inscripción actualizado.')
            ->with('open_event_details', $registration->event_id);
    }

    public function destroy($id)
    {
        $event = Event::findOrFail($id);

        foreach ($event->attachments ?? [] as $attachment) {
            if (!empty($attachment['path'])) {
                Storage::disk('public')->delete($attachment['path']);
                $this->deletePublicStorageMirror($attachment['path']);
            }
        }

        $event->delete();
        return redirect()->back()->with('success', 'Evento eliminado.');
    }

    private function storeAttachments(Request $request): array
    {
        if (!$request->hasFile('attachments')) {
            return [];
        }

        return collect($request->file('attachments'))->map(function ($file) {
            $path = $file->store('events/attachments', 'public');
            $this->mirrorToPublicStorage($path);

            return [
                'name' => $file->getClientOriginalName(),
                'url' => '/storage/' . $path,
                'path' => $path,
                'mime' => $file->getClientMimeType(),
                'size' => $file->getSize(),
            ];
        })->values()->all();
    }

    private function mergeAttachments(Event $event, array $removePaths, array $newAttachments): array
    {
        $removePaths = array_filter($removePaths);

        $kept = collect($event->attachments ?? [])
            ->reject(function ($attachment) use ($removePaths) {
                $path = $attachment['path'] ?? null;

                if ($path && in_array($path, $removePaths, true)) {
                    Storage::disk('public')->delete($path);
                    $this->deletePublicStorageMirror($path);
                    return true;
                }

                return false;
            })
            ->values()
            ->all();

        return array_values(array_merge($kept, $newAttachments));
    }

    private function mirrorToPublicStorage(string $path): void
    {
        $source = Storage::disk('public')->path($path);
        $target = public_path('storage/' . $path);

        if (!is_file($source)) {
            return;
        }

        File::ensureDirectoryExists(dirname($target));
        File::copy($source, $target);
    }

    private function deletePublicStorageMirror(string $path): void
    {
        $target = public_path('storage/' . ltrim($path, '/'));

        if (is_file($target)) {
            File::delete($target);
        }
    }
}
