<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WaStatusSchedule;
use App\Services\WhatsappClient;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class WaStatusScheduleController extends Controller
{
    public function index()
    {
        $schedules = WaStatusSchedule::orderBy('scheduled_at', 'desc')->paginate(10);
        return view('content.whatsapp.status', compact('schedules'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'content' => 'nullable|string',
            'scheduled_at' => 'required|date',
        ]);

        if (empty($request->content) && !$request->hasFile('media')) {
            return back()->withErrors(['content' => 'Teks status atau file gambar harus diisi salah satu!']);
        }

        $mediaPath = null;
        if ($request->hasFile('media')) {
            $file = $request->file('media');
            $extension = strtolower($file->getClientOriginalExtension());
            $allowedExtensions = ['jpeg', 'png', 'jpg', 'gif'];
            if (!in_array($extension, $allowedExtensions)) {
                return back()->withErrors(['media' => 'File media harus berupa gambar dengan format jpeg, png, jpg, atau gif!']);
            }
            if ($file->getSize() > 5 * 1024 * 1024) {
                return back()->withErrors(['media' => 'Ukuran file media maksimal 5MB!']);
            }
            // Move file manually to public storage to bypass Laravel's extension guesser (which requires php_fileinfo extension)
            $filename = time() . '_' . uniqid() . '.' . $extension;
            $targetDir = storage_path('app/public/status_media');
            if (!file_exists($targetDir)) {
                @mkdir($targetDir, 0755, true);
                @chmod($targetDir, 0755);
            }
            $file->move($targetDir, $filename);
            @chmod($targetDir . '/' . $filename, 0644);
            $mediaPath = 'status_media/' . $filename;
        }

        WaStatusSchedule::create([
            'content' => $request->content,
            'media' => $mediaPath,
            'scheduled_at' => $request->scheduled_at,
            'status' => 'pending'
        ]);

        return redirect()->route('whatsapp.status.index')->with('success', 'Jadwal status berhasil dibuat!');
    }

    public function destroy($id)
    {
        $schedule = WaStatusSchedule::findOrFail($id);
        
        if ($schedule->media) {
            $filePath = storage_path('app/public/' . $schedule->media);
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
        }

        $schedule->delete();

        return redirect()->route('whatsapp.status.index')->with('success', 'Jadwal status berhasil dihapus!');
    }

    public function publishImmediately($id)
    {
        $schedule = WaStatusSchedule::findOrFail($id);
        $waClient = new WhatsappClient();

        try {
            $mediaBase64 = null;
            $mimetype = 'image/jpeg';

            if ($schedule->media) {
                $filePath = storage_path('app/public/' . $schedule->media);
                if (file_exists($filePath)) {
                    $fileData = file_get_contents($filePath);
                    $mediaBase64 = base64_encode($fileData);
                    
                    // Guess mimetype based on extension to bypass php_fileinfo extension requirement
                    $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
                    $mimes = [
                        'jpg' => 'image/jpeg',
                        'jpeg' => 'image/jpeg',
                        'png' => 'image/png',
                        'gif' => 'image/gif',
                    ];
                    $mimetype = $mimes[$extension] ?? 'image/jpeg';
                } else {
                    throw new \Exception("Media file not found at: " . $filePath);
                }
            }

            // Passing empty array [] tells the bot to broadcast the status to ALL contacts in the phone's memory
            $success = $waClient->sendStatus($schedule->content, $mediaBase64, $mimetype, $schedule->content, []);

            if ($success) {
                $schedule->update([
                    'status' => 'posted',
                    'error_message' => null
                ]);
                return redirect()->route('whatsapp.status.index')->with('success', 'Status WhatsApp berhasil terupload seketika!');
            } else {
                throw new \Exception("Gagal mengirim status WhatsApp melalui Bot Client.");
            }
        } catch (\Exception $e) {
            Log::error("Failed manual publish for WA Status {$schedule->id}: " . $e->getMessage());
            $schedule->update([
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ]);
            return redirect()->route('whatsapp.status.index')->with('error', 'Gagal mempublikasikan status: ' . $e->getMessage());
        }
    }
}
