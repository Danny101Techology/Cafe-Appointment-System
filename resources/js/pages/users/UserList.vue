<script setup>
import axios from 'axios';
import { ref, onMounted, reactive, watch } from 'vue';
import { Form, Field, useResetForm, useSetFieldError } from 'vee-validate';
import * as yup from 'yup';
import UserListItem from './UserListItem.vue';
import { useToastr } from '../../toastr.js';
import { debounce } from 'lodash';
import { Bootstrap4Pagination } from 'laravel-vue-pagination';

const users = ref({ 'data': [] });
const editing = ref(false);
const formValues = ref();
const form = ref(null);
const searchQuery = ref(null);
const toastr = useToastr();


const getUsers = (page = 1) => {
    axios.get(`/api/users?page=${page}`)
        .then((response) => {
            users.value = response.data;
            selectedUsers.value = [];
            selectAll.value = false;
        });
};

const createUserSchema = yup.object({
    name: yup.string().required(),
    email: yup.string().email().required(),
    password: yup.string().required().min(8),
});

const editUserSchema = yup.object({
    name: yup.string().required(),
    email: yup.string().email().required(),
    password: yup.string().when((password, schema) => {
        return password ? schema.min(8) : schema;
    }),

});


const createUser = (values, { resetForm, setErrors }) => {
    axios.post('/api/users', values)
        .then((response) => {
            users.value.data.unshift(response.data); // in the first line in the table
            //user.value.shift(response.data); in the last line in the table

            $('#UserFormModal').modal('hide');
            resetForm();
            toastr.success('User created successfully!');
        })
        .catch((error) => {
            if (error.response.data.errors) {
                setErrors(error.response.data.errors);
            }
            // setFieldError('email', error.response.data.errors.email[0]);
        })
};


// const form = reactive({
//     name: "",
//     email: "",
//     password: "",
// });

// const createUser = () => {
//     axios.post('/api/users', form)
//     .then((response) => {
//         users.value.unshift(response.data); // in the first line in the table
//          //user.value.unshift(response.data); in the last line in the table
//         form.name = '';
//         form.email = '';
//         form.password = '';
//         $('#UserFormModal').modal('hide');
//     });
// }

const addUser = () => {
    editing.value = false;
    $("#UserFormModal").modal("show");
};

const editUser = (user) => {
    editing.value = true;
    form.value.resetForm();
    $("#UserFormModal").modal("show");
    formValues.value = {
        id: user.id,
        name: user.name,
        email: user.email,
    };
};

const updateUser = (values, { setErrors }) => {
    axios.put('/api/users/' + formValues.value.id, values)
        .then((response) => {
            const index = users.value.findIndex(user => user.id === response.data.id);
            users.value[index] = response.data;
            $('#UserFormModal').modal('hide');
            toastr.success('User updated successfully!');
        }).catch((error) => {
            setErrors(error.response.data.errors);
            console.log(error);
        })
}

const handleSubmit = (values, actions) => {
    if (editing.value) {
        updateUser(values, actions);
    } else {
        createUser(values, actions);
    }
}

const userDeleted = (userId) => {
    users.value = users.value.filter(user => user.id !== userId)
};


const search = () => {
    axios.get('/api/users/search', {
        params: {
            query: searchQuery.value
        }
    })
        .then(response => {
            users.value = response.data;
        })
        .catch(error => {
            console.log(error);
        })
}

const selectedUsers = ref([]);
const toggleSelection = (user) => {
    const index = selectedUsers.value.indexOf(user.id);
    if (index === -1) {
        selectedUsers.value.push(user.id);
    } else {
        selectedUsers.value.splice(index, 1);
    }
    console.log(selectedUsers.value);
};

const bulkDelete = () => {
    axios.delete('/api/users', {
        data: {
            ids: selectedUsers.value
        }
    })
        .then(response => {
            users.value.data = users.value.data.filter(user => !selectedUsers.value.includes(user.id));
            selectedUsers.value = [];
            selectAll.value = false;
            toastr.success('Users deleted successfully!');
        });
}
const selectAll = ref(false);
const selectAllUsers = () => {
    if (selectAll.value) {
        selectedUsers.value = users.value.data.map(user => user.id);
    } else {
        selectedUsers.value = [];
    }
    console.log(selectedUsers.value);
}

