    $(function () {

    let currentPage = 1;
    let searchQuery = '';
    let sortBy = 'id';
    let sortDir = 'asc';

    function fetchUsers() {
        $.getJSON('api.php', {
            action: 'read',
            page: currentPage,
            search: searchQuery,
            sortBy: sortBy,
            sortDir: sortDir,
        }, function (res) {
            const tbody = $('#userTableBody').empty();
            
            if (res.data.length === 0) {
                tbody.append('<tr><td colspan="5" class="text-center">No users found.</td></tr>');
                $('#pagination').empty();
                return;
            }

            res.data.forEach(user => {
                tbody.append(`
                    <tr>
                        <td>${user.id}</td>
                        <td>${user.name}</td>
                        <td>${user.email}</td>
                        <td><img src="uploads/${user.profile_photo || 'default.png'}" class="profile-img" width="40"></td>
                        <td>
                            <button class="btn btn-sm btn-info editBtn" data-id="${user.id}">Edit</button>
                            <button class="btn btn-sm btn-danger deleteBtn" data-id="${user.id}">Delete</button>
                        </td>
                    </tr>
                `);
            });
            const pagination = $('#pagination').empty();
            for (let i = 1; i <= res.totalPages; i++) {
                pagination.append(`
                    <li class="page-item ${i === res.page ? 'active' : ''}">
                        <a class="page-link" href="#" data-page="${i}">${i}</a>
                    </li>
                `);
            }
        });
    }

    $('#searchInput').on('input', function () {
        searchQuery = $(this).val();
        currentPage = 1;
        fetchUsers();
    });

    $('#pagination').on('click', '.page-link', function (e) {
        e.preventDefault();
        const page = parseInt($(this).data('page'));
        if (page !== currentPage) {
            currentPage = page;
            fetchUsers();
        }
    });

    $(document).on('click', '.sort', function (e) {
        e.preventDefault();
        const selectedSort = $(this).data('sort');

        if (sortBy === selectedSort) {
            sortDir = (sortDir === 'asc') ? 'desc' : 'asc';
        } else {
            sortBy = selectedSort;
            sortDir = 'asc';
        }

        currentPage = 1;
        fetchUsers();
    });


        $.get('session-check.php', function (res) {
            if (!res.logged_in) {
                $('#loginModal').modal('show');
            } else {
                $('#mainContent').show();
                $('#navbarUsername').text(res.user_name);
            }
        }, 'json');

        fetchUsers();

        function showTooltip(msg, type = 'success') {
            $('#tooltip')
                .removeClass('tooltip-success tooltip-error')
                .addClass(type === 'success' ? 'tooltip-success' : 'tooltip-error')
                .text(msg).fadeIn(200).delay(2000).fadeOut(400);
        }

        $('#loginForm').on('submit', function (e) {
            e.preventDefault();
        
            const email = $('#loginEmail').val().trim();
            const password = $('#loginPassword').val().trim();
        
            if (!email || !password) {
                $('#loginError').text('Both email and password are required.');
                return;
            }
            $.ajax({
                url: 'api.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ action: 'login', email, password }),
                success: function (res) {
                    if (res.status === 'success') {
                        $('#loginModal').modal('hide');
                        $('#mainContent').fadeIn();
                        $('#navbarUsername').text(res.name || 'Admin');
                        fetchUsers(); // your custom function to fetch all users
                    } else {
                        $('#loginError').text(res.message || 'Invalid credentials');
                    }
                },
                error: function () {
                    $('#loginError').text('Server error. Please try again.');
                }
            });
            
        });
        

        const formRules = {
            name: { required: true },
            phone: { required: true, minlength: 10, maxlength: 15 },
            email: { required: true, email: true },
            age: { required: true, number: true, min: 1 },
            salary: { required: true, number: true, min: 0 },
            address: { required: true },
            gender: { required: true }
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
            gender: 'Please select gender'
        };

        $('#addForm').on('submit', function (e) {
            e.preventDefault();
        
            // Clear previous errors
            $('#addForm label.text-danger.small').remove();
        
            // Extract form values manually
            const name = $('#addForm [name="name"]').val().trim();
            const email = $('#addForm [name="email"]').val().trim();
            const phone = $('#addForm [name="phone"]').val().trim();
            const password = $('#addForm [name="password"]').val().trim();
            const age = $('#addForm [name="age"]').val().trim();
            const address = $('#addForm [name="address"]').val().trim();
            const gender = $('#addForm [name="gender"]:checked').val();
        
            // Validate client-side (basic check)
            if (!name || !email || !phone || !password || !age || !address || !gender) {
                showTooltip('Please fill out all fields', 'error');
                return;
            }
        
            const photo = $('#addForm [name="profile_photo"]')[0].files[0];
        
            const formData = new FormData();
            formData.append('action', 'create');
            formData.append('name', name);
            formData.append('email', email);
            formData.append('phone', phone);
            formData.append('password', password);
            formData.append('age', age);
            formData.append('address', address);
            formData.append('gender', gender);
            if (photo) formData.append('profile_photo', photo);
        
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
                        fetchUsers();
                        showTooltip('User added!');
                    } else if (res.status === 'error' && res.field) {
                        $(`#addForm [name="${res.field}"]`).after(`<label class="text-danger small">${res.message}</label>`);
                    } else {
                        showTooltip('Failed to add user', 'error');
                    }
                },
                error() {
                    showTooltip('Server error occurred', 'error');
                }
            });
        });
        

    $('#userTable tbody').on('click', '.editBtn', function () {
        const id = $(this).closest('tr').data('id');
        if (!id) {
            showTooltip('Invalid user ID', 'error');
            return;
        }

        $.ajax({
            url: 'api.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ action: 'get_user', id }),
            success: function (user) {
                if (user && user.id) {
                    $('#edit-id').val(user.id);
                    $('#existing_photo').val(user.profile_photo || '');
                    $('#edit-name').val(user.name || '');
                    $('#edit-phone').val(user.phone || '');
                    $('#edit-email').val(user.email || '');
                    $('#edit-age').val(user.age || '');
                    $('#edit-salary').val(user.salary || '');
                    $('#edit-address').val(user.address || '');
                    $(`#editForm input[name="gender"][value="${user.gender}"]`).prop('checked', true);

                    const preview = $('#editPreview');
                    if (user.profile_photo) {
                        preview.attr('src', 'uploads/' + user.profile_photo).show();
                    } else {
                        preview.attr('src', '').hide();
                    }

                    $('#editModal').modal('show');
                } else {
                    showTooltip('User not found.', 'error');
                }
            },
            error: function () {
                showTooltip('Error fetching user data.', 'error');
            }
        });
    });
        

    $('#editForm').validate({
        rules: formRules,
        messages: formMessages,
        errorClass: 'text-danger small',
        submitHandler: function (form) {
            $('#editForm label.text-danger.small').remove();

            const id = $('#editForm [name="id"]').val().trim();
            const name = $('#editForm [name="name"]').val().trim();
            const email = $('#editForm [name="email"]').val().trim();
            const phone = $('#editForm [name="phone"]').val().trim();
            const password = $('#editForm [name="password"]').val().trim();
            const age = $('#editForm [name="age"]').val().trim();
            const address = $('#editForm [name="address"]').val().trim();
            const gender = $('#editForm [name="gender"]:checked').val();

            const photo = $('#editForm [name="profile_photo"]')[0].files[0];

            const formData = new FormData();
            formData.append('action', 'update');
            formData.append('id', id);
            formData.append('name', name);
            formData.append('email', email);
            formData.append('phone', phone);
            formData.append('password', password);
            formData.append('age', age);
            formData.append('address', address);
            formData.append('gender', gender);
            if (photo) formData.append('profile_photo', photo);

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
                        fetchUsers();
                        showTooltip('User updated!');
                    } else if (res.status === 'error' && res.field) {
                        $(`#editForm [name="${res.field}"]`).after(`<label class="text-danger small">${res.message}</label>`);
                    } else {
                        showTooltip('Failed to update', 'error');
                    }
                },
                error() {
                    showTooltip('Server error occurred', 'error');
                }
            });
        }
    });


        $('#userTable tbody').on('click', '.deleteBtn', function () {
            deleteId = $(this).closest('tr').data('id');
            $('#deleteModal').modal('show');
        });

        $('#confirmDeleteBtn').click(function () {
            if (!deleteId) {
                showTooltip('No user selected for deletion', 'error');
                return;
            }
        
            $.ajax({
                url: 'api.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ action: 'delete', id: deleteId }),
                success: function (res) {
                    if (res.status === 'success') {
                        $('#deleteModal').modal('hide');
                        fetchUsers(); // Refresh user list
                        showTooltip('User deleted successfully!', 'success');
                    } else {
                        showTooltip(res.message || 'Failed to delete user', 'error');
                    }
                },
                error: function () {
                    showTooltip('Server error occurred while deleting user', 'error');
                }
            });
            
        });
        

        $('#edit-photo').on('change', function (event) {
            $('#editPreview').attr('src', URL.createObjectURL(event.target.files[0]));
        });

        $('#logoutBtn').click(function (e) {
            e.preventDefault();
            $.ajax({
                url: 'api.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ action: 'logout' }),
                dataType: 'json',
                success() {
                    location.reload();
                }
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
                success(res) {
                    if (res.status === 'success') {
                        let msg = [];
                        if (res.inserted) msg.push(`${res.inserted} user(s) added`);
                        if (res.updated) msg.push(`${res.updated} user(s) updated`);
                        showTooltip(msg.join(' & '));
                        $('#importModal').modal('hide');
                        $('#importForm')[0].reset();
                        fetchUsers();
                    } else {
                        $('#importError').text(res.message || 'Unknown error');
                    }
                },
                error() {
                    $('#importError').text('Something went wrong!');
                }
            });
        });

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
        });

    });
