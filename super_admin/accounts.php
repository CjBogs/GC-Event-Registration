<?php
// Assume $conn is your mysqli connection from '../config.php'

$allUsers = $conn->query("SELECT id, first_name, last_name, email, status, role, created_at FROM users WHERE role != 'super_admin' ORDER BY created_at DESC");
?>
<div class="max-w-4xl mx-auto">
    <div class="text-center mb-6">
        <h2 class="text-xl font-semibold text-[#1D503A]">List of Accounts</h2>
        <p class="text-lg text-[#4A5D4C] border-b-2 pb-2 border-[#1D503A]">
            View, manage, and update account information
        </p>
    </div>
</div>


<div class="overflow-x-auto bg-white rounded-xl shadow border mx-auto" style="border-color: #1D503A; max-width: 1000px;">
    <table class="w-full text-sm text-left text-gray-700">
        <thead class="bg-[#E5E1DB] border-b text-xs text-[#1D503A] uppercase font-semibold">
            <tr>
                <th class="px-4 py-3">Name</th>
                <th class="px-4 py-3">Email</th>
                <th class="px-4 py-3">Role</th>
                <th class="px-4 py-3">Created At</th>
                <th class="px-4 py-3 text-center">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($allUsers && $allUsers->num_rows > 0): ?>
                <?php while ($user = $allUsers->fetch_assoc()):
                    $userId = (int)$user['id'];
                    $firstName = htmlspecialchars($user['first_name'], ENT_QUOTES, 'UTF-8');
                    $lastName = htmlspecialchars($user['last_name'], ENT_QUOTES, 'UTF-8');
                    $email = htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8');
                    $createdAt = htmlspecialchars(date('F j, Y', strtotime($user['created_at'])), ENT_QUOTES, 'UTF-8');
                    $role = htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8');
                ?>
                    <tr id="user-row-<?= $userId ?>" class="border-b hover:bg-[#FAF5EE] transition-colors duration-200">
                        <td class="px-4 py-3 font-medium text-[#1D503A] name"><?= $firstName . ' ' . $lastName ?></td>
                        <td class="px-4 py-3 text-[#1D503A] email"><?= $email ?></td>
                        <td class="px-4 py-3 font-semibold text-[#1D503A] role"><?= ucfirst($role) ?></td>
                        <td class="px-4 py-3 text-[#1D503A]"><?= $createdAt ?></td>
                        <td class="px-4 py-3 text-center">
                            <button
                                onclick="openModal(<?= $userId ?>)"
                                class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded-md text-sm font-semibold"
                                aria-label="Edit user <?= $firstName ?> <?= $lastName ?>">
                                Edit
                            </button>
                        </td>
                    </tr>

                    <!-- Edit User Modal -->
                    <div
                        id="modal-<?= $userId ?>"
                        class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black bg-opacity-40"
                        style="backdrop-filter: blur(4px);"
                        role="dialog" aria-modal="true" aria-labelledby="modalTitle-<?= $userId ?>"
                        onclick="closeModal(<?= $userId ?>)">
                        <div
                            onclick="event.stopPropagation()"
                            class="bg-white rounded-lg shadow-xl max-w-lg w-full p-6 overflow-auto max-h-[90vh]">
                            <h3 id="modalTitle-<?= $userId ?>" class="text-xl font-bold text-[#1D503A] mb-4">Edit User</h3>

                            <form onsubmit="return submitEditForm(event, <?= $userId ?>)" class="space-y-4" id="editForm-<?= $userId ?>">
                                <input type="hidden" name="id" value="<?= $userId ?>">

                                <div>
                                    <label class="block text-sm font-medium text-[#1D503A]" for="first_name_<?= $userId ?>">First Name</label>
                                    <input type="text" name="first_name" id="first_name_<?= $userId ?>" value="<?= $firstName ?>" required autofocus autocomplete="given-name" class="border border-gray-300 rounded px-3 py-2 w-full" />
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-[#1D503A]" for="last_name_<?= $userId ?>">Last Name</label>
                                    <input type="text" name="last_name" id="last_name_<?= $userId ?>" value="<?= $lastName ?>" required autocomplete="family-name" class="border border-gray-300 rounded px-3 py-2 w-full" />
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-[#1D503A]" for="email_<?= $userId ?>">Email</label>
                                    <input type="email" name="email" id="email_<?= $userId ?>" value="<?= $email ?>" required autocomplete="email" class="border border-gray-300 rounded px-3 py-2 w-full" />
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-[#1D503A]" for="password_<?= $userId ?>">New Password (optional)</label>
                                    <input type="password" name="password" id="password_<?= $userId ?>" placeholder="Leave blank to keep current" autocomplete="new-password" class="border border-gray-300 rounded px-3 py-2 w-full" />
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-[#1D503A]" for="confirm_password_<?= $userId ?>">Confirm New Password</label>
                                    <input type="password" name="confirm_password" id="confirm_password_<?= $userId ?>" placeholder="Re-enter new password" autocomplete="new-password" class="border border-gray-300 rounded px-3 py-2 w-full" />
                                </div>

                                <div id="error-msg-<?= $userId ?>" class="text-red-600 text-sm hidden"></div>
                                <div id="success-msg-<?= $userId ?>" class="text-green-600 text-sm hidden mt-2">Changes saved successfully.</div>

                                <div class="flex justify-end space-x-4 pt-2">
                                    <button type="button" onclick="closeModal(<?= $userId ?>)" class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400 transition">Cancel</button>
                                    <button type="submit" class="bg-[#1D503A] text-white px-4 py-2 rounded hover:bg-[#15412B] transition">Save</button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="py-6 text-center text-gray-500 font-medium">
                        No user accounts found.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
    function openModal(userId) {
        const modal = document.getElementById('modal-' + userId);
        if (modal) {
            modal.classList.remove('hidden');
            const firstInput = modal.querySelector('input, button, select, textarea');
            if (firstInput) firstInput.focus();
        }
    }

    function closeModal(userId) {
        const modal = document.getElementById('modal-' + userId);
        if (modal) {
            modal.classList.add('hidden');
            const errorMsg = document.getElementById('error-msg-' + userId);
            const successMsg = document.getElementById('success-msg-' + userId);
            if (errorMsg) {
                errorMsg.classList.add('hidden');
                errorMsg.textContent = '';
            }
            if (successMsg) {
                successMsg.classList.add('hidden');
            }
            const form = document.getElementById('editForm-' + userId);
            if (form) {
                form.password.value = '';
                form.confirm_password.value = '';
            }
        }
    }

    function submitEditForm(event, userId) {
        event.preventDefault();

        const form = document.getElementById('editForm-' + userId);
        if (!form) {
            alert('Unexpected error: Form not found.');
            return false;
        }

        const password = form.password.value.trim();
        const confirmPassword = form.confirm_password.value.trim();

        const errorMsg = document.getElementById('error-msg-' + userId);
        const successMsg = document.getElementById('success-msg-' + userId);

        if (!errorMsg || !successMsg) {
            alert('Unexpected error: Message elements missing.');
            return false;
        }

        errorMsg.classList.add('hidden');
        successMsg.classList.add('hidden');
        errorMsg.textContent = '';

        if ((password || confirmPassword) && password !== confirmPassword) {
            errorMsg.textContent = "Passwords do not match.";
            errorMsg.classList.remove('hidden');
            return false;
        }

        const submitButton = form.querySelector('button[type="submit"]');
        if (submitButton) submitButton.disabled = true;

        const formData = new FormData(form);

        fetch('edit-admin.php', {
                method: 'POST',
                body: formData,
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    const updated = data.updated_fields || {};
                    const row = document.getElementById('user-row-' + userId);
                    if (row) {
                        if (typeof updated.first_name === 'string' && typeof updated.last_name === 'string') {
                            row.querySelector('.name').textContent = updated.first_name + ' ' + updated.last_name;
                        }
                        if (typeof updated.email === 'string') {
                            row.querySelector('.email').textContent = updated.email;
                        }
                        if (typeof updated.role === 'string') {
                            row.querySelector('td.font-semibold').textContent = updated.role.charAt(0).toUpperCase() + updated.role.slice(1);
                        }
                    }
                    successMsg.classList.remove('hidden');
                    setTimeout(() => {
                        closeModal(userId);
                        if (submitButton) submitButton.disabled = false;
                    }, 1500);
                } else {
                    errorMsg.textContent = data.message || 'Failed to update user.';
                    errorMsg.classList.remove('hidden');
                    if (submitButton) submitButton.disabled = false;
                }
            })
            .catch(err => {
                errorMsg.textContent = 'Error: ' + err.message;
                errorMsg.classList.remove('hidden');
                if (submitButton) submitButton.disabled = false;
            });

        return false;
    }
</script>