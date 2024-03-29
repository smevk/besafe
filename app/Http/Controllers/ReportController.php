<?php

namespace App\Http\Controllers;

use App\Exports\BasicExcelExport;
use App\Exports\IncidentExport;
use App\Http\Controllers\Api\ApiResponseController;
use App\Http\Resources\ReportCollection;
use App\Models\FirePropertyDamage;
use App\Models\Hazard;
use App\Models\Injury;
use App\Models\InternalExternalAuditClause;
use App\Models\MetaDepartment;
use App\Models\MetaFireCategory;
use App\Models\MetaIncidentStatus;
use App\Models\MetaInjuryCategory;
use App\Models\MetaLine;
use App\Models\MetaLocation;
use App\Models\MetaPropertyDamage;
use App\Models\MetaRiskLevel;
use App\Models\MetaUnit;
use App\Models\NearMiss;
use App\Models\PermitToWork;
use App\Models\Report;
use App\Models\UnsafeBehavior;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class ReportController extends Controller
{

    public function index(Request $request, $channel = "web")
    {

        $limit = 10;
        $reports = Report::query();
        if ($request->has('limit') && $request->limit != "") {
            $limit = $request->limit;
        }
        if ($request->has('report_of') && $request->report_of != "") {
            $reports = $reports->where('report_of', $request->report_of);
        }

        if ($channel == 'api') {
            return ReportCollection::collection($reports->latest()->paginate($limit));
        }


        if ($request->ajax()) {
            $data = [];
            $i = 0;
            foreach ($reports->latest()->get() as $report) {
                $i++;
                $data[] = [
                    'sno' => $i,
                    'report_of' => ucfirst($report->report_of),
                    'from_date' => formatDate($report->from_date),
                    'to_date' => formatDate($report->to_date),
                    "file" => asset('reports/' . $report->file_name),
                    'generated_by' => $report->user->first_name,
                    'created_at' => $report->created_at->format('d-m-Y'),
                    'action' => view('pdfreports.partials.action-buttons', ['report' => $report])->render()
                ];
            }

            return DataTables::of($data)->toJson();
        }

        return view('pdfreports.index');
    }

    public function store(Request $request)
    {
        return $this->createReport($request);
    }


    public function createReport(Request $request, $channel = "web")
    {
        $validator = Validator::make($request->all(), [
            'report_of' => 'required|in:hazards,near_misses,unsafe_behaviors,injuries,fpdamages,ptws,ie_audits',
            // 'availble_reports' => 'required|min:5|string',
        ]);

        $formErrorsResponse = FormValidatitionDispatcherController::Response($validator, $channel);
        if ($formErrorsResponse) {
            return $formErrorsResponse;
        }

        $filters = $request->except('report_of', 'to_date', 'from_date', 'availble_reports', 'redirect', 'month_selector', '_token');
        if ($request->has('report_of') && $request->report_of != "") {
            $model = $this->getIncidentModelViaKeys($request->report_of);
            if ($model) {
                $data = $model::query();
                if ($request->has('from_date') && $request->has('to_date')) {
                    $data = $data->whereBetween('created_at', [$request->from_date, $request->to_date]);
                }
                // filters
                if (!empty($filters) && $filters != "Select") {
                    $valuesToIgnore = ['Select', '', 'null'];
                    foreach ($filters as $filter_key => $filter_value) {
                        if (!in_array($filter_value, $valuesToIgnore)) {

                            // checking if filter is availble
                            $filterExist = $this->availbleFilters($filter_key, $request->report_of);
                            if ($filterExist) {
                                $data = $data->where($filter_key, $filter_value);
                            }
                        }
                    }
                }

                // gettting final data
                $data = $data->get();
                if ($data->first()) {
                    if ($request->report_file_format == 'excel') {
                        $report = $this->generateExcelReport($request, $data);
                    } else {
                        // return $report = $this->generatePdfReport($request, $data);
                        $report = $this->generatePdfReport($request, $data);
                    }
                    if (@$report->user_id && $channel == 'api') {
                        return ApiResponseController::successWithData('Report has been generated', new ReportCollection($report));
                    }
                } else {
                    if ($channel == 'api') {
                        return ApiResponseController::error('No Data Returned. Please change attributes');
                    }
                    return ['error', 'No Data Returned. Please change attributes'];
                }

            } else {
                return ApiResponseController::error('Please provide valid report_of');
            }
        }

        return ['success', 'Report has been generated', $request->redirect];
    }


    public function getIncidentModelViaKeys($key)
    {
        $keys = [
            'hazards' => Hazard::class,
            'near_misses' => NearMiss::class,
            'unsafe_behaviors' => UnsafeBehavior::class,
            'injuries' => Injury::class,
            'fpdamages' => FirePropertyDamage::class,
            'ptws' => PermitToWork::class,
            'ie_audits' => InternalExternalAuditClause::class,
        ];

        return $keys[$key];
    }

    public function availbleFilters($filter_name, $model_key)
    {

        $keys = [
            'hazards' => ['meta_incident_status_id', 'meta_department_id', 'meta_line_id', 'meta_unit_id', 'initiated_by', 'meta_location_id', 'meta_risk_level_id'],
            'near_misses' => ['meta_incident_status_id', 'initiated_by', 'meta_location_id', 'meta_department_id'],
            'unsafe_behaviors' => ['meta_incident_status_id', 'initiated_by', 'meta_department_id', 'meta_line_id', 'meta_unit_id', 'meta_location_id', 'meta_risk_level_id'],
            'injuries' => ['meta_incident_status_id', 'initiated_by', 'meta_injury_category_id', 'meta_incident_category_id', 'meta_location_id', 'meta_risk_level_id', 'meta_line_id', 'witness_name', 'time'],
            'fpdamages' => ['meta_incident_status_id', 'initiated_by', 'meta_fire_category_id', 'meta_unit_id', 'meta_property_damage_id', 'reviewed_by', 'investigated_by', 'meta_location_id'],
            'ptws' => ['initiated_by', 'meta_ptw_type_id', 'meta_ptw_item_id'],
            'ie_audits' => ['initiated_by', 'meta_audit_type_id', 'meta_audit_hall_id'],
        ];


        return in_array($filter_name, $keys[$model_key]);
    }

    public function generatePdfReport($request, $data)
    {
        $now = Carbon::now();
        $file_name = $request->report_of . '_' . $now;
        if ($request->has('from_date') && $request->has('to_date')) {
            $file_name = $request->report_of . '_' . $request->from_date . "_to_" . $request->to_date;
        }
        $file_name = $file_name . $now->getTimestamp();
        $file_name = \Str::slug($file_name) . ".pdf";
        $view = $this->getViewForReport($request->availble_reports);
        if ($view == "") {
            $view = $this->getViewForReport($request->report_of . "_" . "list");
        }
        // return view($view, ['data' => $data]);
        try {
            $file = \PDF::loadView($view, ['data' => $data])
                ->setPaper('a4')
                ->setOption('margin-left', 3)
                ->setOption('margin-right', 3)
                ->setOption('margin-top', 3)
                ->setOrientation('landscape');
            $file->save(public_path('reports/' . $file_name));
            return $this->saveReport($request, $file_name);
        } catch (\Exception $e) {
            return $e->getMessage();
        }


    }
    public function generateExcelReport($request, $data)
    {
        $now = Carbon::now();
        $file_name = $request->report_of . '_' . $now;
        if ($request->has('from_date') && $request->has('to_date')) {
            $file_name = $request->report_of . '_' . $request->from_date . "_to_" . $request->to_date;
        }
        $file_name = $file_name . $now->getTimestamp();
        $file_name = \Str::slug($file_name) . ".xlsx";

        try {
            if (Excel::store(new BasicExcelExport($data), $file_name)) {
                $sourceFilePath = storage_path('app/' . $file_name);
                $file_path = public_path('reports/' . $file_name);
                File::move($sourceFilePath, $file_path);
            }

            return $this->saveReport($request, $file_name);
        } catch (\Exception $e) {
            return $e->getMessage();
        }


    }

    public function saveReport($request, $file_name)
    {
        $report = new Report();
        $report->user_id = auth()->user()->id;
        $report->report_of = $request->report_of;
        $report->file_path = 'reports';
        $report->file_name = $file_name;
        $report->from_date = $request->from_date;
        $report->to_date = $request->to_date;
        $report->save();

        return $report;
    }


    public function getViewForReport($model_key)
    {


        $keys = [
            'hazards_list' => 'pdf.hazards_list',
            'near_misses_list' => 'pdf.near_misses_list',
            'unsafe_behaviors_list' => 'pdf.unsafe_behaviors_list',
            'injuries_list' => 'pdf.injuries_list',
            'fpdamages_list' => 'pdf.fpdamages_list',
            'ptws_list' => 'pdf.ptws_list',
            'ie_audits_list' => 'pdf.ie_audits_list',
        ];

        return $keys[$model_key];
    }

    public function metaData()
    {
        $data = [];
        $data['locations'] = MetaLocation::select('id', 'location_title as title')->get();
        $data['departments'] = MetaDepartment::select('id', 'department_title as title')->get();
        $data['incident_statuses'] = MetaIncidentStatus::select('id', 'status_title as title')->get();
        $data['risk_levels'] = MetaRiskLevel::select('id', 'risk_level_title as title')->get();
        $data['units'] = MetaUnit::select('id', 'unit_title as title')->get();
        $data['injury_categories'] = MetaInjuryCategory::select('id', 'injury_category_title as title')->get();
        $data['lines'] = MetaLine::select('id', 'line_title as title')->get();
        $data['property_types'] = MetaPropertyDamage::select('id', 'property_damage_title as title')->get();
        $data['fire_categories'] = MetaFireCategory::select('id', 'fire_category_title as title')->get();
        return $data;
    }

    public function destroy($report_id, $channel = 'web')
    {
        // RolesPermissionController::can(['report.delete']);

        $report = Report::where('id', $report_id)->first();
        if (!$report && $channel === "api") {
            return ApiResponseController::error('report not found', 404);
        }

        if (!$report) {
            return ['error', 'report not found'];
        }

        // deleting the report
        if (
            File::delete(public_path('reports/' . $report->file_name)) &&
            $report->delete()
        ) {
            if ($channel === 'api') {
                return ApiResponseController::success('report has been delete');
            } else {
                return ['deleted', 'report has been deleted'];
            }
        } else {
            if ($channel === 'api') {
                return ApiResponseController::error('Could not delete the report.');
            } else {
                return ['error', 'Could not delete the report'];
            }
        }
    }
}