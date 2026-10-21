<?php

namespace App\Http\Controllers;

use App\Models\DeletionLog;
use App\Models\StockAdjustment;
use Illuminate\Http\Request;

class DeletionLogController extends Controller
{
    /** Riwayat penghapusan data (khusus owner) — audit log nota, penerimaan agen, dan pelanggan. */
    public function index(Request $request)
    {
        $query = DeletionLog::with('user')->latest();

        if ($request->filled('type') && in_array($request->type, ['Transaction', 'AgentDelivery', 'Customer', 'Product', 'User'], true)) {
            $query->where('deletable_type', $request->type);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('summary', 'like', "%{$search}%")
                  ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%"));
            });
        }

        $logs = $query->paginate(20)->withQueryString();
        return view('deletion-logs.index', ['logs' => $logs, 'tab' => 'deletion']);
    }

    /** Riwayat penyesuaian stok (khusus owner) — siapa mengubah stok apa, dari mana ke mana, dan alasannya. */
    public function adjustments(Request $request)
    {
        $query = StockAdjustment::with(['product', 'user'])->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reason', 'like', "%{$search}%")
                  ->orWhereHas('product', fn ($p) => $p->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%"));
            });
        }

        $adjustments = $query->paginate(20)->withQueryString();
        return view('deletion-logs.index', ['adjustments' => $adjustments, 'tab' => 'adjustment']);
    }
}
