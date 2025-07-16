$(function () {
    let allUsers = [];
    let currentPage = 1;
    let rowsPerPage = 10;
    let sortColumn = 'id';
    let sortDirection = 'asc';
    let deleteId = null;

    function api(action, data = {}) {
        data.action = action;
        return fetch('api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        }).then(res => res.json());
    }

    function fetchUsers() {
        api('read').then(res => {
            allUsers = res.data || [];
            renderTable();
        });
    }

    function renderTable() {
        const query = $('#searchInput').val().toLowerCase();
        let filtered = allUsers.filter(user =>
            user.name.toLowerCase().includes(query) ||
            user.email.toLowerCase().includes(query) ||
            user.phone.toLowerCase().includes(query)
        );

        filtered.sort((a, b) => {
            let valA = a[sortColumn] ?? '';
            let valB = b[sortColumn] ?? '';

            if (typeof valA === 'string') valA = valA.toLowerCase();
            if (typeof valB === 'string') valB = valB.toLowerCase();

            if (valA < valB) return sortDirection === 'asc' ? -1 : 1;
            if (valA > valB) return sortDirection === 'asc' ? 1 : -1;
            return 0;
        });


        const start = (currentPage - 1) * rowsPerPage;
        const paginated = filtered.slice(start, start + rowsPerPage);

        let rows = '';
        paginated.forEach(user => {
            rows += `
            <tr data-id="${user.id}">
                <td>${user.id}</td>
                <td><img src="uploads/${user.profile_photo}" width="50" height ="50" class="rounded-circle"></td>
                <td>${user.name}</td>
                <td>${user.email}</td>
                <td>${user.phone}</td>
                <td>${user.gender}</td>
                <td>${user.age}</td>
                <td>${user.address}</td>
                <td>${user.created_at}</td>
                <td>${parseFloat(user.salary).toFixed(2)}</td>
                <td>
                    <button class="btn btn-sm btn-warning editBtn">Edit</button>
                    <button class="btn btn-sm btn-danger deleteBtn">Delete</button>
                </td>
            </tr>`;
        });

        $('#userTable tbody').html(rows);
        $('#tableInfo').text(`Showing ${start + 1} to ${Math.min(start + rowsPerPage, filtered.length)} of ${filtered.length} entries`);

        let pagination = '';
        const totalPages = Math.ceil(filtered.length / rowsPerPage);
        pagination += `<button class="btn btn-outline-secondary me-1" ${currentPage === 1 ? 'disabled' : ''} data-page="${currentPage - 1}">Prev</button>`;
        for (let i = 1; i <= totalPages; i++) {
            pagination += `<button class="btn ${i === currentPage ? 'btn-primary' : 'btn-outline-secondary'} me-1" data-page="${i}">${i}</button>`;
        }
        pagination += `<button class="btn btn-outline-secondary" ${currentPage === totalPages ? 'disabled' : ''} data-page="${currentPage + 1}">Next</button>`;
        $('#paginationControls').html(pagination);

        $('#userTable thead th[data-sort]').each(function () {
            const $th = $(this);
            const col = $th.data('sort');
            const icon = $th.find('i');

            if (col === sortColumn) {
                icon.removeClass('bi-caret-down-fill bi-caret-up-fill')
                    .addClass(sortDirection === 'asc' ? 'bi-caret-up-fill' : 'bi-caret-down-fill');
            } else {
                icon.removeClass('bi-caret-up-fill').addClass('bi-caret-down-fill');
            }
        });
    }

    $('#paginationControls').on('click', 'button[data-page]', function () {
        changePage(parseInt($(this).data('page')));
    });

    function changePage(page) {
        currentPage = page;
        renderTable();
    }

    function sortTable(column) {
        sortDirection = (sortColumn === column && sortDirection === 'asc') ? 'desc' : 'asc';
        sortColumn = column;
        renderTable();
    }

    $('#userTable thead').on('click', 'th[data-sort]', function () {
        sortTable($(this).data('sort'));
    });

    $('#searchInput').on('input', () => {
        currentPage = 1;
        renderTable();
    });

    $('#recordsPerPage').on('change', function () {
        rowsPerPage = parseInt($(this).val());
        currentPage = 1;
        renderTable();
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
        $('#loginError').text('');
        const email = $('input[name="email"]').val();
        const password = $('input[name="password"]').val();

        api('login', { email, password }).then(res => {
            if (res.status === 'success') {
                $('#loginModal').modal('hide');
                $('#mainContent').fadeIn();
                $('#navbarUsername').text(res.name || 'Admin');
                fetchUsers();
            } else {
                $('#loginError').text(res.message || 'Invalid credentials');
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

    $('#addForm').validate({
        rules: formRules,
        messages: formMessages,
        errorClass: 'text-danger small',
        submitHandler: function (form) {
            const rawData = Object.fromEntries(new FormData(form));
            rawData.action = 'create';
            const formData = new FormData();
            formData.append('data', JSON.stringify(rawData));

            const photo = $('#addForm [name="profile_photo"]')[0].files[0];
            if (photo) formData.append('profile_photo', photo);

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
                        fetchUsers();
                        showTooltip('User added!');
                    } else if (res.status === 'error' && res.field) {
                        $(`#addForm [name="${res.field}"]`).after(`<label class="text-danger small">${res.message}</label>`);
                    } else {
                        showTooltip('Failed to add user', 'error');
                    }
                }
            });
        }
    });

    $('#userTable tbody').on('click', '.editBtn', function () {
        const id = $(this).closest('tr').data('id');
        api('get_user', { id }).then(u => {
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
        });
    });

    $('#editForm').validate({
        rules: formRules,
        messages: formMessages,
        errorClass: 'text-danger small',
        submitHandler: function (form) {
            const rawData = Object.fromEntries(new FormData(form));
            rawData.action = 'update';
            const formData = new FormData();
            formData.append('data', JSON.stringify(rawData));

            const photo = $('#editForm [name="profile_photo"]')[0].files[0];
            if (photo) formData.append('profile_photo', photo);

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
                        fetchUsers();
                        showTooltip('User updated!');
                    } else if (res.status === 'error' && res.field) {
                        $(`#editForm [name="${res.field}"]`).after(`<label class="text-danger small">${res.message}</label>`);
                    } else {
                        showTooltip('Failed to update', 'error');
                    }
                }
            });
        }
    });

    $('#userTable tbody').on('click', '.deleteBtn', function () {
        deleteId = $(this).closest('tr').data('id');
        $('#deleteModal').modal('show');
    });

    $('#confirmDeleteBtn').click(function () {
        api('delete', { id: deleteId }).then(res => {
            if (res.status === 'success') {
                $('#deleteModal').modal('hide');
                fetchUsers();
                showTooltip('User deleted!', 'error');
            } else {
                showTooltip('Failed to delete user', 'error');
            }
        });
    });

    $('#edit-photo').on('change', function (event) {
        $('#editPreview').attr('src', URL.createObjectURL(event.target.files[0]));
    });

    $('#logoutBtn').click(function (e) {
        e.preventDefault();
        api('logout').then(() => location.reload());
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
    $('.photo-wrapper').on('click', function () {
        $('#edit-photo').trigger('click');
    });

});
