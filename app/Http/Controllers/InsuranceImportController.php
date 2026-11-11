<?php

namespace App\Http\Controllers;

use App\Imports\InsurancesImport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

final class InsuranceImportController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls'],
        ]);

        $import = app(InsurancesImport::class);

        Excel::import($import, $request->file('file'));

        return to_route('insurances.index')->with('status', $import->summary());
    }
}
