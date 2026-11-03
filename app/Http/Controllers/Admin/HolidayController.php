<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class HolidayController extends Controller
{
    public function index()
    {
        $holidays = Holiday::orderBy('holiday_date', 'asc')->paginate(20);

        return view('admin.holidays.index', compact('holidays'));
    }

    public function create()
    {
        return view('admin.holidays.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'holiday_date' => 'required|date|unique:holidays,holiday_date',
            'name' => 'required|string|max:255',
        ]);

        Holiday::create([
            'holiday_date' => $request->holiday_date,
            'name' => $request->name,
            'is_national' => false, // manual addition means company holiday usually
        ]);

        return redirect()->route('admin.holidays.index')->with('success', 'Hari libur berhasil ditambahkan.');
    }

    public function destroy($id)
    {
        $holiday = Holiday::findOrFail($id);
        $holiday->delete();

        return redirect()->route('admin.holidays.index')->with('success', 'Hari libur berhasil dihapus.');
    }

    public function sync(Request $request)
    {
        $year = $request->year ?? date('Y');
        Artisan::call("sync:holidays {$year}");

        return redirect()->route('admin.holidays.index')->with('success', "Sinkronisasi kalender libur $year berhasil dilakukan.");
    }
}
