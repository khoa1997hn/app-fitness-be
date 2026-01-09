<?php

namespace App\Admin\Http\Controllers;

use App\Share\Http\Controllers\Controller as BaseController;
use App\Share\Models\User;
use Illuminate\Http\Request;
use League\Csv\Writer;

class UserController extends BaseController
{
    public function index(Request $request)
    {
        $query = $this->applyFilters(User::query(), $request);

        $users = $query->latest()->orderByDesc('id')->paginate(10);

        return view('admin.users.index', compact('users'));
    }

    public function destroy(User $user)
    {
        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'Xóa khách hàng thành công.');
    }

    public function export(Request $request)
    {
        $query = $this->applyFilters(User::query(), $request);

        $csv = Writer::fromString();
        $csv->setEscape('');

        $query->select(['id', 'email', 'name', 'phone', 'referral_email', 'created_at'])
            ->orderBy('id')
            ->chunkById(200, function ($users) use ($csv) {
                foreach ($users as $user) {
                    $csv->insertOne([
                        $user->email,
                        $user->name,
                        $user->phone ?? '',
                        $user->referral_email ?? '',
                        $user->created_at->format('Y-m-d H:i:s'),
                    ]);
                }
            });

        return $csv->download('users.csv');
    }

    private function applyFilters($query, Request $request)
    {
        if ($keyword = $request->input('q')) {
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%")
                    ->orWhere('phone', 'like', "%{$keyword}%");
            });
        }

        if ($startDate = $request->input('start_date')) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate = $request->input('end_date')) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        return $query;
    }
}
