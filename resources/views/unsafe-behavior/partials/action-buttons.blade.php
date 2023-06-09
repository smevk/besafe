
    <td class="text-sm">
        @can('unsafe_behavior.edit')
        <x-forms.action-btn href="{{route('unsafe-behaviors.edit',$unsafe_behavior['id'])}}" action="edit" title="edit unsafe behavior"></x-forms.action-btn>
            
        @endcan
        @can('unsafe_behavior.view')
        <x-forms.action-btn href="{{route('unsafe-behaviors.show',$unsafe_behavior['id'])}}" action="view" title="view unsafe behavior"></x-forms.action-btn>
            
        @endcan

        @can('unsafe_behavior.delete')
        <x-forms.action-btn href="" id="table_data_delete" action="delete" title="delete unsafe behavior"
        data-action="{{route('unsafe-behaviors.destroy',$unsafe_behavior['id'])}}" data-parent="tr"></x-forms.action-btn>
            
        @endcan
    </td>
