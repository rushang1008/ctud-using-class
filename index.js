$(function () {
    let allUsers = [];
    let currentPage = 1;
    let rowsPerPage = 10;
    let sortColumn = 'id';
    let sortDirection = 'asc';
    let deleteId = null;

   function fetchUsers() {
    $.ajax({
        url: 'api.php',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ action: 'read' }),
        dataType: 'json',
        success(res) {
            if (res.status === 'success') {
                allUsers = res.data || [];
                renderTable();
            } else {
                console.error('Fetch failed:', res.message);
            }
        },
        error(err) {
            console.error('Server error:', err);
        }
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
    
        const tbody = $('#userTable tbody');
        tbody.find('tr:not(#rowTemplate)').remove(); // Clear all except the template
        
        paginated.forEach(user => {
            const $row = $('#rowTemplate').clone().removeAttr('id').removeClass('d-none');
        
            $row.attr('data-id', user.id);
            $row.find('.user-id').text(user.id);
            $row.find('.user-photo').attr('src', `uploads/${user.profile_photo}?t=${Date.now()}`);
            $row.find('.user-name').text(user.name);
            $row.find('.user-email').text(user.email);
            $row.find('.user-phone').text(user.phone);
            $row.find('.user-gender').text(user.gender);
            $row.find('.user-age').text(user.age);
            $row.find('.user-address').text(user.address);
            $row.find('.user-created').text(user.created_at);
            $row.find('.user-salary').text(parseFloat(user.salary).toFixed(2));
        
            tbody.append($row);
        });
        
        $('#tableInfo').text(`Showing ${start + 1} to ${Math.min(start + rowsPerPage, filtered.length)} of ${filtered.length} entries`);
    
        const totalPages = Math.ceil(filtered.length / rowsPerPage);
    
        // Handle Pagination Buttons
        $('#prevPageBtn').prop('disabled', currentPage === 1).data('page', currentPage - 1);
        $('#nextPageBtn').prop('disabled', currentPage === totalPages).data('page', currentPage + 1);
    
        // Render Page Numbers
        let pageButtons = '';
        for (let i = 1; i <= totalPages; i++) {
            pageButtons += `
                <button class="btn ${i === currentPage ? 'btn-primary' : 'btn-outline-secondary'} page-btn" data-page="${i}">
                    ${i}
                </button>
            `;
        }
        $('#pageNumbers').html(pageButtons);
    
        // Update sorting icons
        $('#userTable thead th[data-sort]').each(function () {
            const $th = $(this);
            const col = $th.data('sort');
            const icon = $th.find('i');
    
            if (col === sortColumn) {
                icon.removeClass('bi-caret-down-fill bi-caret-up-fill')
                    .addClass(sortDirection === 'asc' ? 'bi-caret-up-fill' : 'bi-caret-down-fill');
            } else {
                icon.removeClass('bi-caret-up-fill bi-caret-down-fill').addClass('bi-caret-down-fill');
            }
        });
    }
    
    // Place this OUTSIDE renderTable:
    $(document).on('click', '#paginationControls .page-btn, #prevPageBtn, #nextPageBtn', function () {
        const targetPage = parseInt($(this).data('page'));
        if (!isNaN(targetPage)) {
            currentPage = targetPage;
            renderTable();
        }
    });
    

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
            const formDataObj = new FormData(form);
            const rawData = Object.fromEntries(formDataObj.entries());
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
