<?php

namespace App\Http\Controllers;

use App\Http\Resources\IncidentCollection;
use App\Models\FirePropertyDamage;
use App\Models\Hazard;
use App\Models\Injury;
use App\Models\NearMiss;
use App\Models\UnsafeBehavior;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        $commonColumns = ['id', 'initiated_by', 'created_at', 'updated_at', 'meta_incident_status_id'];
        $hazards = Hazard::select($commonColumns)
            ->selectRaw("'hazards' AS incident_name");

        $nearMisses = NearMiss::select($commonColumns)
            ->selectRaw("'near_misses' AS incident_name");

        $unsafe_behaviors = UnsafeBehavior::select($commonColumns)
            ->selectRaw("'unsafe_behaviors' AS incident_name");

        $injuries = Injury::select($commonColumns)
            ->selectRaw("'injuries' AS incident_name");

        $fpdemages = FirePropertyDamage::select($commonColumns)
            ->selectRaw("'fpdamages' AS incident_name");

        $results = $hazards->unionAll($nearMisses)
            ->unionAll($unsafe_behaviors)
            ->unionAll($injuries)
            ->unionAll($fpdemages)
            ->orderBy('created_at', 'desc');



        $limit = 20;
        if ($request->has('limit')) {
            $limit = $request->limit;
        }
        if ($request->has('from_date') and $request->has('to_date')) {
            $results = $results->whereBetween('created_at', [$request->from_date, $request->to_date]);
        }

        if ($request->has('meta_incident_status_id')) {
            $results = $results->where('meta_incident_status_id', $request->meta_incident_status_id);
        }
        if ($request->has('initiated_by')) {
            $results = $results->where('initiated_by', $request->initiated_by);
        }

        $results = $results->paginate($limit);



        return IncidentCollection::collection($results);

        // return $resultsWithTable
        return $resultsWithTable;
        return view('dashboard');

    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}