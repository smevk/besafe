
    <td class="text-sm">
        
        <x-forms.action-btn href="" action="edit" title="edit user"></x-forms.action-btn>
        <x-forms.action-btn href="" action="view" title="view user"></x-forms.action-btn>
        <x-forms.action-btn href="" id="table_data_delete" action="delete" title="delete user"
            data-action="{{route('users.destroy',$user['id'])}}" data-parent="tr"></x-forms.action-btn>
    </td>
