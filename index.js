$(function () {
    const table = $('#userTable').DataTable({
        ajax: {
            url: 'api.php',
            type: 'POST',
            data: { read: true },
            dataSrc: 'data'
        },
        columns: [
            { data: 'id' },
            {
                data: 'profile_photo',
                render: function (data) {
                    return `<img src="uploads/${data}" width="50" height="50" class="rounded-circle">`;
                }
            },
            { data: 'name' },
            { data: 'email' },
            { data: 'phone' },
            { data: 'gender' },
            { data: 'age' },
            { data: 'address' },
            { data: 'created_at' },
            {
                data: 'salary',
                render: function (data) {
                    return parseFloat(data).toFixed(2);
                }
            },
            {
                data: null,
                orderable: false,
                render: function () {
                    return `
                        <button class="btn btn-sm btn-warning editBtn">Edit</button>
                        <button class="btn btn-sm btn-danger deleteBtn">Delete</button>`;
                }
            }
        ],
        rowCallback: function (row, data) {
            $(row).attr('data-id', data.id);
        }
    });
    
    let deleteId = null;

    // Session check
    $.get('session-check.php', function (res) {
        if (!res.logged_in) {
            $('#loginModal').modal('show');
        } else {
            $('#mainContent').show();
            $('#navbarUsername').text(res.user_name);
        }
    }, 'json');

    // Tooltip display
    function showTooltip(msg, type = 'success') {
        $('#tooltip')
            .removeClass('tooltip-success tooltip-error')
            .addClass(type === 'success' ? 'tooltip-success' : 'tooltip-error')
            .text(msg).fadeIn(200).delay(2000).fadeOut(400);
    }

    // Login
    $('#loginForm').on('submit', function (e) {
        e.preventDefault();
        const formData = $(this).serialize() + '&login=true';
        $('#loginError').text('');
        $.post('api.php', formData, function (res) {
            if (res.status === 'success') {
                $('#loginModal').modal('hide');
                $('#mainContent').fadeIn();
                $('#navbarUsername').text(res.name || 'Admin');
            } else {
                $('#loginError').text(res.message || 'Invalid credentials');
            }
        }, 'json');
    });

    // Form validation config (used for both add and edit)
    const formRules = {
        name: { required: true },
        phone: { required: true, minlength: 10, maxlength: 15 },
        email: { required: true, email: true },
        age: { required: true, number: true, min: 1 },
        salary: { required: true, number: true, min: 0 },
        address: { required: true },
        gender: { required: true },
        profile_photo: { extension: 'jpg|jpeg|png|gif' }
    };

    const formMessages = {
        name: 'Please enter name',
        phone: {
            required: 'Please enter phone number',
            minlength: 'Minimum 10 digits',
            maxlength: 'Maximum 15 digits'
        },
        email: {
            required: 'Email is required',
            email: 'Enter valid email'
        },
        age: {
            required: 'Please enter age',
            number: 'Enter numeric value',
            min: 'Age must be at least 1'
        },
        salary: {
            required: 'Please enter salary',
            number: 'Enter numeric value',
            min: 'Salary must be non-negative'
        },
        address: 'Please enter address',
        gender: 'Please select gender',
        profile_photo: { extension: 'Only image files allowed' }
    };

    // Add Form
    $('#addForm').validate({
        rules: formRules,
        messages: formMessages,
        errorClass: 'text-danger small',
        submitHandler: function (form) {
            let formData = new FormData(form);
            formData.append('create', true);
            $('#addForm label.text-danger.small').remove();

            $.ajax({
                url: 'api.php',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success(res) {
                    if (res.status === 'success') {
                        $('#addModal').modal('hide');
                        table.row.add([
                            res.user.id,
                            `<img src="uploads/${res.user.profile_photo}" width="50" height="50" class="rounded-circle">`,
                            res.user.name,
                            res.user.email,
                            res.user.phone,
                            res.user.gender,
                            res.user.age,
                            res.user.address,
                            res.user.created_at,
                            parseFloat(res.user.salary).toFixed(2),
                            `<button class="btn btn-sm btn-warning editBtn">Edit</button>
                             <button class="btn btn-sm btn-danger deleteBtn">Delete</button>`
                        ]).draw(false);
                        showTooltip('User added!');
                    } else if (res.status === 'error' && res.field && res.message) {
                        $(`#addForm [name="${res.field}"]`).after(
                            `<label class="text-danger small">${res.message}</label>`
                        );
                    } else {
                        showTooltip('Failed to add user', 'error');
                    }
                }
            });
        }
    });

    // Edit click
    $('#userTable tbody').on('click', '.editBtn', function () {
        const row = $(this).closest('tr');
        const id = row.data('id');
        $.get('api.php', { get_user_id: id }, function (u) {
            $('#edit-id').val(u.id);
            $('#existing_photo').val(u.profile_photo);
            $('#edit-name').val(u.name);
            $('#edit-phone').val(u.phone);
            $('#edit-email').val(u.email);
            $('#edit-age').val(u.age);
            $('#edit-salary').val(u.salary);
            $('#edit-address').val(u.address);
            $(`#editForm input[name="gender"][value="${u.gender}"]`).prop('checked', true);
            $('#editPreview').attr('src', 'uploads/' + u.profile_photo);
            $('#editModal').modal('show');
        }, 'json');
    });

    // Edit Form
    $('#editForm').validate({
        rules: formRules,
        messages: formMessages,
        errorClass: 'text-danger small',
        submitHandler: function (form) {
            let formData = new FormData(form);
            formData.append('update', true);
            $('#editForm label.text-danger.small').remove();

            $.ajax({
                url: 'api.php',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success(res) {
                    if (res.status === 'success') {
                        $('#editModal').modal('hide');
                        const row = $(`#userTable tr[data-id="${formData.get('id')}"]`);
                        let photo = res.newPhoto || formData.get('existing_photo');
                        table.row(row).data([
                            formData.get('id'),
                            `<img src="uploads/${photo}" width="50" height="50" class="rounded-circle">`,
                            formData.get('name'),
                            formData.get('email'),
                            formData.get('phone'),
                            formData.get('gender'),
                            formData.get('age'),
                            formData.get('address'),
                            res.user.created_at || '',
                            parseFloat(formData.get('salary')).toFixed(2),
                            `<button class="btn btn-sm btn-warning editBtn">Edit</button>
                             <button class="btn btn-sm btn-danger deleteBtn">Delete</button>`
                        ]).draw(false);
                        showTooltip('User updated!');
                    } else if (res.status === 'error' && res.field && res.message) {
                        $(`#editForm [name="${res.field}"]`).after(
                            `<label class="text-danger small">${res.message}</label>`
                        );
                    } else {
                        showTooltip('Failed to update', 'error');
                    }
                }
            });
        }
    });

    // Delete user
    $('#userTable tbody').on('click', '.deleteBtn', function () {
        deleteId = $(this).closest('tr').data('id');
        $('#deleteModal').modal('show');
    });

    $('#confirmDeleteBtn').click(function () {
        $.post('api.php', { delete: true, id: deleteId }, function (res) {
            if (res.status === 'success') {
                let row = $(`#userTable tr[data-id="${deleteId}"]`);
                table.row(row).remove().draw(false);
                $('#deleteModal').modal('hide');
                showTooltip('User deleted!', 'error');
            } else {
                showTooltip('Failed to delete', 'error');
            }
        }, 'json');
    });

    // Profile photo preview
    $(document).on('click', '.photo-wrapper img, .edit-icon', function (e) {
        e.stopPropagation();
        $('#edit-photo').trigger('click');
    });

    $('#edit-photo').on('change', function (event) {
        const output = document.getElementById('editPreview');
        output.src = URL.createObjectURL(event.target.files[0]);
    });

    // Logout
    $('#logoutBtn').click(function (e) {
        e.preventDefault();
        $.post('api.php', { logout: true }, function () {
            location.reload();
        });
    });

    $('#importForm').on('submit', function (e) {
        e.preventDefault();
    
        const formData = new FormData(this);
    
        $.ajax({
            url: 'import-excel.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json',
            success: function (res) {
                if (res.status === 'success') {
                    let messages = [];
    
                    if (res.inserted > 0) {
                        messages.push(`${res.inserted} user(s) added`);
                    }
    
                    if (res.updated > 0) {
                        messages.push(`${res.updated} user(s) updated`);
                    }
    
                    if (messages.length > 0) {
                        showTooltip(messages.join(' & ')); // e.g., "3 user(s) added & 2 user(s) updated"
                    }
    
                    $('#importModal').modal('hide');
                    $('#importForm')[0].reset();
                    $('#userTable').DataTable().ajax.reload();
                } else {
                    $('#importError').text(res.message || 'Unknown error');
                }
            },
            error: function () {
                $('#importError').text('Something went wrong!');
            }
        });
    });
    

    // Modal Reset Handlers
    $('#importModal').on('hidden.bs.modal', function () {
        $('#importForm')[0].reset();
        $('#importError').text('');
    });

    $('#addModal').on('hidden.bs.modal', function () {
        $('#addForm')[0].reset();
        $('#addForm').validate().resetForm();
    });

    $('#editModal').on('hidden.bs.modal', function () {
        $('#editForm').validate().resetForm();
        $('#editForm .text-danger').remove();
    });
});
