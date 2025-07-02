<?php
require_once "config.php";
require_once "User.php";
$user = new User($conn);
$users = $user->readAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>AJAX DataTable CRUD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/jquery.validate.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/additional-methods.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <style>
        #tooltip {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            padding: 10px 20px;
            border-radius: 8px;
            display: none;
            font-size: 0.9rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .tooltip-success {
            background: #d1e7dd;
            color: #0f5132;
        }

        .tooltip-error {
            background: #f8d7da;
            color: #842029;
        }

        .photo-wrapper {
            position: relative;
            display: inline-block;
            cursor: pointer;
        }

        .photo-wrapper:hover .edit-icon {
            opacity: 1;
        }

        .edit-icon {
            position: absolute;
            bottom: 0;
            right: 0;
            background: #000000aa;
            color: #fff;
            border-radius: 50%;
            padding: 5px;
            font-size: 14px;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .photo-wrapper img {
            object-fit: cover;
            border: 2px solid #ddd;
        }

        .photo-wrapper input[type="file"] {
            display: none;
        }
    </style>
</head>

<body class="bg-light">
    <div id="tooltip"></div>
    <div class="container mt-5">
        <div class="d-flex justify-content-between mb-3">
            <h2>User Management</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">+ Add User</button>
        </div>
        <table id="userTable" class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Gender</th>
                    <th>Salary</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr data-id="<?= $u['id'] ?>">
                        <td><?= htmlspecialchars($u['id']) ?></td>
                        <td><img src="uploads/<?= htmlspecialchars($u['profile_photo']) ?>" width="50" height="50"
                                class="rounded-circle"></td>
                        <td><?= htmlspecialchars($u['name']) ?></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td><?= htmlspecialchars($u['phone']) ?></td>
                        <td><?= htmlspecialchars($u['gender']) ?></td>
                        <td><?= number_format($u['salary'], 2) ?></td>
                        <td>
                            <button class="btn btn-sm btn-warning editBtn">Edit</button>
                            <button class="btn btn-sm btn-danger deleteBtn">Delete</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- ADD Modal -->
    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <form id="addForm" class="modal-content" enctype="multipart/form-data" novalidate>
                <div class="modal-header bg-primary text-white">
                    <h5>Add User</h5><button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body row g-3">
                    <div class="col-md-6"><input name="name" class="form-control" placeholder="Name" required></div>
                    <div class="col-md-6"><input name="phone" class="form-control" placeholder="Phone" required></div>
                    <div class="col-md-6"><input type="email" name="email" class="form-control" placeholder="Email"
                            required></div>
                    <div class="col-md-6"><input type="password" name="password" class="form-control"
                            placeholder="Password" required></div>
                    <div class="col-md-6"><input type="number" name="age" class="form-control" placeholder="Age"
                            required></div>
                    <div class="col-md-6"><input type="number" name="salary" class="form-control" placeholder="Salary"
                            step="0.01" required></div>
                    <div class="col-md-6"><input type="file" name="profile_photo" class="form-control" accept="image/*"
                            required></div>
                    <div class="col-12"><textarea name="address" class="form-control" placeholder="Address"
                            required></textarea></div>
                    <div class="col-12">
                        <label>Gender</label>
                        <div class="form-check form-check-inline"><input class="form-check-input" type="radio"
                                name="gender" value="Male" required>Male</div>
                        <div class="form-check form-check-inline"><input class="form-check-input" type="radio"
                                name="gender" value="Female" required>Female</div>
                        <div class="form-check form-check-inline"><input class="form-check-input" type="radio"
                                name="gender" value="Other" required>Other</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <form id="editForm" class="modal-content" enctype="multipart/form-data" novalidate>
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <input type="hidden" name="id" id="edit-id">
                    <input type="hidden" name="existing_photo" id="existing_photo">

                    <div class="row g-3">
                        <!-- Profile Picture with Hover Upload -->
                        <div class="col-md-6 text-center">
                            <label class="form-label d-block">Profile Photo</label>
                            <div class="photo-wrapper mx-auto">
                                <img id="editPreview" src="" width="100" height="100" class="rounded-circle">
                                <span class="edit-icon">
                                    <i class="bi bi-pencil-fill"></i> <!-- Bootstrap icon -->
                                </span>
                                <input type="file" name="profile_photo" id="edit-photo" accept="image/*"
                                    onchange="previewEditPhoto(event)">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label for="edit-name" class="form-label">Name</label>
                            <input type="text" name="name" id="edit-name" class="form-control" required>
                        </div>

                        <div class="col-md-6">
                            <label for="edit-phone" class="form-label">Phone</label>
                            <input type="text" name="phone" id="edit-phone" class="form-control" required>
                        </div>

                        <div class="col-md-6">
                            <label for="edit-email" class="form-label">Email</label>
                            <input type="email" name="email" id="edit-email" class="form-control" required>
                        </div>

                        <div class="col-md-6">
                            <label for="edit-age" class="form-label">Age</label>
                            <input type="number" name="age" id="edit-age" class="form-control" required>
                        </div>

                        <div class="col-md-6">
                            <label for="edit-salary" class="form-label">Salary</label>
                            <input type="number" name="salary" id="edit-salary" step="0.01" class="form-control"
                                required>
                        </div>

                        <div class="col-md-12">
                            <label for="edit-address" class="form-label">Address</label>
                            <textarea name="address" id="edit-address" class="form-control" required></textarea>
                        </div>

                        <div class="col-12">
                            <label class="form-label d-block">Gender</label>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" name="gender" type="radio" value="Male"
                                    id="edit-gender-male">
                                <label class="form-check-label" for="edit-gender-male">Male</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" name="gender" type="radio" value="Female"
                                    id="edit-gender-female">
                                <label class="form-check-label" for="edit-gender-female">Female</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" name="gender" type="radio" value="Other"
                                    id="edit-gender-other">
                                <label class="form-check-label" for="edit-gender-other">Other</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Update</button>
                </div>
            </form>
        </div>
    </div>

    <!-- DELETE Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5>Confirm Delete</h5><button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">Are you sure?</div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button id="confirmDeleteBtn" class="btn btn-danger">Delete</button>
                </div>
            </div>
        </div>
    </div>
    <script>
        function previewEditPhoto(event) {
            document.getElementById('editPreview').src = URL.createObjectURL(event.target.files[0]);
        }
        $(function () {
            const table = $('#userTable').DataTable();
            let deleteId = null;
            function showTooltip(msg, type = 'success') {
                $('#tooltip').removeClass('tooltip-success tooltip-error')
                    .addClass(type == 'success' ? 'tooltip-success' : 'tooltip-error')
                    .text(msg).fadeIn(200).delay(2000).fadeOut(400);
            }
            // Add Form Validation
            $('#addForm').validate({
                rules: {
                    name: { required: true },
                    phone: { required: true, minlength: 10, maxlength: 15 },
                    email: { required: true, email: true },
                    password: { required: true, minlength: 6 },
                    age: { required: true, number: true, min: 1 },
                    salary: { required: true, number: true, min: 0 },
                    address: { required: true },
                    gender: { required: true },
                    profile_photo: { required: true, extension: "jpg|jpeg|png|gif" }
                },
                messages: {
                    name: "Please enter name",
                    phone: {
                        required: "Please enter phone number",
                        minlength: "Minimum 10 digits",
                        maxlength: "Maximum 15 digits"
                    },
                    email: {
                        required: "Email is required",
                        email: "Enter valid email"
                    },
                    password: {
                        required: "Password is required",
                        minlength: "At least 6 characters"
                    },
                    age: {
                        required: "Please enter age",
                        number: "Enter numeric value",
                        min: "Age must be at least 1"
                    },
                    salary: {
                        required: "Please enter salary",
                        number: "Enter numeric value",
                        min: "Salary must be non-negative"
                    },
                    address: "Please enter address",
                    gender: "Please select gender",
                    profile_photo: {
                        required: "Please upload a photo",
                        extension: "Only image files allowed"
                    }
                },
                errorClass: "text-danger small",
                submitHandler: function (form) {
                    let formData = new FormData(form);
                    formData.append('create', true);
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
                                    '',
                                    `<img src="uploads/${res.newPhoto}" width="50" height="50" class="rounded-circle">`,
                                    formData.get('name'),
                                    formData.get('email'),
                                    formData.get('phone'),
                                    formData.get('gender'),
                                    parseFloat(formData.get('salary')).toFixed(2),
                                    `<button class="btn btn-sm btn-warning editBtn">Edit</button>
                         <button class="btn btn-sm btn-danger deleteBtn">Delete</button>`
                                ]).draw(false).node().setAttribute('data-id', res.newPhoto);
                                showTooltip('User added!');
                                form.reset();
                            } else if (res.errors) {
                                if (res.errors.email) {
                                    $('[name="email"]').after(`<label class="text-danger small">${res.errors.email}</label>`);
                                }
                                if (res.errors.phone) {
                                    $('[name="phone"]').after(`<label class="text-danger small">${res.errors.phone}</label>`);
                                }
                            } else {
                                showTooltip('Failed to add', 'error');
                            }
                        }
                    });
                }
            });

            $('#userTable tbody').on('click', '.editBtn', function () {
                const row = $(this).closest('tr');
                const id = row.data('id');
                $.get('api.php', { get_user_id: id }, function (u) { // updated
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
            // Edit Form Validation
            $('#editForm').validate({
                rules: {
                    name: { required: true },
                    phone: { required: true, minlength: 10, maxlength: 15 },
                    email: { required: true, email: true },
                    age: { required: true, number: true, min: 1 },
                    salary: { required: true, number: true, min: 0 },
                    address: { required: true },
                    gender: { required: true },
                    profile_photo: {
                        extension: "jpg|jpeg|png|gif"
                    }
                },
                messages: {
                    name: "Please enter name",
                    phone: {
                        required: "Please enter phone number",
                        minlength: "Minimum 10 digits",
                        maxlength: "Maximum 15 digits"
                    },
                    email: {
                        required: "Email is required",
                        email: "Enter valid email"
                    },
                    age: {
                        required: "Please enter age",
                        number: "Enter numeric value",
                        min: "Age must be at least 1"
                    },
                    salary: {
                        required: "Please enter salary",
                        number: "Enter numeric value",
                        min: "Salary must be non-negative"
                    },
                    address: "Please enter address",
                    gender: "Please select gender",
                    profile_photo: {
                        extension: "Only image files allowed"
                    }
                },
                errorClass: "text-danger small",
                submitHandler: function (form) {
                    let formData = new FormData(form);
                    formData.append('update', true);

                    // Clear old inline error messages
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
                                    parseFloat(formData.get('salary')).toFixed(2),
                                    row.find('td').eq(7).html()
                                ]).draw(false);

                                showTooltip('User updated!');
                            } else if (res.status === 'error' && res.field && res.message) {
                                // Show validation message below the specific field
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

            $('#userTable tbody').on('click', '.deleteBtn', function () {
                deleteId = $(this).closest('tr').data('id');
                $('#deleteModal').modal('show');
            });

            $('#confirmDeleteBtn').click(function () {
                $.post('api.php', { delete: true, id: deleteId }, function (res) { // updated
                    if (res.status == 'success') {
                        let row = $(`#userTable tr[data-id="${deleteId}"]`);
                        table.row(row).remove().draw(false);
                        $('#deleteModal').modal('hide');
                        showTooltip('User deleted!', 'error');
                    } else showTooltip('Failed to delete', 'error');
                }, 'json');
            });
        });
        function previewEditPhoto(event) {
            const output = document.getElementById('editPreview');
            output.src = URL.createObjectURL(event.target.files[0]);
        }
        $(document).on('click', '.photo-wrapper img, .edit-icon', function (e) {
            e.stopPropagation(); // prevent bubbling
            $('#edit-photo').trigger('click');
        });


    </script>
</body>

</html>