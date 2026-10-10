// ==================
// Users Management Page JS
// ==================

const UsersPage = {
    currentUserId: null,
    
    init: function () {
        // Check if container exists
        const container = document.getElementById('users-container');
        if (!container) {
            return;
        }

        // Check if App.api exists
        if (typeof App === 'undefined' || !App.api) {
            container.innerHTML = '<div class="alert alert-danger">Application not loaded properly. Please refresh.</div>';
            return;
        }

        this.loadUsers();
        this.setupAddForm();
        this.setupEditForm();
    },
    
    loadUsers: function () {
        const container = document.getElementById('users-container');
        if (!container) return;
        
        container.innerHTML = `
            <div class="text-center p-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-3">Loading users...</p>
            </div>`;
        
        App.api.get('users.php')
            .then(response => {
                if (response && response.success) {
                    this.displayUsers(response.data || []);
                } else {
                    container.innerHTML = `<div class="alert alert-warning">No users found.</div>`;
                }
            })
            .catch(error => {
                container.innerHTML = `<div class="alert alert-danger">Failed to load users: ${error.message}</div>`;
            });
    },
    
    displayUsers: function (users) {
        const container = document.getElementById('users-container');
        if (!container) return;
        
        if (!users || users.length === 0) {
            container.innerHTML = `<div class="alert alert-info">No users found. Click "Add New User" to create one.</div>`;
            return;
        }
        
        let html = `
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">System Users (${users.length} total)</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Created Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
        `;
        
        users.forEach(user => {
            let roleBadge = '';
            if (user.role === 'admin') {
                roleBadge = '<span class="badge bg-danger">Admin</span>';
            } else if (user.role === 'agent') {
                roleBadge = '<span class="badge bg-primary">Agent</span>';
            } else if (user.role === 'officestaff') {
                roleBadge = '<span class="badge bg-info">System Staff</span>';
            } else {
                roleBadge = `<span class="badge bg-secondary">${this.escapeHtml(user.role)}</span>`;
            }
            
            const statusBadge = user.is_active ?
                '<span class="badge bg-success">Active</span>' :
                '<span class="badge bg-secondary">Inactive</span>';

            html += `
                <tr data-user-id="${user.id}">
                    <td>${user.id}</td>
                    <td><strong>${this.escapeHtml(user.username)}</strong></td>
                    <td>${this.escapeHtml(user.email)}</td>
                    <td>${roleBadge}</td>
                    <td>${statusBadge}</td>
                    <td>${this.formatDate(user.created_at)}</td>
                    <td>
                        <button class="btn btn-sm btn-secondary me-1" onclick="UsersPage.previewCredentials(${user.id})">
                            <i class="fas fa-eye"></i> Preview
                        </button>
                        <button class="btn btn-sm btn-warning me-1" onclick="UsersPage.resendCredentials(${user.id})">
                            <i class="fas fa-envelope"></i> Resend
                        </button>
                        <button class="btn btn-sm btn-info me-1" onclick="UsersPage.editUser(${user.id})">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="UsersPage.deleteUser(${user.id}, '${this.escapeHtml(user.username)}')">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </td>
                </tr>
            `;
        });
        
        html += `
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;
        
        container.innerHTML = html;
    },
    
    setupAddForm: function () {
        const form = document.getElementById('add-user-form-element');
        if (!form) {
            return;
        }
        
        // Remove existing listener to avoid duplicates
        const newForm = form.cloneNode(true);
        form.parentNode.replaceChild(newForm, form);
        
        newForm.addEventListener('submit', (e) => {
            e.preventDefault();
            
            const formData = new FormData(newForm);
            const data = {
                username: formData.get('username'),
                email: formData.get('email'),
                password: formData.get('password'),
                role: formData.get('role'),
                is_active: formData.get('is_active') === '1',
                send_credentials: formData.get('send_credentials') === '1'
            };
            
            // Validate
            if (!data.username || !data.email || !data.password || !data.role) {
                if (typeof App !== 'undefined' && App.notify) {
                    App.notify.error('Please fill all required fields');
                } else {
                    alert('Please fill all required fields');
                }
                return;
            }
            
            // Disable submit button
            const submitBtn = newForm.querySelector('button[type="submit"]') || newForm.parentElement?.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Creating...';
            }
            
            App.api.post('users.php', data)
                .then(res => {
                    if (res.success) {
                        if (typeof App !== 'undefined' && App.notify) {
                            App.notify.success('User created successfully!');
                        } else {
                            alert('User created successfully!');
                        }
                        newForm.reset();
                        // Close modal
                        const modal = bootstrap.Modal.getInstance(document.getElementById('addUserModal'));
                        if (modal) modal.hide();
                        this.loadUsers();
                    } else {
                        if (typeof App !== 'undefined' && App.notify) {
                            App.notify.error(res.message || 'Failed to create user');
                        } else {
                            alert(res.message || 'Failed to create user');
                        }
                    }
                })
                .catch(err => {
                    if (typeof App !== 'undefined' && App.notify) {
                        App.notify.error('Server error while creating user');
                    } else {
                        alert('Server error while creating user');
                    }
                })
                .finally(() => {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = 'Create User';
                    }
                });
        });
        
        // Attach preview button handler if present
        const previewBtn = document.getElementById('addUserPreviewBtn');
        if (previewBtn) {
            previewBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.previewNewUserFromForm();
            });
        }
    },

    previewNewUserFromForm: function () {
        const form = document.getElementById('add-user-form-element');
        if (!form) return;
        const fd = new FormData(form);
        const username = fd.get('username') || '';
        const email = fd.get('email') || '';
        const password = fd.get('password') || '';

        if (!username || !email) {
            if (typeof App !== 'undefined' && App.notify) {
                App.notify.error('Please enter username and email to preview the email');
            } else {
                alert('Please enter username and email to preview the email');
            }
            return;
        }

        App.api.post('users.php', { action: 'preview_credentials', username, email, password })
            .then(res => {
                if (res && res.success) {
                    document.getElementById('emailPreviewSubject').value = res.data.subject || '';
                    document.getElementById('emailPreviewBody').innerHTML = res.data.html || '';
                    const modal = new bootstrap.Modal(document.getElementById('emailPreviewModal'));
                    modal.show();
                } else {
                    if (typeof App !== 'undefined' && App.notify) App.notify.error(res.message || 'Unable to generate preview');
                    else alert(res.message || 'Unable to generate preview');
                }
            })
            .catch(() => {
                if (typeof App !== 'undefined' && App.notify) App.notify.error('Server error while generating preview');
                else alert('Server error while generating preview');
            });
    },
    
    editUser: function (id) {
        this.currentUserId = id;
        
        App.api.get(`users.php/${id}`)
            .then(res => {
                
                if (res.success) {
                    const user = res.data;
                    this.populateEditForm(user);
                    
                    // Show modal
                    const modal = new bootstrap.Modal(document.getElementById('editUserModal'));
                    modal.show();
                } else {
                    if (typeof App !== 'undefined' && App.notify) {
                        App.notify.error('Failed to load user details');
                    } else {
                        alert('Failed to load user details');
                    }
                }
            })
            .catch(err => {
                if (typeof App !== 'undefined' && App.notify) {
                    App.notify.error('Error fetching user');
                } else {
                    alert('Error fetching user');
                }
            });
    },
    
    populateEditForm: function (user) {
        document.getElementById('edit_user_id').value = user.id;
        document.getElementById('edit_username').value = user.username;
        document.getElementById('edit_email').value = user.email;
        document.getElementById('edit_role').value = user.role;
        document.getElementById('edit_password').value = '';
        document.getElementById('edit_user_is_active').checked = !!user.is_active;
    },

    previewCredentials: function (id) {
        App.api.get(`users.php/${id}`, { action: 'preview_credentials' })
            .then(res => {
                if (res.success) {
                    document.getElementById('emailPreviewSubject').value = res.data.subject || '';
                    document.getElementById('emailPreviewBody').innerHTML = res.data.html || '';
                    const modal = new bootstrap.Modal(document.getElementById('emailPreviewModal'));
                    modal.show();
                } else {
                    if (typeof App !== 'undefined' && App.notify) {
                        App.notify.error(res.message || 'Unable to load preview');
                    } else {
                        alert(res.message || 'Unable to load preview');
                    }
                }
            })
            .catch(() => {
                if (typeof App !== 'undefined' && App.notify) {
                    App.notify.error('Server error while loading preview');
                } else {
                    alert('Server error while loading preview');
                }
            });
    },

    resendCredentials: function (id) {
        if (!confirm('Send new credentials email to this user?')) {
            return;
        }

        App.api.post('users.php', { action: 'send_credentials', id })
            .then(res => {
                if (res.success) {
                    if (typeof App !== 'undefined' && App.notify) {
                        App.notify.success(res.message || 'Credentials resent successfully');
                    } else {
                        alert(res.message || 'Credentials resent successfully');
                    }
                } else {
                    if (typeof App !== 'undefined' && App.notify) {
                        App.notify.error(res.message || 'Failed to resend credentials');
                    } else {
                        alert(res.message || 'Failed to resend credentials');
                    }
                }
            })
            .catch(() => {
                if (typeof App !== 'undefined' && App.notify) {
                    App.notify.error('Server error while resending credentials');
                } else {
                    alert('Server error while resending credentials');
                }
            });
    },

    setupEditForm: function () {
        const form = document.getElementById('edit-user-form-element');
        if (!form) {
            return;
        }
        // Remove existing listener
        const newForm = form.cloneNode(true);
        form.parentNode.replaceChild(newForm, form);
        
        newForm.addEventListener('submit', (e) => {
            e.preventDefault();
            
            const formData = new FormData(newForm);
            const userId = formData.get('user_id');
            
            // Build data object
            const data = {
                username: formData.get('username'),
                email: formData.get('email'),
                role: formData.get('role'),
                is_active: formData.get('is_active') === '1'
            };
            if (formData.get('password')) data.password = formData.get('password');
            
            // Disable submit button
            const submitBtn = newForm.querySelector('button[type="submit"]') || newForm.parentElement?.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Updating...';
            }
            
            App.api.put(`users.php/${userId}`, data)
                .then(res => {
                    if (res.success) {
                        if (typeof App !== 'undefined' && App.notify) {
                            App.notify.success('User updated successfully!');
                        } else {
                            alert('User updated successfully!');
                        }
                        const modal = bootstrap.Modal.getInstance(document.getElementById('editUserModal'));
                        if (modal) modal.hide();
                        this.loadUsers();
                    } else {
                        if (typeof App !== 'undefined' && App.notify) {
                            App.notify.error(res.message || 'Failed to update user');
                        } else {
                            alert(res.message || 'Failed to update user');
                        }
                    }
                })
                .catch(err => {
                    if (typeof App !== 'undefined' && App.notify) {
                        App.notify.error('Server error while updating user');
                    } else {
                        alert('Server error while updating user');
                    }
                })
                .finally(() => {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = 'Update User';
                    }
                });
        });
    },
    
    deleteUser: function (id, username) {
        if (!confirm(`Are you sure you want to delete user "${username}"? This action cannot be undone.`)) {
            return;
        }
        
        App.api.delete(`users.php/${id}`)
            .then(res => {
                if (res.success) {
                    if (typeof App !== 'undefined' && App.notify) {
                        App.notify.success('User deleted successfully!');
                    } else {
                        alert('User deleted successfully!');
                    }
                    this.loadUsers();
                } else {
                    if (typeof App !== 'undefined' && App.notify) {
                        App.notify.error(res.message || 'Failed to delete user');
                    } else {
                        alert(res.message || 'Failed to delete user');
                    }
                }
            })
            .catch(err => {
                if (typeof App !== 'undefined' && App.notify) {
                    App.notify.error('Server error while deleting user');
                } else {
                    alert('Server error while deleting user');
                }
            });
    },
    
    formatDate: function (dateString) {
        if (!dateString) return 'N/A';
        try {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-PK', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        } catch(e) {
            return dateString;
        }
    },
    
    escapeHtml: function (text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
};

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof UsersPage !== 'undefined') {
            UsersPage.init();
        }
    });
} else {
    if (typeof UsersPage !== 'undefined') {
        UsersPage.init();
    }
}