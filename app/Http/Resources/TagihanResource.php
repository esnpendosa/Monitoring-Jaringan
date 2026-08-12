<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TagihanResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_tagihan' => $this->id_tagihan,
            'id_pelanggan' => $this->id_pelanggan,
            'bulan' => (int) $this->bulan,
            'tahun' => (int) $this->tahun,
            'jumlah' => (int) $this->jumlah,
            'status' => $this->status,
            'metode_pembayaran' => $this->metode_pembayaran,
            'bukti_bayar' => $this->bukti_bayar ? asset('storage/' . $this->bukti_bayar) : null,
            'catatan_admin' => $this->catatan_admin,
            'snap_token' => $this->snap_token,
            'payment_url' => $this->payment_url,
            'paid_at' => $this->paid_at ? $this->paid_at->toIso8601String() : null,
            'bayar_di_awal' => (bool) $this->bayar_di_awal,
            'created_at' => $this->created_at ? $this->created_at->toIso8601String() : null,
            'updated_at' => $this->updated_at ? $this->updated_at->toIso8601String() : null,
            'pelanggan' => new PelangganResource($this->whenLoaded('pelanggan')),
        ];
    }
}