watch(searchQuery, debounce(() => {
    search();
}, 300));

onMounted(() => {
    getUsers();
});
</script>
<template>
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Users</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item active">Users</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <div class="d-flex justify-content-between">
                <div class="d-flex">
                    <button @click="addUser" type="button" class="btn btn-primary mb-2">
                        <i class="fa fa-plus-circle mr-1"></i>
                        Add New User
                    </button>
                    <div v-if="selectedUsers.length > 0">
                        <button @click="bulkDelete" type="button" class="btn btn-danger mb-2 ml-2">
                            <i class="fa fa-trash mr-1"></i>
                            Delete Selected
                        </button>
                        <span class="ml-2">Selected {{ selectedUsers.length }} users</span>
                    </div>

                </div>

                <div>
                    <input type="text" v-model="searchQuery" class="form-control" placeholder="Search..." />
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 50px"><input type="checkbox" v-model="selectAll"
                                        @change="selectAllUsers" /></th>
                                <th style="width: 10px">#</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Registered Date</th>
                                <th>Role</th>
                                <th>Options</th>
                            </tr>
                        </thead>
                        <tbody v-if="users.data.length > 0">
                            <!-- <tr v-for="(user, index) in users" :key="user.id">
                                <td>{{ index + 1 }}</td>
                                <td>{{ user.name }}</td>
                                <td>{{ user.email }}</td>
                                <td>{{ user.formatted_created_at }}</td>
                                <td>{{ user.role }}</td>
                                <td>
                                    <a href="#" @click.prevent="editUser(user)"><i class="fa fa-edit"></i></a>
                                    <a href="#" @click.prevent="confirmUserDeletion(user)"><i
                                            class="fa fa-trash text-danger ml-2"></i></a>
                                </td>
                            </tr> -->
                            <UserListItem v-for="(user, index) in users.data" :key="user.id" :user=user :index=index
                                @user-deleted="userDeleted" @edit-user="editUser" @toggle-selection="toggleSelection"
                                :select-all="selectAll" />
                        </tbody>
                        <tbody v-else>
                            <tr>
                                <td colspan="6" class="text-center"> No results found
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="mt-3" style="margin-left: 50%;margin-right: 50%;">
                        <Bootstrap4Pagination :data="users" @pagination-change-page="getUsers" />
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="UserFormModal" data-backdrop="static" tabindex="-1" role="dialog"
        aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel">
                        <span v-if="editing">Edit User</span>
                        <span v-else>Add New User</span>
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <Form ref="form" @submit="handleSubmit" :validation-schema="editing ? editUserSchema : createUserSchema"
                    v-slot="{ errors }" :initial-values="formValues">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="name">Name</label>
                            <Field name="name" type="text" class="form-control" :class="{ 'is-invalid': errors.name }"
                                id="name" aria-describedby="" placeholder="Enter Full Name" />
                            <span class="invalid-feedback">{{ errors.name }}</span>
                        </div>

                        <div class="form-group">
                            <label for="email">Email</label>
                            <Field name="email" type="email" class="form-control" :class="{ 'is-invalid': errors.email }"
                                id="email" aria-describedby="l" placeholder="Enter Emai" />
                            <span class="invalid-feedback">{{ errors.email }}</span>
                        </div>

                        <div class="form-group">
                            <label for="email">Password</label>
                            <Field name="password" type="password" class="form-control"
                                :class="{ 'is-invalid': errors.password }" id="password" aria-describedby="Enter Password"
                                placeholder="Enter Password" />
                            <span class="invalid-feedback">{{ errors.password }}</span>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            Save
                        </button>
                    </div>
                </Form>
            </div>
        </div>
    </div>
    <!-- <div class="modal fade" id="deleteUserModal" data-backdrop="static" tabindex="-1" role="dialog"
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
    </div> -->
</template>
