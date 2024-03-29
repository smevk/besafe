<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\ApiResponseController;
use App\Http\Resources\IEAuditClauseCollection;
use App\Models\InternalExternalAuditClause;
use App\Models\MetaAuditHall;
use App\Models\MetaAuditType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class InternalExternalAuditClauseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, $channel = "web")
    {
        RolesPermissionController::can(['ie_audit_cluase.index']);
        $ie_audit = InternalExternalAuditClause::where('initiated_by', auth()->user()->id);
        if (auth()->user()->can(['ie_audit_cluase.index', 'ie_audit_cluase.delete', 'ie_audit_cluase.edit'])) {
            $ie_audit = InternalExternalAuditClause::orderby('id', 'desc');
        }

        if ($channel == "api") {
            return $ie_audit;
        }

        if ($request->ajax()) {
            $data = [];
            $i = 0;
            foreach ($ie_audit->get() as $ie_audit) {
                $i++;
                $data[] = [
                    'sno' => $i,
                    'audit_hall' => $ie_audit->audit_hall->hall_title,
                    'audit_type' => $ie_audit->audit_type->audit_title,
                    'audit_date' => formatDate($ie_audit->audit_date),
                    'audit_score' => $ie_audit->audit_score . "%",
                    'action' => view('ie_audits.partials.action-buttons', ['ie_audit' => $ie_audit])->render()
                ];
            }

            return DataTables::of($data)->toJson();
        }

        return view('ie_audits.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        RolesPermissionController::can(['ie_audit_cluase.create']);
        $audit_types = MetaAuditType::select('id', 'audit_title')->get();
        $audit_halls = MetaAuditHall::select('id', 'hall_title')->get();
        return view('ie_audits.create', compact('audit_types', 'audit_halls'));

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, $channel = "web")
    {
        RolesPermissionController::can(['ie_audit_cluase.create']);

        $validator = $this->validateData($request);

        $formErrorsResponse = FormValidatitionDispatcherController::Response($validator, $channel);
        if ($formErrorsResponse) {
            return $formErrorsResponse;
        }
        $ie_audit = new InternalExternalAuditClause();
        $ie_audit->initiated_by = auth()->user()->id;
        $ie_audit->meta_audit_hall_id = $request->meta_audit_hall_id;
        $ie_audit->meta_audit_type_id = $request->meta_audit_type_id;
        $ie_audit->audit_date = $request->audit_date;
        $ie_audit->audit_end_date = $request->audit_end_date;
        $ie_audit->auditor_name = $request->auditor_name;
        $ie_audit->can_rectify = $request->can_rectify ?? 1;
        $ie_audit->audit_status = $request->audit_status ?? 'porgress';
        $ie_audit->status = $request->status ?? 1;

        // Save the new model instance
        $ie_audit->save();


        if ($channel == "api") {
            return ApiResponseController::successWithData('IE Audit Clause Created.', new IEAuditClauseCollection($ie_audit));
        }

        return ['success', 'Initiating Audit. Please wait...', route('audit_init.edit', $ie_audit->id)];
    }

    /**
     * Display the specified resource.
     */
    public function show($ie_audit_id, $channel = "web")
    {
        RolesPermissionController::can(['ie_audit_cluase.view']);
        $ie_audit = InternalExternalAuditClause::where('id', $ie_audit_id)->where('initiated_by', auth()->user()->id)->first();
        if (auth()->user()->can(['ie_audit_cluase.edit', 'ie_audit_cluase.create', 'ie_audit_cluase.delete'])) {
            $ie_audit = InternalExternalAuditClause::where('id', $ie_audit_id)->first();
        }

        if (!$ie_audit && $channel === 'api') {
            return ApiResponseController::error('IE Audit not found', 404);
        }

        if (!$ie_audit) {
            return ['error', 'IE Audit not found'];
        }

        if ($channel == "api") {
            return $ie_audit;
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(InternalExternalAuditClause $internalExternalAuditClause)
    {
        RolesPermissionController::can(['ie_audit_cluase.edit']);

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $ie_audit_id, $channel = 'web')
    {
        RolesPermissionController::can(['ie_audit_cluase.edit']);

        $validator = $this->validateData($request);

        $formErrorsResponse = FormValidatitionDispatcherController::Response($validator, $channel);
        if ($formErrorsResponse) {
            return $formErrorsResponse;
        }
        $ie_audit = InternalExternalAuditClause::where('id', $ie_audit_id)->where('initiated_by', auth()->user()->id)->first();
        if (auth()->user()->can(['ie_audit_cluase.edit', 'ie_audit_cluase.create', 'ie_audit_cluase.delete'])) {
            $ie_audit = InternalExternalAuditClause::where('id', $ie_audit_id)->first();
        }

        if (!$ie_audit && $channel === 'api') {
            return ApiResponseController::error('IE Audit not found', 404);
        }

        if (!$ie_audit) {
            return ['error', 'IE Audit not found'];
        }
        if ($ie_audit->audit_answers->count() > 0) {
            if ($channel == 'api') {
                return ApiResponseController::error('IE Audit cannot be updated as it is already responded. Please delete it.');
            }
            return ['error', 'IE Audit cannot be updated as it is already responded and some questions are answered. Please delete it'];
        }

        // cannot update if audit answer is given
        $ie_audit->meta_audit_hall_id = $request->meta_audit_hall_id;
        $ie_audit->meta_audit_type_id = $request->meta_audit_type_id;
        $ie_audit->audit_date = $request->audit_date;
        $ie_audit->audit_end_date = $request->audit_end_date;
        $ie_audit->auditor_name = $request->auditor_name;
        // $ie_audit->can_rectify = $request->can_rectify;
        // $ie_audit->audit_status = $request->audit_status;
        // $ie_audit->status = $request->status;

        // Save the new model instance
        $ie_audit->save();

        if ($channel === 'api') {
            return ApiResponseController::successWithData('IE Audit Clause Updated.', new IEAuditClauseCollection($ie_audit));
        }
    }

    public static function auditScoreCalculator($ie_audit_id)
    {
        $ie_audit = InternalExternalAuditClause::findOrFail($ie_audit_id);
        $true_answers = $ie_audit->audit_answers->where('yes_or_no', 1)->count();
        $false_answers = $ie_audit->audit_answers->where('yes_or_no', 0)->count();
        $total_answers = $true_answers + $false_answers;
        if ($total_answers > 0) {
            $ie_audit->audit_score = ($true_answers / $total_answers) * 100; //in percentage
        }
        $ie_audit->save();
        // return true;

    }

    /**zzz
     * Remove the specified resource from storage.
     */
    public function destroy($ie_audit_id, $channel = "web")
    {
        $ie_audit = InternalExternalAuditClause::where('id', $ie_audit_id)->where('initiated_by', auth()->user()->id)->first();
        if (auth()->user()->can(['ie_audit_cluase.edit', 'ie_audit_cluase.create', 'ie_audit_cluase.delete'])) {
            $ie_audit = InternalExternalAuditClause::where('id', $ie_audit_id)->first();
        }

        if (!$ie_audit && $channel === 'api') {
            return ApiResponseController::error('IE Audit not found', 404);
        }

        if (!$ie_audit) {
            return ['error', 'IE Audit not found'];
        }

        if ($ie_audit->delete()) {
            if ($channel == "api") {
                return ApiResponseController::success('IE Audit has been deleted');
            }

            return ['deleted', 'IE Audit has been deleted'];
        } else {
            if ($channel == "api") {
                return ApiResponseController::error('Could not delete IE Audit');
            }

            return ['error', 'Could not delete IE Audit'];
        }
    }

    public function validateData($request)
    {
        return Validator::make($request->all(), [
            'meta_audit_hall_id' => ['required', 'exists:meta_audit_halls,id'],
            'meta_audit_type_id' => ['required', 'exists:meta_audit_types,id'],
            'audit_date' => ['required', 'date', 'date_format:Y-m-d'],
            'audit_end_date' => ['nullable', 'date', 'after_or_equal:audit_date', 'date_format:Y-m-d'],
            'auditor_name' => ['nullable', 'string'],
            'can_rectify' => ['nullable', 'boolean'],
            'audit_status' => ['nullable', 'string', 'in:progress,pending,completed'],
            'status' => ['nullable', 'boolean'],
        ]);
    }
}