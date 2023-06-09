@extends('layouts.main')
@section('breadcrumb')
<x-templates.bread-crumb page-title="Reports List">
</x-templates.bread-crumb>
@endsection

@section('content')
  <x-templates.basic-page-temp page-title="Reports List" page-desc="List of Generated Reports">
    {{-- x-slot:pageheader referes to the second slot in one componenet --}}
      <x-slot:pageHeader>
        <div class="ms-auto my-auto mt-lg-0 mt-4">
          {{-- <div class="ms-auto my-auto">
            <a href="{{route('reports.create')}}" class="btn bg-gradient-primary btn-sm mb-0" >+&nbsp; New report</a>
            <button class="btn btn-outline-primary btn-sm export mb-0 mt-sm-0 mt-1" data-type="csv" type="button" name="button">Export</button>
          </div> --}}
        </div>
      </x-slot>
      {{-- x slot page header ends here --}}

      
      {{-- default slot starts here --}}

      <div class="row container">
        <div class="col-12 mb-5">

          @can('report.create')
            <div class="accordion-item mb-3">
              <h5 class="accordion-header" id="headingOne">
                <button class="accordion-button border-bottom font-weight-bold collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                  Generate New Reports
                  <i class="collapse-close fa fa-plus text-xs pt-1 position-absolute end-0 me-3" aria-hidden="true"></i>
                  <i class="collapse-open fa fa-minus text-xs pt-1 position-absolute end-0 me-3" aria-hidden="true"></i>
                </button>
              </h5>
              <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#accordionRental" style="">
                <div class="accordion-body text-sm opacity-8">
                <form action="{{route('pdfreports.store')}}" method="post" class="ajax-form row" enctype="multipart/form-data">
                  @csrf
                  <div class="form-group col-4">
                      <label for="report_of">Report Of</label>
                      <select name="report_of" class="form-control-sm form-control">
                          <option value="hazards">Hazards</option>
                          <option value="near_misses">Near Misses</option>
                          <option value="unsafe_behaviors">Unsafe Behaviors</option>
                          <option value="injuries">Injuries</option>
                          <option value="fpdamages">Fire and Property Damages</option>
                          <option value="ptws">Permit to Work</option>
                          <option value="ie_audits">IE Audits</option>
                      </select>
                  </div>

              <x-forms.basic-input label="From Date" name="from_date" type="date" value=""  width="col-4" input-class="form-control-sm"></x-forms.basic-input>
              <x-forms.basic-input label="To Date" name="to_date" type="date" value="" width="col-4" input-class="form-control-sm"></x-forms.basic-input>

              <div class="form-group col-4">
                <label for="report_file_format">Report File Format</label>
                <select name="report_file_format" class="form-control-sm form-control">
                    <option value="pdf">PDF</option>
                    <option value="excel">Excel</option>
                </select>
              </div>
                  <div class="form-group col-12">
                  <input type="hidden" name="redirect" value="{{url()->current()}}">
                  <x-forms.ajax-submit-btn div-class="col-12"  id="submit-button" btn-class="btn-sm btn-primary btn-ladda">Generate Report</x-forms.ajax-submit-btn>
                  </div>
                </form>

              </div>
            </div>
          @endcan
        </div>
        </div>
      </div>


        <div class="table-responsive">
            <table class="table table-flush" id="report-table" data-source="{{route('pdfreports.index')}}">
              <thead class="thead-light">
                <x-table.tblhead heads="S.No,Report of, From Date, To Date,Generated By,Created at,Action"></x-table.tblhead>
              </thead>
              <tbody>
              </tbody>
             
            </table>
        </div>
      {{-- defautl slot end here --}}

   </x-templates.basic-page-temp>

@endsection
@section('script')
<script>    
$(document).ready(function() {
  const table = $('#report-table');
  const DataSource = table.attr('data-source');
  table.DataTable({
    ajax: {
      url: DataSource,
      type: 'GET',
    },
    columns: [
      { data: 'sno', name: 'sno' },
      { data: 'report_of', name: 'report_of' },
      { data: 'from_date', name: 'from_date' },
      { data: 'to_date', name: 'to_date' },
      { data: 'generated_by', name: 'generated_by' },
      { data: 'created_at', name: 'created_at' },
      { data: 'action', name: 'action', orderable: false, searchable: false },
    ],
    
  });
  
});
</script>
@endsection