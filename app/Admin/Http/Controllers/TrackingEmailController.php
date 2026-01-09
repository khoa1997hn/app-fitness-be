<?php

namespace App\Admin\Http\Controllers;

use App\Share\Enums\FormType;
use App\Share\Http\Controllers\Controller as BaseController;
use App\Share\Models\TrackingEmail;
use Illuminate\Http\Request;
use League\Csv\Writer;

class TrackingEmailController extends BaseController
{
    public function index(Request $request)
    {
        $query = $this->applyFilters(TrackingEmail::query(), $request);

        $trackingEmails = $query->latest()->orderByDesc('id')->paginate(10);

        return view('admin.tracking-emails.index', [
            'trackingEmails' => $trackingEmails,
            'formTypes' => FormType::class,
        ]);
    }

    public function destroy(TrackingEmail $trackingEmail)
    {
        $trackingEmail->delete();

        return redirect()->route('admin.tracking-emails.index')->with('success', 'Xóa tracking email thành công.');
    }

    public function export(Request $request)
    {
        $query = $this->applyFilters(TrackingEmail::query(), $request);

        $csv = Writer::fromString();
        $csv->setEscape('');

        $query->select(['id', 'email'])
            ->orderBy('id')
            ->chunkById(200, function ($trackingEmails) use ($csv) {
                foreach ($trackingEmails as $trackingEmail) {
                    $csv->insertOne([$trackingEmail->email]);
                }
            });

        return $csv->download('emails.csv');
    }

    private function applyFilters($query, Request $request)
    {
        if ($keyword = $request->input('q')) {
            $query->where(function ($q) use ($keyword) {
                $q->where('email', 'like', "%{$keyword}%")
                    ->orWhereRaw('JSON_EXTRACT(data, "$") LIKE ?', ["%{$keyword}%"]);
            });
        }

        if ($formType = $request->input('form_type')) {
            $query->where('form_type', $formType);
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
