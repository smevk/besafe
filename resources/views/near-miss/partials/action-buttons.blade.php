
    <td class="text-sm">
        @can('near_miss.edit')
        <x-forms.action-btn href="{{route('near-miss.edit',$near_miss['id'])}}" action="edit" title="edit near miss"></x-forms.action-btn>
            
        @endcan
        @can('near_miss.view')
        <x-forms.action-btn href="{{route('near-miss.show',$near_miss['id'])}}" action="view" title="view near miss"></x-forms.action-btn>
            
        @endcan
        @can('near_miss.delete')
        <x-forms.action-btn href="" id="table_data_delete" action="delete" title="delete near miss"
            data-action="{{route('near-miss.destroy',$near_miss['id'])}}" data-parent="tr"></x-forms.action-btn>
        @endcan
    </td>
