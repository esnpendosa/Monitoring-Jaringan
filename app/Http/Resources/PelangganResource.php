<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PelangganResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_pelanggan' => $this->id_pelanggan,
            'id_user' => $this->id_user,
            'id_router' => $this->id_router,
            'kode_pelanggan' => $this->kode_pelanggan,
            'nama_pelanggan' => $this->nama_pelanggan,
            'email' => $this->email,
            'no_wa' => $this->no_wa,
            'mikrotik_username' => $this->mikrotik_username,
            'mikrotik_type' => $this->mikrotik_type,
            'alamat' => $this->alamat,
            'latitude' => $this->latitude ? (float) $this->latitude : null,
            'longitude' => $this->longitude ? (float) $this->longitude : null,
            'usage_gb' => $this->usage_gb,
            'jumlah_device' => $this->jumlah_device,
            'paket' => $this->paket,
            'harga_layanan' => (int) $this->harga_layanan,
            'is_active' => (bool) $this->is_active,
            'wa_active' => (bool) $this->wa_active,
            'prioritas_label' => $this->prioritas_label,
            'ip_address' => $this->ip_address,
            'last_online_status' => $this->last_online_status,
            'last_ping_at' => $this->last_ping_at,
            'billing_date' => $this->billing_date,
            'foto_rumah' => $this->foto_rumah ? asset('storage/' . $this->foto_rumah) : null,
            'tanggal_pasang' => $this->tanggal_pasang,
            'gratis_pemasangan' => (bool) $this->gratis_pemasangan,
            'wifi_profile' => $this->wifi_profile,
            'router' => $this->whenLoaded('router'),
            'user' => $this->whenLoaded('user'),
        ];
    }
}
