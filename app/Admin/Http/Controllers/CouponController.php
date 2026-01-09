<?php

namespace App\Admin\Http\Controllers;

use App\Share\Enums\DiscountType;
use App\Share\Http\Controllers\Controller as BaseController;
use App\Share\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CouponController extends BaseController
{
    public function index(Request $request)
    {
        $query = Coupon::query()->with(['creator', 'updater']);

        if ($keyword = $request->input('q')) {
            $query->where('code', 'like', "%{$keyword}%");
        }

        $coupons = $query->latest()->orderByDesc('id')->paginate(10);

        return view('admin.coupons.index', compact('coupons'));
    }

    public function create()
    {
        return view('admin.coupons.create', [
            'discountTypes' => DiscountType::class,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:255|unique:coupons,code',
            'start_at' => 'nullable|date',
            'end_at' => 'nullable|date|after_or_equal:start_at',
            'total_quantity' => 'required|integer|min:0',
            'discount_type' => 'required|in:'.DiscountType::Percentage.','.DiscountType::FixedAmount,
            'discount_value' => 'required|numeric|min:0',
        ], [
            'code.required' => 'Mã coupon không được để trống.',
            'code.unique' => 'Mã coupon đã tồn tại.',
            'end_at.after_or_equal' => 'Ngày kết thúc phải sau hoặc bằng ngày bắt đầu.',
            'total_quantity.required' => 'Tổng số lượng không được để trống.',
            'total_quantity.min' => 'Tổng số lượng phải lớn hơn hoặc bằng 0.',
            'discount_type.required' => 'Loại giảm giá không được để trống.',
            'discount_value.required' => 'Giá trị giảm giá không được để trống.',
            'discount_value.min' => 'Giá trị giảm giá phải lớn hơn hoặc bằng 0.',
        ]);

        // Validate discount_value based on discount_type
        if ($validated['discount_type'] === DiscountType::Percentage) {
            $request->validate([
                'discount_value' => 'max:100',
            ], [
                'discount_value.max' => 'Giá trị giảm giá phần trăm không được vượt quá 100.',
            ]);
        }

        Coupon::query()->create([
            'code' => $validated['code'],
            'start_at' => $validated['start_at'] ?? null,
            'end_at' => $validated['end_at'] ?? null,
            'total_quantity' => $validated['total_quantity'],
            'used_quantity' => 0,
            'discount_type' => $validated['discount_type'],
            'discount_value' => $validated['discount_value'],
            'creator_id' => Auth::guard('admin')->id(),
        ]);

        return redirect()->route('admin.coupons.index')->with('success', 'Tạo coupon thành công.');
    }

    public function edit(Coupon $coupon)
    {
        return view('admin.coupons.edit', compact('coupon'));
    }

    public function update(Request $request, Coupon $coupon)
    {
        // Chỉ cho phép sửa start_at và end_at
        $validated = $request->validate([
            'start_at' => 'nullable|date',
            'end_at' => 'nullable|date|after_or_equal:start_at',
        ], [
            'end_at.after_or_equal' => 'Ngày kết thúc phải sau hoặc bằng ngày bắt đầu.',
        ]);

        $coupon->update([
            'start_at' => $validated['start_at'] ?? null,
            'end_at' => $validated['end_at'] ?? null,
            'updater_id' => Auth::guard('admin')->id(),
        ]);

        return redirect()->route('admin.coupons.index')->with('success', 'Cập nhật coupon thành công.');
    }

    public function destroy(Coupon $coupon)
    {
        $coupon->delete();

        return redirect()->route('admin.coupons.index')->with('success', 'Xóa coupon thành công.');
    }
}
