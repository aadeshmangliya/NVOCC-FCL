<?php
// Users Management Page (Admin Only)
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /login.php');
    exit;
}
?>

<div class="page-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">User Management</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="fas fa-plus"></i> Add New User
        </button>
    </div>
    <!-- Users List Container -->
    <div id="users-container">
        <div class="text-center p-5">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-3">Loading users...</p>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="add-user-form-element">
                    <div class="mb-3">
                        <label class="form-label">Username *</label>
                        <input type="text" class="form-control" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email *</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password *</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role *</label>
                        <select class="form-control" name="role" required>
                            <option value="">Select Role</option>
                            <option value="agent">Agent</option>
                            <option value="admin">Admin</option>
                            <option value="officestaff">System Staff</option>
                        </select>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="is_active" id="add_user_is_active" value="1" checked>
                        <label class="form-check-label" for="add_user_is_active">Active user</label>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="send_credentials" id="send_credentials" value="1" checked>
                        <label class="form-check-label" for="send_credentials">Send credentials email after create</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" id="addUserPreviewBtn" onclick="UsersPage.previewNewUserFromForm()">Preview Email</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="add-user-form-element" class="btn btn-primary">Create User</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="edit-user-form-element">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    <div class="mb-3">
                        <label class="form-label">Username *</label>
                        <input type="text" class="form-control" name="username" id="edit_username" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email *</label>
                        <input type="email" class="form-control" name="email" id="edit_email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password (leave blank to keep unchanged)</label>
                        <input type="password" class="form-control" name="password" id="edit_password" placeholder="Enter new password to change">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role *</label>
                        <select class="form-control" name="role" id="edit_role" required>
                            <option value="agent">Agent</option>
                            <option value="admin">Admin</option>
                            <option value="officestaff">System Staff</option>
                        </select>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="is_active" id="edit_user_is_active" value="1">
                        <label class="form-check-label" for="edit_user_is_active">Active user</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="edit-user-form-element" class="btn btn-primary">Update User</button>
            </div>
        </div>
    </div>
</div>

<!-- Email Preview Modal -->
<div class="modal fade" id="emailPreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Credential Email Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Subject</label>
                    <input type="text" class="form-control" id="emailPreviewSubject" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">Preview</label>
                    <div id="emailPreviewBody" class="border rounded p-3" style="min-height:300px; background:#f8f9fa;"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="../assets/js/users.js"></script>