<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\RefundRequest;
use App\Models\Transaksi;
use App\Models\User;
use App\Notifications\AdminRefundRequestedManual;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class RefundController extends Controller
{
    public function request(Request $request, $transaksiId)
    {
        /** @var int|null $userId */
        $userId = Auth::id();

        abort_if(! $userId, 403);

        return DB::transaction(function () use ($request, $transaksiId, $userId) {
            $transaksi = Transaksi::with(['latestRefund', 'pembayaran'])
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->findOrFail($transaksiId);

            if ($transaksi->status_transaksi !== 'selesai') {
                return back()->with('flash', [
                    'type' => 'warning',
                    'action' => 'create',
                    'entity' => 'Refund',
                    'message' => 'Refund hanya bisa diajukan setelah pesanan selesai.',
                ]);
            }

            $refund = $transaksi->latestRefund;
            if ($refund && $refund->status !== RefundRequest::STATUS_FAILED) {
                return back()->with('flash', [
                    'type' => 'warning',
                    'action' => 'create',
                    'entity' => 'Refund',
                    'message' => 'Refund sudah diajukan. Silakan menunggu proses.',
                ]);
            }

            $rules = [
                'reason' => 'required|string|max:1000',
                'proof' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
                'bank_name' => 'required|string|max:100',
                'account_number' => 'required|string|max:50',
                'account_name' => 'required|string|max:100',
            ];

            $data = $request->validate($rules);

            $proofPath = null;
            if ($request->hasFile('proof')) {
                $proofPath = $request->file('proof')->store('refund_proofs', 'public');
            }

            $newRefund = RefundRequest::create([
                'user_id' => $userId,
                'transaksi_id' => $transaksi->id,
                'method' => RefundRequest::METHOD_MANUAL,
                'status' => RefundRequest::STATUS_REQUESTED,
                'amount' => (int) ($transaksi->total_pembayaran ?? 0),
                'reason' => $data['reason'],
                'bank_name' => $data['bank_name'] ?? null,
                'account_number' => $data['account_number'] ?? null,
                'account_name' => $data['account_name'] ?? null,
                'proof_path' => $proofPath,
            ]);

            $this->notifyAdmins($newRefund, $transaksi);

            return back()->with('flash', [
                'type' => 'success',
                'action' => 'create',
                'entity' => 'Refund',
                'message' => 'Pengajuan refund berhasil dikirim. Menunggu proses admin.',
            ]);
        });
    }

    private function notifyAdmins(RefundRequest $refund, Transaksi $trx): void
    {
        $admins = User::where('user_role', 'admin')->get();

        if ($admins->isEmpty()) {
            return;
        }

        Notification::send($admins, new AdminRefundRequestedManual($refund, $trx));
    }
}
