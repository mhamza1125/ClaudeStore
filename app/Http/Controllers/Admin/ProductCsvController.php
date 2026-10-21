<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ProductCsv;
use Illuminate\Http\Request;

class ProductCsvController extends Controller
{
    public function index()
    {
        return view('admin.csv', ['columns' => ProductCsv::COLUMNS]);
    }

    public function export(ProductCsv $csv)
    {
        return response()->streamDownload(function () use ($csv) {
            $out = fopen('php://output', 'w');
            $csv->export($out);
            fclose($out);
        }, 'products-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function import(Request $request, ProductCsv $csv)
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:5120']]);
        $dryRun = $request->boolean('dry_run');
        $result = $csv->import($request->file('file')->getRealPath(), $dryRun);

        if ($result['errors']) {
            return back()->with('import_errors', $result['errors'])->with('error', 'Import failed — no changes were saved.');
        }

        return back()->with('status', ($dryRun ? 'Dry run OK: would create ' : 'Imported: created ')."{$result['created']}, ".($dryRun ? 'update ' : 'updated ')."{$result['updated']} product(s).");
    }
}
