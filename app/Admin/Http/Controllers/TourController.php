<?php

namespace App\Admin\Http\Controllers;

use App\Share\Http\Controllers\Controller as BaseController;
use App\Share\Models\Tour;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TourController extends BaseController
{
    public function index(Request $request)
    {
        $query = Tour::query()->with(['creator', 'updater']);

        if ($keyword = $request->input('q')) {
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('link', 'like', "%{$keyword}%");
            });
        }

        $tours = $query->latest()->orderByDesc('id')->paginate(10);

        return view('admin.tours.index', compact('tours'));
    }

    public function create()
    {
        return view('admin.tours.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'link' => 'required|string|max:500',
        ], [
            'name.required' => 'Tên tour không được để trống.',
            'name.max' => 'Tên tour không được vượt quá 255 ký tự.',
            'link.required' => 'Link không được để trống.',
            'link.max' => 'Link không được vượt quá 500 ký tự.',
        ]);

        Tour::query()->create([
            'name' => $validated['name'],
            'link' => $validated['link'],
            'creator_id' => Auth::guard('admin')->id(),
        ]);

        return redirect()->route('admin.tours.index')->with('success', 'Tạo tour thành công.');
    }

    public function edit(Tour $tour)
    {
        return view('admin.tours.edit', compact('tour'));
    }

    public function update(Request $request, Tour $tour)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'link' => 'required|string|max:500',
        ], [
            'name.required' => 'Tên tour không được để trống.',
            'name.max' => 'Tên tour không được vượt quá 255 ký tự.',
            'link.required' => 'Link không được để trống.',
            'link.max' => 'Link không được vượt quá 500 ký tự.',
        ]);

        $tour->update([
            'name' => $validated['name'],
            'link' => $validated['link'],
            'updater_id' => Auth::guard('admin')->id(),
        ]);

        return redirect()->route('admin.tours.index')->with('success', 'Cập nhật tour thành công.');
    }

    public function destroy(Tour $tour)
    {
        $tour->delete();

        return redirect()->route('admin.tours.index')->with('success', 'Xóa tour thành công.');
    }
}
