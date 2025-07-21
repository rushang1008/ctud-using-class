<?php
session_start();

require_once "config.php";
require_once "User.php";
$user = new User($conn);
$users = $user->readAll();

 include_once "includes/header.php"; ?>
<!-- Login Modal -->
<div class="modal fade" id="loginModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <form id="loginForm" class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title">Admin Login</h5>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <input type="email" name="email" id="loginEmail" class="form-control" placeholder="Email" required>
                </div>
                <div class="mb-3">
                    <input type="password" name="password" id="loginPassword" class="form-control"
                        placeholder="Password" required>
                </div>
                <div id="loginError" class="text-danger small"></div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-dark w-100">Login</button>
            </div>
        </form>
    </div>
</div>

<!-- Main Content -->
<div id="mainContent" style="display: none;">
    <!-- Page Content -->
    <div class="web container-fluid  p-5">
        <div class="d-flex justify-content-between mb-3">
            <h2>User Management <br><span>
                    <h3 class="text-white bg-dark d-inline-block px-2 py-1 mt-1">By MR_KAVA</h3>
                </span></h2>
            <div>
                <a href="sample-excel.php" class="btn btn-outline-dark me-2">
                    <i class="bi bi-file-earmark-excel"></i> Sample Excel
                </a>
                <button class="btn btn-outline-secondary me-2" data-bs-toggle="modal" data-bs-target="#importModal">
                    <i class="bi bi-download"></i> Import Excel
                </button>
                <a href="export-excel.php" class="btn btn-outline-success me-2">
                    <i class="bi bi-upload"></i> Export Excel
                </a>
                <a href="export-pdf.php" target="_blank" class="btn btn-outline-danger me-2">
                    <i class="bi bi-file-earmark-pdf-fill"></i> Export as PDF
                </a>

                <button class="btn btn-outline-primary me-2" data-bs-toggle="modal" data-bs-target="#addModal">
                    + Add User
                </button>
            </div>
        </div>

        <!-- Controls -->
        <div class="row mb-3 d-flex justify-content-between ">
            <div class="col-md-1">
                <select id="recordsPerPage" class="form-select">
                    <option value="5">5</option>
                    <option value="10" selected>10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
            </div>
            <div class="col-md-2">
                <input type="text" id="searchInput" class="form-control" placeholder="Search users...">
            </div>
        </div>

        <!-- DataTable -->
        <div class="table-responsive">
            <table id="userTable" class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th class="sort" data-sort="id">ID </th>
                        <th>Photo</th>
                        <th class="sort" data-sort="name">Name </th>
                        <th class="sort" data-sort="email">Email </th>
                        <th class="sort" data-sort="phone">Phone </th>
                        <th class="sort" data-sort="gender">Gender </th>
                        <th class="sort" data-sort="age">Age </th>
                        <th class="sort" data-sort="address">Address </th>
                        <th class="sort" data-sort="created_at">Created </th>
                        <th class="sort" data-sort="salary">Salary </th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr id="rowTemplate" class="d-none">
                        <td class="user-id"></td>
                        <td><img src="" width="50" height="50" class="rounded-circle user-photo"></td>
                        <td class="user-name"></td>
                        <td class="user-email"></td>
                        <td class="user-phone"></td>
                        <td class="user-gender"></td>
                        <td class="user-age"></td>
                        <td class="user-address"></td>
                        <td class="user-created"></td>
                        <td class="user-salary"></td>
                        <td>
                            <button class="btn btn-sm btn-warning editBtn">Edit</button>
                            <button class="btn btn-sm btn-danger deleteBtn">Delete</button>
                        </td>
                    </tr>
                </tbody>

            </table>
        </div>
        <div class="d-flex justify-content-between">
            <div id="tableInfo" class="text-muted small"></div>
            <!-- Pagination info -->
            <div id="paginationControls" class="d-flex align-items-center gap-2 mt-2">
                <button id="prevPageBtn" class="btn btn-outline-secondary border-0">Previous</button>
                <div id="pageNumbers" class="btn-group btn-secondary"></div>
                <button id="nextPageBtn" class="btn btn-outline-secondary border-0">Next</button>
            </div>
        </div>
    </div>

    <!-- Add Modal -->
    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <form id="addForm" class="modal-content" enctype="multipart/form-data" novalidate>
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Add User</h5>
                    <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body row g-3">
                    <div class="col-md-6"><input name="name" class="form-control" placeholder="Name" required></div>
                    <div class="col-md-6"><input name="phone" class="form-control" placeholder="Phone" required>
                    </div>
                    <div class="col-md-6"><input type="email" name="email" class="form-control" placeholder="Email"
                            required></div>
                    <div class="col-md-6"><input type="number" name="age" class="form-control" placeholder="Age"
                            required></div>
                    <div class="col-md-6"><input type="number" name="salary" class="form-control" placeholder="Salary"
                            step="0.01" required></div>
                    <div class="col-md-6"><input type="file" name="profile_photo" class="form-control" accept="image/*"
                            required></div>
                    <div class="col-6"><textarea name="address" class="form-control" placeholder="Address"
                            required></textarea></div>

                    <div class="col-12 mb-3">
                        <label class="form-label d-block">Gender</label>
                        <div id="gender-group" class="d-flex gap-3 align-items-center">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="gender" value="Male"> Male
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="gender" value="Female"> Female
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="gender" value="Other"> Other
                            </div>
                        </div>
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
                        <div class="col-md-6 text-center">
                            <label for="edit-photo" class="photo-wrapper" id="edit-photo-wrapper">
                                <img id="editPreview" src="" alt="Profile" />
                                <div class="edit-icon">
                                    <i
                                        class="bi bi-pencil-fill edit-icon d-flex justify-content-center align-items-center"></i>

                                </div>
                            </label>
                            <input type="file" id="edit-photo" name="profile_photo" class="d-none" />
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" id="edit-name" class="form-control" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" id="edit-phone" class="form-control" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" id="edit-email" class="form-control" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Age</label>
                            <input type="number" name="age" id="edit-age" class="form-control" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Salary</label>
                            <input type="number" name="salary" id="edit-salary" step="0.01" class="form-control"
                                required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Address</label>
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

    <!-- Delete Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">Are you sure you want to delete this user?</div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button id="confirmDeleteBtn" class="btn btn-danger">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Import Modal -->
    <div class="modal fade" id="importModal" tabindex="-1">
        <div class="modal-dialog">
            <form id="importForm" class="modal-content" enctype="multipart/form-data">
                <div class="modal-header bg-secondary text-white">
                    <h5 class="modal-title">Import Users from Excel</h5>
                    <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="file" name="excel_file" class="form-control" accept=".xlsx,.xls">
                    <label id="importError" class="text-danger small mt-2 d-block"></label>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success text-white">Import</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php
  include_once "includes/footer.php"  ?>