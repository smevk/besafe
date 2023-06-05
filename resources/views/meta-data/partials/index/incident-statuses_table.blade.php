@php
$collection = $incidentStatuses;
$title_field = "status_title";
$desc_field = "status_desc";
$meta_data_name = "incident_statuses";
$heads = "S.No,incident status ID,incident status title,status code,Group,Date Created,Actions";
@endphp

<div class="d-flex justify-content-between">
  <h5>
    {{ucfirst(str_replace('_'," ",$meta_data_name))}}
  </h5>
  {{-- <a data-bs-toggle="modal" id="meta_data_add_button" data-title_field="{{$title_field}}" data-desc_field="{{$desc_field}}" data-meta_data_name="{{$meta_data_name}}"    data-bs-target="#metaDataCreate"class="btn btn-sm btn-primary align-right">Add</a> --}}
</div>

<div class="table-responsive">
<table class="table table-flush datatable" id="datatable">
  <thead class="thead-light">
    <x-table.tblhead :heads=$heads></x-table.tblhead>
  </thead>
  <tbody>
    @foreach ($collection as $meta_data)
        <tr>
            <td>{{$loop->iteration}}</td>
            <td>{{$meta_data->id}}</td>
            <td>{{$meta_data->$title_field}}</td>
            <td>{{$meta_data->status_code}}</td>
            <td>{{$meta_data->group_name}}</td>
            <td>{{$meta_data->created_at}}</td>
            <td class="text-sm">
              @include('meta-data.partials.index.meta_action_btn')
          </td>
        </tr>
    @endforeach

  </tbody>
  
</table>
</div>