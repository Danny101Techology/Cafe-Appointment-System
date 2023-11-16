<script setup>
import { ref } from 'vue';
import { useToastr } from '../../toastr.js';

const props = defineProps({
    user: Object,
    index: Number,
    selectAll: Boolean,
})

const emit = defineEmits(['userDeleted', 'editUser']);
const toastr = useToastr();
const userIdBeingDeleted = ref(null);

const confirmUserDeletion = (user) => {
    userIdBeingDeleted.value = user.id;
    $('#deleteUserModal').modal('show');
};

const deleteUser = () => {
    axios.delete(`/api/users/${userIdBeingDeleted.value}`)
        .then(() => {
            $('#deleteUserModal').modal('hide');
            // users.value = users.value.filter(user => user.id !== userIdBeingDeleted.value)
            toastr.success('User deleted successfully!');
            emit('userDeleted', userIdBeingDeleted.value);
        });
}

const editUser = (user) => {
    emit('editUser', user);
};

const roles = ref([
    {
        name: 'Admin',
        value: 1
    },
    {
        name: 'Manager',
        value: 2
    },
    {
        name: 'User',
        value: 3
    }
]);

const changeRole = (user, role) => {
    axios.patch(`/api/users/${user.id}/change-role`, {
        role: role,
    })
        .then(() => {
            toastr.success('Role changed successfully!');
        })
};

const toggleSelection = () => {
    emit('toggleSelection', props.user);
}
</script>
<template>
    <tr>
        <td class="text-center" style="width: 50px"><input type="checkbox" :checked="selectAll" @change="toggleSelection" />
        </td>
        <td>{{ index + 1 }}</td>
        <td>{{ user.name }}</td>
        <td>{{ user.email }}</td>
        <td>{{ user.formatted_created_at }}</td>
        <td>
            <select name="" id="" class="form-control" @change="changeRole(user, $event.target.value)">
                <option v-for="role in roles" :value="role.value" :selected="user.role === role.name">{{ role.name }}
                </option>
            </select>
        </td>
        <td>
            <a href="#" @click.prevent="editUser(user)"><i class="fa fa-edit"></i></a>
            <a href="#" @click.prevent="confirmUserDeletion(user)"><i class="fa fa-trash text-danger ml-2"></i></a>
        </td>
    </tr>

    <div class="modal fade" id="deleteUserModal" data-backdrop="static" tabindex="-1" role="dialog"
        aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel">
                        <span>Delete User</span>
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <h5>Are you sure you want to delete this user?</h5>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        Cancel
                    </button>
                    <button @click.prevent="deleteUser" type="button" class="btn btn-danger">
                        Remove
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
