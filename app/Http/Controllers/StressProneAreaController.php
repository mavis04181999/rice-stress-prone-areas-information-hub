<?php

namespace App\Http\Controllers;

use App\Http\Requests\StressProneAreaRequest;
use App\Http\Requests\UpdateStressProneAreaRequest;
use App\Models\Barangay;
use App\Models\City;
use App\Models\Municipality;
use App\Models\Province;
use App\Models\StressProneArea;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StressProneAreaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StressProneAreaRequest $request)
    {
        
        if (isset($request->validated()['input_spa_city'])) 
        {
            $stressProneArea = StressProneArea::where('city_id', $request->validated()['input_spa_city'])->where('barangay_id', $request->validated()['input_spa_barangay'])->where('deleted_at', null)->first();
        } else 
        {
            $stressProneArea = StressProneArea::where('municipality_id', $request->validated()['input_spa_municipality'])->where('barangay_id', $request->validated()['input_spa_barangay'])->get();
        }

        if (!empty($stressProneArea)) 
        {
            return redirect()->route('enumerator.form2')->with('info', 'Stress Data Already Exists <a class="ml-2 btn btn-blue inline-block px-6 py-2.5 bg-blue-600 text-black font-medium text-xs leading-normal uppercase rounded shadow-lg hover:bg-blue-700 hover:shadow-lg focus:bg-blue-700 focus:shadow-lg focus:outline-none focus:ring-0 active:bg-blue-800 active:shadow-lg transition duration-150 ease-in-out" href="'.route('stresspronearea.province',  ['province' => $stressProneArea->province_id]).'">View Record</a>');
        }
        
        
        try 
        {
            DB::beginTransaction();

                $stressProneArea = StressProneArea::create([
                    'province_id' => isset($request->validated()['input_spa_province']) ? $request->validated()['input_spa_province'] : null,
                    'city_id' => isset($request->validated()['input_spa_city']) ? $request->validated()['input_spa_city'] : null,
                    'municipality_id' => isset($request->validated()['input_spa_municipality']) ? $request->validated()['input_spa_municipality'] : null,
                    'barangay_id' => isset($request->validated()['input_spa_barangay']) ? $request->validated()['input_spa_barangay'] : null,
                    'stressEcosystem' => $request->validated()['input_spa_stressecosystem'],
                    'totalFarmers' => isset($request->validated()['input_spa_totalfarmers']) ? $request->validated()['input_spa_totalfarmers'] : null,
                    'totalStressArea' => isset($request->validated()['input_spa_totalstressarea']) ? $request->validated()['input_spa_totalstressarea'] : null,

                    'initdt' => Carbon::now(),
                    'upddt' => Carbon::now(),
                    'initid' => Auth::user()->id,
                    'updid' => Auth::user()->id,
                ]);

            DB::commit();

            return back()->with('success', "new Stress Prone Area Data Added Successfully!");

        } catch (\Exception $ex) 
        {
            DB::rollBack();
            throw $ex;
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\StressProneArea  $stressProneArea
     * @return \Illuminate\Http\Response
     */
    public function show(StressProneArea $stressProneArea)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\StressProneArea  $stressProneArea
     * @return \Illuminate\Http\Response
     */
    public function edit(StressProneArea $stressProneArea)
    {
        $stressProneArea = StressProneArea::where('stresspronearea.id', $stressProneArea->id)
                                ->leftjoin('provinces', 'stresspronearea.province_id', '=', 'provinces.id')
                                ->leftjoin('cities', 'stresspronearea.city_id', '=', 'cities.id')
                                ->leftjoin('municipalities', 'stresspronearea.municipality_id', '=', 'municipalities.id')
                                ->leftjoin('barangay', 'stresspronearea.barangay_id', '=', 'barangay.id')
                                ->select(
                                    'stresspronearea.*',
                                    'provinces.province as province',
                                    'municipalities.municipality as municipality',
                                    'cities.city as city',
                                    'barangay.barangay as barangay',
                                )
                                ->first();

        return view('stresspronearea.edit', compact('stressProneArea'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\StressProneArea  $stressProneArea
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateStressProneAreaRequest $request, StressProneArea $stressProneArea)
    {
        $stressProneArea->update([
            'totalFarmers' => $request->validated()['input_spa_totalfarmers'],
            'totalStressArea' => $request->validated()['input_spa_totalstressarea'],
            'stressEcosystem' => empty($request->validated()['input_spa_stressecosystem']) ? NULL : $request->validated()['input_spa_stressecosystem'],

            'upddt' => now(),
            'updid' => Auth::user()->id,

        ]);

        return back()->with('success', 'Stress Data Updated Successfully!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\StressProneArea  $stressProneArea
     * @return \Illuminate\Http\Response
     */
    public function destroy(StressProneArea $stressProneArea)
    {
        $stressProneArea->delete();

        return redirect()->back()->with('message', 'Stress Data was Deleted Successfully. <a class="btn btn-sm btn-warning" href="'.route('stresspronearea.restore',  ['stressProneArea' => $stressProneArea->id]).'">Whoops, Undo</a>');

    }

    public function restore(int $stressProneArea)
    {
        $stressProneArea = StressProneArea::withTrashed()->find($stressProneArea);

        if ($stressProneArea && $stressProneArea->trashed())
        {
            $stressProneArea->restore();
        }

        return redirect()->back()->with('success', 'Stress Data was restored successfully');
    }

    public function delete(int $stressProneArea)
    {
        $stressProneArea = StressProneArea::withTrashed()->find($stressProneArea);

        if ($stressProneArea && $stressProneArea->trashed())
        {
            $stressProneArea->forceDelete();
        }

        return back();
    }

    public function dashboard()
    {
        $totalFarmers = StressProneArea::sum('totalFarmers');
        $totalStressArea = StressProneArea::sum('totalStressArea');

        // dd($totalFarmers, $totalStressArea);

        $stressProneAreaDetails = StressProneArea::leftjoin('provinces', 'stresspronearea.province_id', '=', 'provinces.id')
            ->select(
                'stresspronearea.province_id',
                'provinces.province',
                'provinces.code',
                DB::raw('SUM(stresspronearea.totalFarmers) as totalFarmers'),
                DB::raw('SUM(stresspronearea.totalStressArea) as totalStressArea'),
            )->groupBy(
                'stresspronearea.province_id',
                'provinces.province',
                'provinces.code',
            )->get();

        // dd($stressProneAreaDetails);

        return view('stresspronearea.dashboard', compact('stressProneAreaDetails', 'totalFarmers', 'totalStressArea'));
    }

    public function province(Province $province)
    {

        $citiesSPADetails = StressProneArea::leftjoin('provinces', 'stresspronearea.province_id', '=', 'provinces.id')
                                    ->leftjoin('cities', 'stresspronearea.city_id', '=', 'cities.id')
                                    ->leftjoin('barangay', 'stresspronearea.barangay_id', '=', 'barangay.id')
                                    ->where('stresspronearea.province_id', $province->id)
                                    ->where('stresspronearea.municipality_id', null)
                                    ->select(
                                        'stresspronearea.*',
                                        'provinces.province as province',
                                        'provinces.code as provincecode',
                                        'cities.city as city',
                                        'cities.code as citycode',
                                        'barangay.barangay as barangay',
                                        'barangay.code as barangaycode',
                                    )
                                    ->get();
        
        $municipalitiesSPADetails = StressProneArea::leftjoin('provinces', 'stresspronearea.province_id', '=', 'provinces.id')
                                    ->leftjoin('municipalities', 'stresspronearea.municipality_id', '=', 'municipalities.id')
                                    ->leftjoin('barangay', 'stresspronearea.barangay_id', '=', 'barangay.id')
                                    ->where('stresspronearea.province_id', $province->id)
                                    ->where('stresspronearea.city_id', null)
                                    ->select(
                                        'stresspronearea.*',
                                        'provinces.province as province',
                                        'provinces.code as provincecode',
                                        'municipalities.municipality as municipality',
                                        'municipalities.code as municipalitycode',
                                        'barangay.barangay as barangay',
                                        'barangay.code as barangaycode',
                                    )
                                    ->get();

        return view('stresspronearea.province', compact('citiesSPADetails', 'municipalitiesSPADetails', 'province'));
    }

    public function archive()
    {
        $stressProneAreaDetails = StressProneArea::onlyTrashed()
                        ->leftjoin('provinces', 'stresspronearea.province_id', '=', 'provinces.id')
                        ->leftjoin('cities', 'stresspronearea.city_id', '=', 'cities.id')
                        ->leftjoin('municipalities', 'stresspronearea.municipality_id', '=', 'municipalities.id')
                        ->leftjoin('barangay', 'stresspronearea.barangay_id', '=', 'barangay.id')
                    ->select(
                        'stresspronearea.*',
                        'provinces.province',
                        'cities.city',
                        'municipalities.municipality',
                        'barangay.barangay',
                    )
                    ->get();

        return view('stresspronearea.archive', compact('stressProneAreaDetails'));
    }
}
