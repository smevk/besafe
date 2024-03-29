<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\ApiResponseController;
use App\Http\Resources\HazardCollection;
use App\Models\Hazard;
use App\Models\MetaDepartment;
use App\Models\MetaDepartmentTag;
use App\Models\MetaIncidentStatus;
use App\Models\MetaLine;
use App\Models\MetaLocation;
use App\Models\MetaRiskLevel;
use App\Models\MetaUnit;
use App\Rules\MetaLocationValidate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class HazardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, $channel = "web")
    {
        RolesPermissionController::can(['hazard.index']);
        $hazards = IncidentAssignController::getAssignedIncidents(Hazard::class, 'hazard');
        if ($channel === 'api') {
            return $hazards;
        }

        if ($request->ajax()) {
            $data = [];
            $i = 0;
            foreach ($hazards->get() as $hazard) {
                $i++;
                $data[] = [
                    'sno' => $i,
                    'unit' => $hazard->unit->unit_title,
                    'date' => formatDate($hazard->date),
                    'department' => $hazard->department->department_title,
                    'line' => $hazard->line,
                    'risk_level' => $hazard->risk_level->risk_level_title,
                    'incident_status' => $hazard->incident_status->status_title,
                    'location' => $hazard->meta_location ? $hazard->meta_location->location_title : '',
                    // 'department_tag' => $hazard->department_tag->department_tag_title,
                    'action' => view('hazard.partials.action-buttons', ['hazard' => $hazard])->render()
                ];
            }

            return DataTables::of($data)->toJson();
        }
        return view('hazard.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        RolesPermissionController::can(['hazard.create']);
        $departments = MetaDepartment::select('id', 'department_title')->get();
        $lines = MetaLine::select('id', 'line_title')->get();
        $incident_statuses = MetaIncidentStatus::select('status_code', 'status_title', 'id')->get();
        $risk_levels = MetaRiskLevel::select('id', 'risk_level_title')->get();
        $department_tags = MetaDepartmentTag::select('id', 'department_tag_title')->get();
        $units = MetaUnit::select('id', 'unit_title')->get();
        $locations = MetaLocation::select('id', 'meta_unit_id', 'location_title')->get();
        return view('hazard.create', compact('lines', 'departments', 'units', 'incident_statuses', 'risk_levels', 'department_tags', 'locations'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, $channel = 'web')
    {
        RolesPermissionController::can(['hazard.create']);


        $validator = $this->validateData($request);

        $formErrorsResponse = FormValidatitionDispatcherController::Response($validator, $channel);
        if ($formErrorsResponse) {
            return $formErrorsResponse;
        }

        $hazard = new Hazard();

        $hazard->meta_unit_id = $request->meta_unit_id ?? null;
        $hazard->meta_department_id = $request->meta_department_id ?? null;
        $hazard->meta_risk_level_id = $request->meta_risk_level_id ?? null;
        $hazard->meta_department_tag_id = $request->meta_department_tag_id ?? null;
        // $hazard->meta_line_id = $request->meta_line_id ?? null;
        $hazard->meta_incident_status_id = MetaIncidentStatus::where('status_code', 0)->first()->id; //pending
        $hazard->initiated_by = auth()->user()->id;
        $hazard->meta_location_id = $request->meta_location_id;
        $hazard->description = $request->description;
        $hazard->other_location = $request->other_location;
        $hazard->date = $request->date;
        $hazard->action_cost = $request->action_cost;
        $hazard->action = $request->action;
        $hazard->line = $request->line;
        $hazard->save();
        if ($request->has('initial_attachements')) {
            // (new CommonAttachementController)->uploadedArray($request->attachements, $hazard, 'attachements');
            (new CommonAttachementController)->syncUploadedArray($request->initial_attachements, $hazard, 'initial_attachements');
        }

        if ($channel === 'api') {
            return ApiResponseController::successWithData('Hazard Incident created.', new HazardCollection($hazard));
        }

        return ['success', 'Hazard has been created', $request->redirect];

    }

    /**
     * Display the specified resource.
     */
    public function show($hazard_id, $channel = "web")
    {
        $hazard = Hazard::where('id', $hazard_id);
        RolesPermissionController::canViewIncident($hazard->first(), 'hazard');
        if ($channel === 'api') {
            return $hazard->first();
        }
        $hazard = $hazard->firstOrFail();
        return view('hazard.show', compact('hazard'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Hazard $hazard)
    {
        RolesPermissionController::can(['hazard.create']);
        $units = MetaUnit::select('id', 'unit_title')->get();
        $departments = MetaDepartment::select('id', 'department_title')->get();
        $lines = MetaLine::select('id', 'line_title')->get();
        $incident_statuses = MetaIncidentStatus::select('status_code', 'status_title', 'id')->whereNot('status_code', 0)->get();
        $risk_levels = MetaRiskLevel::select('id', 'risk_level_title')->get();
        $department_tags = MetaDepartmentTag::select('id', 'department_tag_title')->get();
        $locations = MetaLocation::select('id', 'meta_unit_id', 'location_title')->get();
        return view('hazard.edit', compact('lines', 'departments', 'units', 'incident_statuses', 'risk_levels', 'department_tags', 'hazard', 'locations'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $hazard_id, $channel = "web")
    {


        $validator = $this->validateData($request, 'update');

        $formErrorsResponse = FormValidatitionDispatcherController::Response($validator, $channel);
        if ($formErrorsResponse) {
            return $formErrorsResponse;
        }

        $hazard = Hazard::where('id', $hazard_id)->first();

        // if allowed to update
        RolesPermissionController::canEditIncident($hazard, 'hazard');

        if (!$hazard && $channel === 'api') {
            return ApiResponseController::error('Hazard not found', 404);
        }




        $hazard->meta_unit_id = $request->meta_unit_id ?? $hazard->meta_unit_id;
        $hazard->meta_department_id = $request->meta_department_id ?? $hazard->meta_department_id;
        $hazard->meta_risk_level_id = $request->meta_risk_level_id ?? $hazard->meta_risk_level_id;
        $hazard->meta_department_tag_id = $request->meta_department_tag_id ?? $hazard->meta_department_tag_id;
        // $hazard->meta_line_id = $request->meta_line_id ?? $hazard->meta_line_id;
        $hazard->meta_incident_status_id = $request->meta_incident_status_id ?? $hazard->meta_incident_status_id;
        $hazard->meta_location_id = $request->meta_location_id ?? $hazard->meta_location_id;
        $hazard->description = $request->description ?? $hazard->description;
        $hazard->other_location = $request->other_location ?? $hazard->other_location;
        $hazard->date = $request->date ?? $hazard->date;
        $hazard->action_cost = $request->action_cost ?? $hazard->action_cost;
        // $hazard->others = $request->others;
        $hazard->action = $request->action ?? $hazard->action;
        $hazard->line = $request->line ?? $hazard->line;
        $hazard->save();
        if ($request->has('attachements')) {
            // (new CommonAttachementController)->uploadedArray($request->attachements, $hazard, 'attachements');
            (new CommonAttachementController)->syncUploadedArray($request->attachements, $hazard, 'attachements');
        }
        if ($request->has('initial_attachements')) {
            // (new CommonAttachementController)->uploadedArray($request->attachements, $hazard, 'attachements');
            (new CommonAttachementController)->syncUploadedArray($request->initial_attachements, $hazard, 'initial_attachements');
        }

        if ($channel === 'api') {
            return ApiResponseController::successWithData('Hazard Incident updated.', new HazardCollection($hazard));
        }

        return ['success', 'Hazard has been updated', $request->redirect];

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($hazard_id, $channel = 'web')
    {
        RolesPermissionController::can(['hazard.delete']);

        $hazard = Hazard::find($hazard_id);
        if (!$hazard && $channel === "api") {
            return ApiResponseController::error('hazard not found', 404);
        }

        if (!$hazard) {
            return ['error', 'hazard not found'];
        }

        // deleting the hazard
        if ($hazard->delete()) {
            if ($channel === 'api') {
                return ApiResponseController::success('hazard has been delete');
            } else {
                return ['deleted', 'Hazard has been deleted'];
            }
        } else {
            if ($channel === 'api') {
                return ApiResponseController::error('Could not delete the hazard.');
            } else {
                return ['error', 'Could not delete the hazard'];
            }
        }
    }

    public function assign(Request $request, $hazard_id, $channel = 'web')
    {
        $hazard = Hazard::where('id', $hazard_id)->first();
        if ($hazard) {
            return (new IncidentAssignController)->store($request, $hazard, $channel);
        } else {
            return ApiResponseController::error('Hazard not found.', 404);
        }
    }

    public function validateData(Request $request, $method = "store")
    {
        $rules = [
            'meta_unit_id' => 'required|exists:meta_units,id',
            'meta_location_id' => ['exists:meta_locations,id', new MetaLocationValidate],
            'meta_department_id' => 'required|exists:meta_departments,id',
            // 'meta_line_id' => 'required|exists:meta_lines,id',
            'meta_risk_level_id' => 'required|exists:meta_risk_levels,id',
            'meta_department_tag_id' => 'required|exists:meta_department_tags,id',
            // 'location' => 'nullable|string|max:255',
            'other_location' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:255',
            'line' => 'nullable|string|max:255',
            'action' => 'nullable|string|max:255',
            'date' => 'required|date',
            'action_cost' => 'nullable|numeric|min:0|max:9999999.99',
            'attachements' => ['array', 'nullable'],
            'attachements.*' => ['mimes:jpeg,png,jpg,gif,doc,docx,pdf,xlsx,csv|max:2048'],
            'initial_attachements' => ['array', 'nullable'],
            'initial_attachements.*' => ['mimes:jpeg,png,jpg,gif,doc,docx,pdf,xlsx,csv|max:2048'],
        ];

        if ($method == 'update') {
            $rules['meta_unit_id'] = 'nullable|exists:meta_units,id';
            $rules['meta_incident_status_id'] = 'required|exists:meta_incident_statuses,id';
        }
        return Validator::make($request->all(), $rules);
    }
}