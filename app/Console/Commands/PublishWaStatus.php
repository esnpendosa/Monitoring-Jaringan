<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WaStatusSchedule;
use App\Services\WhatsappClient;
use Illuminate\Support\Facades\Log;

class PublishWaStatus extends Command
{
    protected $signature = 'status:publish';
    protected $description = 'Publish scheduled WhatsApp Status updates';

    public function handle()
    {
         $this->info("Checking for scheduled WhatsApp Status updates...");
         
         $schedules = WaStatusSchedule::where('status', 'pending')
             ->where('scheduled_at', '<=', now())
             ->get();
 
         if ($schedules->isEmpty()) {
             $this->info("No pending schedules found.");
             return 0;
         }
 
         $waClient = new WhatsappClient();
 
         foreach ($schedules as $schedule) {
             $this->info("Publishing Status ID: {$schedule->id} scheduled at {$schedule->scheduled_at}");
             
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
                     $this->info("Successfully published Status ID: {$schedule->id}");
                 } else {
                     throw new \Exception("Failed to send status update via WhatsappClient.");
                 }
             } catch (\Exception $e) {
                 Log::error("Failed to publish WA Status ID {$schedule->id}: " . $e->getMessage());
                 $schedule->update([
                     'status' => 'failed',
                     'error_message' => $e->getMessage()
                 ]);
                 $this->error("Failed to publish Status ID: {$schedule->id}. Error: " . $e->getMessage());
             }
         }
 
         return 0;
     }
}
