<?php

use Illuminate\Support\Facades\Route;
use App\Models\Inspection;
use Illuminate\Http\Request;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/admin/inspections', function (Request $request) {
        $query = Inspection::query();
        if ($request->filled('inspector_id')) {
            $query->where('inspector_id', $request->inspector_id);
        }
        if ($request->filled('from')) {
            $query->whereDate('inspection_date', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('inspection_date', '<=', $request->to);
        }
        if ($request->filled('vessel')) {
            $query->where('landing_site_name', 'like', '%' . $request->vessel . '%');
        }
        $inspections = $query->orderByDesc('updated_at')->paginate(25)->withQueryString();
        return view('admin.inspections.index', ['inspections' => $inspections]);
    });

    Route::get('/admin/inspections/{inspection}', function (Inspection $inspection) {
        return view('admin.inspections.show', ['inspection' => $inspection]);
    });
});
